<?php

namespace App\Http\Controllers;
use App\Models\WebPushSubscription;
use App\services\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebPushController extends Controller
{
   public function store(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

       Log::info('entro al controller');

        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        WebPushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'profile_id'       => $profile->id,
                'public_key'       => $data['keys']['p256dh'],
                'auth_token'       => $data['keys']['auth'],
                'content_encoding' => $request->input('contentEncoding', 'aesgcm'),
            ]
        );

        return response()->json(['ok' => true]);
    }
}
