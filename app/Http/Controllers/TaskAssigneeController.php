<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskAssignee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TaskAssigneeController extends Controller
{
    /**
     * Entregar la tarea (texto y/o archivo).
     * POST /api/tasks/{task}/submit
     */
   public function submit(Request $request, Task $task)
{
    $user = $request->user(); // Sanctum
    $profile = $user->profile;

    if (!$profile) {
        return response()->json([
            'message' => 'No se encontró el perfil del usuario autenticado.',
        ], 403);
    }

    // Verificar que esta tarea está asignada a este perfil
    $taskAssignee = TaskAssignee::where('task_id', $task->id)
        ->where('user_id', $profile->id) // OJO: user_id apunta a profiles.id
        ->firstOrFail();

    $data = $request->validate([
            'submission_text' => ['nullable', 'string'],
            'file'            => ['nullable', 'file', 'max:10240'], // 10 MB
        ]);

        if (
            empty($data['submission_text']) &&
            ! $request->hasFile('file')
        ) {
            return response()->json([
                'message' => 'Debes enviar texto o un archivo para poder entregar la tarea.',
            ], 422);
        }

        $localFilePath   = null;
        $supabaseFileUrl = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // 1) Guardar en storage/app/public 
            // $localFilePath = $file->store(
            //     "tasks/{$task->id}/submissions/{$profile->id}",
            //     'public'
            // );

            // 2) Subir también a Supabase
            try {
                $supabaseFileUrl = $supabase->upload(
                    $file,
                    "tasks/{$task->id}/submissions/{$profile->id}"
                );
            } catch (\Throwable $e) {
                \Log::error('Error al subir archivo de tarea a Supabase', [
                    'error' => $e->getMessage(),
                ]);
                // Si quieres, puedes seguir sin romper la entrega:
                // o lanzar un 500 si quieres ser estricto
            }
        }

        $payload = [
            'text'           => $data['submission_text'] ?? null,
            'local_path'     => $localFilePath,
            'supabase_url'   => $supabaseFileUrl,
        ];

        TaskAssignee::where('task_id', $task->id)
            ->where('user_id', $profile->id)
            ->update([
                'submission_content' => json_encode($payload),
                'status'             => TaskStatus::SUBMITTED->value,
                'submitted_at'       => now(),
            ]);

        $taskAssignee = TaskAssignee::where('task_id', $task->id)
            ->where('user_id', $profile->id)
            ->first();

        return response()->json([
            'message'       => 'Tarea entregada correctamente.',
            'task_assignee' => $taskAssignee,
        ]);
    }

    /**
     * Cambiar el estado de la tarea del lado del alumno.
     * PATCH /api/tasks/{task}/status
     * Solo permite cambiar a IN_PROGRESS.
     */
    public function updateStatus(Request $request, Task $task)
{
    $user = $request->user();
    $profile = $user->profile;

    if (!$profile) {
        return response()->json([
            'message' => 'No se encontró el perfil del usuario autenticado.',
        ], 403);
    }

    $data = $request->validate([
        'status' => [
            'required',
            // Solo permitir in_progress para el alumno
            \Illuminate\Validation\Rule::in([TaskStatus::IN_PROGRESS->value]),
        ],
    ]);

    // Primero verificamos que exista el registro en task_assignees
    $assignee = DB::table('task_assignees')
        ->where('task_id', $task->id)
        ->where('user_id', $profile->id) // OJO: user_id aquí es profiles.id
        ->first();

    if (!$assignee) {
        return response()->json([
            'message' => 'Esta tarea no está asignada a tu perfil.',
        ], 404);
    }

    // Evitar cambios si ya está enviada o calificada
    if (in_array($assignee->status, [
        TaskStatus::SUBMITTED->value,
        TaskStatus::APPROVED->value,
        TaskStatus::REJECTED->value,
        TaskStatus::COMPLETED->value,
    ])) {
        return response()->json([
            'message' => 'No puedes cambiar el estado de una tarea ya enviada o calificada.',
        ], 422);
    }

    // Aquí hacemos el UPDATE real
    DB::table('task_assignees')
        ->where('task_id', $task->id)
        ->where('user_id', $profile->id)
        ->update([
            'status' => $data['status'],
        ]);

    // Recargamos el registro actualizado
    $updated = DB::table('task_assignees')
        ->where('task_id', $task->id)
        ->where('user_id', $profile->id)
        ->first();

    return response()->json([
        'message'       => 'Estado actualizado correctamente.',
        'task_assignee' => $updated,
    ]);
}
}