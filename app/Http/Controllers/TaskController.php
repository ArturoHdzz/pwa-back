<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Group;
use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request, $groupId)
    {
        $tasks = Task::where('group_id', $groupId)
            ->withCount('assignees') 
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request, $groupId)
    {
        $user = $request->user();
        
        if (!in_array($user->profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $groupId, $user) {
            
            $task = Task::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $user->profile->organization_id,
                'group_id' => $groupId,
                'created_by' => $user->profile->id,
                'title' => $request->title,
                'description' => $request->description,
                'due_at' => $request->due_date,
                'status' => TaskStatus::PENDING 
            ]);

            $memberIds = DB::table('group_members')
                ->where('group_id', $groupId)
                ->pluck('user_id'); 

            $assignments = [];
            foreach ($memberIds as $memberId) {
                $assignments[] = [
                    'task_id' => $task->id,
                    'user_id' => $memberId, 
                ];
            }

            if (!empty($assignments)) {
                DB::table('task_assignees')->insert($assignments);
            }

            return response()->json([
                'message' => 'Tarea creada y asignada a ' . count($assignments) . ' miembros.',
                'task' => $task
            ], 201);
        });
    }

    public function destroy(Request $request, $groupId, $taskId)
    {
        $task = Task::where('id', $taskId)->where('group_id', $groupId)->firstOrFail();
        $task->delete();
        return response()->json(['message' => 'Tarea eliminada']);
    }
}
