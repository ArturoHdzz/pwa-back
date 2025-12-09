<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\TwoFactorCode; 
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{

    private function validateTurnstile(Request $request)
    {
        $token = $request->input('turnstile_token');

        if (!$token) {
            return false;
        }

        $secret = config('services.turnstile.secret') ?? env('TURNSTILE_SECRET_KEY');

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        if (!$response->ok()) {
            return false;
        }

        $data = $response->json();
        return $data['success'] ?? false;
    }
    
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'role' => 'required|in:jefe,profesor',
            'organization_name' => 'required_without:organization_code|string|max:255|nullable',
            'organization_code' => 'nullable|string', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $organization = null;
            $role = 'student';

            if ($request->filled('organization_code')) {
                $inputCode = trim($request->organization_code);
                $organization = Organization::whereRaw('LOWER(code) = ?', [strtolower($inputCode)])->first();

                if (!$organization) {
                    return response()->json([
                        'message' => 'Errores de validación',
                        'errors' => ['organization_code' => ['El código de organización no existe.']]
                    ], 422);
                }
                
                $role = 'Alumno'; 

            } else {
                $organization = Organization::create([
                    'name' => $request->organization_name,
                    'code' => strtolower(Str::random(6)) 
                ]);
                
                $role = 'jefe';
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'telefono' => $request->telefono,
                'activo' => true
            ]);

            $profile = Profile::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'display_name' => $user->name . ' ' . $user->apellido_paterno,
                'role' => $role,
            ]);

            DB::commit();

            $token = $user->createToken('auth_token')->accessToken;
            
            $user->load('profile.organization');

            return response()->json([
                'message' => $request->filled('organization_code') ? 'Te has unido a la organización exitosamente' : 'Organización creada exitosamente',
                'user' => $user,
                'token' => $token
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Errores', 'errors' => $validator->errors()], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->activo) {
            return response()->json(['message' => 'Cuenta desactivada.'], 403);
        }

        $code = rand(100000, 999999);
        $user->two_factor_code = $code;
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->save();

        try {
            Mail::to($user->email)->send(new TwoFactorCode($code));
        } catch (\Exception $e) {
             Log::error("Error enviando correo: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Código enviado a tu correo',
            'require_2fa' => true,
            'user_id' => $user->id
        ]);
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'code' => 'required|integer'
        ]);

        $user = User::find($request->user_id);

        if (!$user || $user->two_factor_code != $request->code) {
            return response()->json(['message' => 'Código inválido'], 401);
        }

        if ($user->two_factor_expires_at < now()) {
            return response()->json(['message' => 'El código ha expirado'], 401);
        }

        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->ultimo_login = now();
        $user->save();

        $token = $user->createToken('auth_token')->accessToken;
        $user->load('profile.organization');

        return response()->json([
            'message' => 'Login exitoso',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('profile.organization'));
    }
}