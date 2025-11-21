<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json($user->profile);
    }
    public function post(Request $request)
    {
        $user = $request->user();
        return response()->json($user->profile);
    }
    
    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
        ]);

        $profile->update($data);

        return response()->json($profile);
    }
    public function deactivate(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $profile->active = false;
        $profile->save();

        return response()->json(['message' => 'Perfil desactivado correctamente.']);
    }
    public function activate(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $profile->active = true;
        $profile->save();

        return response()->json(['message' => 'Perfil activado correctamente.']);
    }

}
