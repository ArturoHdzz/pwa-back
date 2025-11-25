<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $organizationId = $user->profile->organization_id;

        $groups = Group::where('organization_id', $organizationId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!in_array($profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No tienes permisos para crear grupos.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_class' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $group = Group::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $profile->organization_id,
            'name' => $request->name,
            'description' => $request->description,
            'is_class' => $request->is_class ?? true,
            'code' => strtoupper(Str::random(6))
        ]);

        return response()->json([
            'message' => 'Grupo creado exitosamente',
            'group' => $group
        ], 201);
    }

    public function joinByCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = $request->user();
        
        $group = Group::where('code', $request->code)->first();

        if (!$group) {
            return response()->json(['message' => 'Código de grupo inválido'], 404);
        }

        $exists = DB::table('group_members')
            ->where('group_id', $group->id)
            ->where('user_id', $user->profile->id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ya eres miembro de este grupo'], 422);
        }

        DB::table('group_members')->insert([
            'group_id' => $group->id,
            'user_id' => $user->profile->id,
            'role' => 'user'
        ]);

        return response()->json([
            'message' => 'Te has unido al grupo exitosamente',
            'group' => $group
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $group = Group::where('id', $id)
            ->where('organization_id', $user->profile->organization_id)
            ->firstOrFail();

        return response()->json($group);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $group = Group::where('id', $id)
            ->where('organization_id', $user->profile->organization_id)
            ->firstOrFail();

        if (!in_array($user->profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $group->update($request->only(['name', 'description', 'is_class']));

        return response()->json([
            'message' => 'Grupo actualizado',
            'group' => $group
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $group = Group::where('id', $id)
            ->where('organization_id', $user->profile->organization_id)
            ->firstOrFail();

        if (!in_array($user->profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $group->delete();

        return response()->json(['message' => 'Grupo eliminado']);
    }
}
