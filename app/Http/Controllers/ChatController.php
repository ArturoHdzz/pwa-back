<?php
// app/Http/Controllers/ChatController.php
namespace App\Http\Controllers;
use App\Models\Profile;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Enums\ConversationType;

class ChatController extends Controller
{

    //ver todas las conversaciones del usuario
    public function index(Request $request)
{
        $user = $request->user();
        $profile = $user->profile; 

        $request->validate([
            'organization_id' => ['nullable', 'uuid'],
        ]);

        $organizationId = $request->input('organization_id', $profile->organization_id);


        $convs = ChatConversation::query()
            ->where('organization_id', $organizationId)
            ->where(function ($q) use ($profile) {
                // DMs donde participa este perfil
                $q->where(function ($q2) use ($profile) {
                    $q2->where('type', 'dm')
                        ->whereHas('members', function ($q3) use ($profile) {
                            $q3->where('chat_members.user_id', $profile->id);
                        });
                })
                // O chats de grupo donde es miembro del grupo
                ->orWhere(function ($q2) use ($profile) {
                    $q2->where('type', 'group')
                        ->whereHas('group.members', function ($q3) use ($profile) {
                            $q3->where('group_members.user_id', $profile->id);
                        });
                });
            })
            ->with([
                'members.user', // para DMs
                'group',        // para grupos
            ])
            ->get()
            ->map(function ($conv) use ($profile) {
                $data = [
                    'id'              => $conv->id,
                    'organization_id' => $conv->organization_id,
                    'type'            => $conv->type,
                    'group_id'        => $conv->group_id,
                ];

               if ($conv->type->value === ConversationType::DM->value) {

                    // perfil del OTRO participante (no el autenticado)
                    $other = $conv->members->firstWhere('id', '!=', $profile->id);

                    $data['participant'] = $other ? [
                        'profile_id'       => $other->id,
                        'display_name'     => $other->display_name,
                        'name'             => optional($other->user)->name,
                        'apellido_paterno' => optional($other->user)->apellido_paterno,
                        'apellido_materno' => optional($other->user)->apellido_materno,
                    ] : null;
                }

                if ($conv->type->value === ConversationType::GROUP->value && $conv->group) {
                    $data['group'] = [
                        'id'   => $conv->group->id,
                        'name' => $conv->group->name,
                    ];
                }

                return $data;
            })
            ->values();


    return response()->json($convs);
}
 public function startDm(Request $request)
    {
        $user = $request->user();
        $myProfile = $user->profile; // profiles.id (uuid)

        $request->validate([
            'other_profile_id' => ['required', 'uuid', 'different:my_profile_id'],
        ]);

        $otherProfileId = $request->input('other_profile_id');

        // Opcional: asegurar que el otro perfil existe y es de la misma org
        $otherProfile = Profile::where('id', $otherProfileId)
            ->where('organization_id', $myProfile->organization_id)
            ->firstOrFail();

        // 1) Buscar si YA existe un DM entre ambos perfiles en esta organización
        $conversation = ChatConversation::query()
            ->where('type', 'dm')
            ->where('organization_id', $myProfile->organization_id)
            ->whereHas('members', function ($q) use ($myProfile) {
                $q->where('chat_members.user_id', $myProfile->id);
            })
            ->whereHas('members', function ($q) use ($otherProfileId) {
                $q->where('chat_members.user_id', $otherProfileId);
            })
            ->first();

        if (! $conversation) {
            // 2) Si no existe, crear la conversación
            $conversation = ChatConversation::create([
                'organization_id' => $myProfile->organization_id,
                'type' => 'dm',
                'group_id' => null,
            ]);

            // 3) Agregar los dos miembros (perfiles) a chat_members
            $conversation->members()->attach([
                $myProfile->id,
                $otherProfileId,
            ]);
        }

        // 4) Devolver la conversación con sus miembros
        $conversation->load('members:id,display_name');

        return response()->json($conversation);
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

