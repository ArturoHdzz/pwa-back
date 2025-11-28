<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
     public function store(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'endpoint' => 'required|string',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
            'content_encoding' => 'required|string',
        ]);

        $profile->pushSubscriptions()
            ->updateOrCreate(
                ['endpoint' => $data['endpoint']],
                $data
            );

        return response()->json(['ok' => true]);
    }
}
