<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    function index(Request $request)
    {
        $user = $request->user();
        $organizations = $user->organizations()->get();
        return response()->json($organizations);
    }

    public function show(Request $request, $organizationId)
    {
        $authUser = $request->user();

        $organization = $authUser->organizations()
            ->with([
                'users' => function ($q) {
                    $q->select('users.id', 'users.name', 'users.apellido_paterno', 'users.apellido_materno', 'users.email');
                },
                'groups' 
            ])
            ->findOrFail($organizationId);

        $users = $organization->users
            ->filter(fn($user) => $user->id !== $authUser->id)
            ->values();

        return response()->json([
            'organization' => [
                'id'   => $organization->id,
                'name' => $organization->name,
            ],
            'users' => $users->map(function ($user) {
                return [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'apellido_paterno'=> $user->apellido_paterno,
                    'apellido_materno'=> $user->apellido_materno,
                    'email'           => $user->email,
                    'display_name'    => $user->pivot->display_name,
                    'role'            => $user->pivot->role,
                    'profile_id'     => $user->pivot->id,
                ];
            }),
            'groups' => $organization->groups->map(function ($group) {
                return [
                    'id'          => $group->id,
                    'name'        => $group->name,
                    'description' => $group->description,
                    'is_class'    => $group->is_class,
                ];
            }),
        ]);
    }
}
