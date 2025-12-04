<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobilePushTokenController extends Controller
{
   public function store(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'token'    => 'required|string',
            'platform' => 'nullable|string', // 'android' | 'ios'
        ]);

        MobilePushToken::updateOrCreate(
            [
                'profile_id' => $profile->id,
                'token'      => $data['token'],
            ],
            [
                'platform'   => $data['platform'] ?? null,
            ]
        );

        return response()->json(['ok' => true]);
    }
}
