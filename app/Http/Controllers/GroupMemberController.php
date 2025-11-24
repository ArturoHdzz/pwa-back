<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GroupMemberController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $group = Group::where('id', $groupId)
            ->where('organization_id', $request->user()->profile->organization_id)
            ->firstOrFail();

        $members = DB::table('group_members')
            ->join('profiles', 'group_members.user_id', '=', 'profiles.id')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('group_members.group_id', $groupId)
            ->select(
                'profiles.id as profile_id',
                'profiles.display_name',
                'profiles.role',
                'users.email',
                'group_members.role as group_role' 
            )
            ->get();

        return response()->json($members);
    }

    public function available(Request $request, $groupId)
    {
        $orgId = $request->user()->profile->organization_id;

        $existingMemberIds = DB::table('group_members')
            ->where('group_id', $groupId)
            ->pluck('user_id');

        $available = Profile::with('user')
            ->where('organization_id', $orgId)
            ->whereNotIn('id', $existingMemberIds)
            ->get()
            ->map(function($profile) {
                return [
                    'profile_id' => $profile->id,
                    'display_name' => $profile->display_name,
                    'email' => $profile->user->email,
                    'role' => $profile->role
                ];
            });

        return response()->json($available);
    }

    public function store(Request $request, $groupId)
    {
        $request->validate([
            'profile_id' => 'required|exists:profiles,id'
        ]);

        $group = Group::where('id', $groupId)
            ->where('organization_id', $request->user()->profile->organization_id)
            ->firstOrFail();

        $exists = DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $request->profile_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El usuario ya es miembro'], 422);
        }

        DB::table('group_members')->insert([
            'group_id' => $groupId,
            'user_id' => $request->profile_id,
            'role' => 'user'
        ]);

        return response()->json(['message' => 'Miembro agregado exitosamente']);
    }

    public function destroy(Request $request, $groupId, $profileId)
    {
        $group = Group::where('id', $groupId)
            ->where('organization_id', $request->user()->profile->organization_id)
            ->firstOrFail();

        DB::table('group_members')
            ->where('group_id', $groupId)
            ->where('user_id', $profileId)
            ->delete();

        return response()->json(['message' => 'Miembro eliminado del grupo']);
    }
}
