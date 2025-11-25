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
                    'lastMessage',
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
                    $last = $conv->lastMessage;
                $data['last_message'] = $last?->body ?? '';
                $data['last_from_me'] = $last?->sender_id === $profile->id;
                $data['last_date']    = $last?->created_at;


                    return $data;
                })
                ->values();


                return response()->json($convs);
            }


            public function startConversation(Request $request)
            {
                $user = $request->user();
                $myProfile = $user->profile;

                $request->validate([
                    'participants' => ['required', 'array', 'min:1'],
                    'participants.*' => ['uuid'],
                ]);

                $participants = collect($request->participants)
                    ->filter(fn($id) => $id !== $myProfile->id)
                    ->values()
                    ->all();

                if (count($participants) === 0) {
                    return response()->json(['error' => 'No se puede crear conversación sin otros participantes'], 422);
                }

                // DM: 1 persona
                if (count($participants) === 1) {
                    return $this->startDmInternal($myProfile, $participants[0]);
                }

                // MULTI
                return $this->startMultiInternal($myProfile, $participants);
            }

            private function startDmInternal($myProfile, $otherProfileId)
            {
                // buscar DM existente
                $existing = ChatConversation::query()
                    ->where('type', 'dm')
                    ->where('organization_id', $myProfile->organization_id)
                    ->whereHas('members', fn($q) => $q->where('user_id', $myProfile->id))
                    ->whereHas('members', fn($q) => $q->where('user_id', $otherProfileId))
                    ->first();

                if ($existing) return response()->json($existing);

                $conv = ChatConversation::create([
                    'organization_id' => $myProfile->organization_id,
                    'type' => 'dm',
                ]);

                $conv->members()->attach([$myProfile->id, $otherProfileId]);

                return response()->json($conv);
            }

            private function startMultiInternal($myProfile, $participants)
            {
                $conv = ChatConversation::create([
                    'organization_id' => $myProfile->organization_id,
                    'type' => 'multi',
                ]);

                $conv->members()->attach(array_merge([$myProfile->id], $participants));

                return response()->json($conv);
            }





            public function startGroupConversation(Request $request)
    {
    $request->validate([
        'group_id' => ['required', 'uuid'],
    ]);

    $user = $request->user();
    $profile = $user->profile;

    $groupId = $request->group_id;

    // Verificar si pertenece al grupo
    $belongs = \DB::table('group_members')
        ->where('group_id', $groupId)
        ->where('user_id', $profile->id)
        ->exists();

    if (! $belongs) {
        return response()->json(['error' => 'No perteneces a este grupo'], 403);
    }

    // verificar si ya existe chat
    $existing = ChatConversation::where('group_id', $groupId)->first();
    if ($existing) return response()->json($existing);

    // crear chat
    $conv = ChatConversation::create([
        'organization_id' => $profile->organization_id,
        'type' => 'group',
        'group_id' => $groupId,
    ]);

    return response()->json($conv);
}

        public function sendMessage(Request $request, $conversationId)
        {
            $user = $request->user();
            $profile = $user->profile;

            $request->validate([
                'body' => ['required', 'string'],
            ]);

            $conv = ChatConversation::with('members')->findOrFail($conversationId);

            $isMember = $conv->members->contains('id', $profile->id);

            if (! $isMember) {
                return response()->json(['error' => 'No perteneces a esta conversación'], 403);
            }

            $msg = ChatMessage::create([
              
                'conversation_id' => $conv->id,
                'organization_id' => $conv->organization_id,
                'sender_id'       => $profile->id,   
                'body'            => $request->body,
            ]);

            return response()->json([
        'id'         => $msg->id,
        'body'       => $msg->body,
        'created_at' => $msg->created_at,
        'sender_id'  => $msg->sender_id,
        'is_me'      => true,  
    ], 201);

        }


    public function messages(Request $request, string $conversationId)
    {
        $user = $request->user();
        $profile = $user->profile;

        $perPage = (int) $request->query('per_page', 30);

        $conv = ChatConversation::with('members')->findOrFail($conversationId);

        // validar que es miembro
        $isMember = $conv->members->contains('id', $profile->id);
        if (! $isMember) {
            return response()->json(['error' => 'No perteneces a esta conversación'], 403);
        }

        $messages = ChatMessage::query()
            ->where('conversation_id', $conv->id)
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        // Mapear a un formato cómodo para el front
        $mapped = $messages->getCollection()->map(function (ChatMessage $m) use ($profile) {
            return [
                'id'         => $m->id,
                'body'       => $m->body,
                'created_at' => $m->created_at,
                'is_me'      => $m->sender_id === $profile->id,
                'sender_id'  => $m->sender_id,
            ];
        });

        // devolver con metadatos de paginación
        return response()->json([
            'data' => $mapped,
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page'    => $messages->lastPage(),
                'per_page'     => $messages->perPage(),
                'total'        => $messages->total(),
            ],
        ]);
    }



   
}

