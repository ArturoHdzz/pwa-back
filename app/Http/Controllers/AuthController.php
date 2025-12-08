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

class AuthController extends Controller
{
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
            return response()->json([
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        $user->ultimo_login = now();
        $user->save();

        if (!$user->activo) {
            return response()->json(['message' => 'Esta cuenta está desactivada.'], 403);
        }

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