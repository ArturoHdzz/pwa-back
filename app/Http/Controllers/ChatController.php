<?php
// app/Http/Controllers/ChatController.php
namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $convs = ChatConversation::query()
            ->where('organization_id', $profile->organization_id)
            ->whereHas('members', function ($q) use ($profile) {
                $q->where('user_id', $profile->id);
            })
            ->get();

        return response()->json($convs);
    }

    public function messages(Request $request, string $id)
    {
        $user = $request->user();
        $profile = $user->profile;

        $conversation = ChatConversation::where('id', $id)
            ->where('organization_id', $profile->organization_id)
            ->firstOrFail();

        $messages = ChatMessage::where('conversation_id', $conversation->id)
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function send(Request $request, string $id)
    {
        $user = $request->user();
        $profile = $user->profile;

        $request->validate([
            'body' => ['required', 'string'],
        ]);

        $conversation = ChatConversation::where('id', $id)
            ->where('organization_id', $profile->organization_id)
            ->firstOrFail();

        $message = ChatMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $conversation->id,
            'organization_id' => $profile->organization_id,
            'sender_id' => $profile->id,
            'body' => $request->body,
        ]);

        return response()->json($message, 201);
    }
}

