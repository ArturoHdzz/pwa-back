<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Group;
use App\Models\Profile;
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

    public function myTasks(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $tasks = Task::whereHas('assignees', function ($query) use ($profile) {
                $query->where('task_assignees.user_id', $profile->id);
            })
            ->with(['group:id,name'])
            ->orderBy('due_at', 'asc')
            ->get()
            ->map(function ($task) use ($profile) {
                $assignee = DB::table('task_assignees')
                    ->where('task_id', $task->id)
                    ->where('user_id', $profile->id)
                    ->first();
                
                $task->my_status = $assignee->status ?? 'pending';
                $task->my_grade = $assignee->grade ?? null;
                $task->is_individual = is_null($task->group_id);
                return $task;
            });

        return response()->json($tasks);
    }

    public function individualTasks(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        if (!in_array($profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $tasks = Task::whereNull('group_id')
            ->where('organization_id', $profile->organization_id)
            ->withCount('assignees')
            ->with(['assignees' => function($q) {
                $q->select('profiles.id', 'profiles.display_name');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request, $groupId = null)
    {
        $user = $request->user();
        $organizationId = $user->profile->organization_id;
        
        if (!in_array($user->profile->role, ['jefe', 'profesor'])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'uuid|exists:profiles,id',
            'is_individual' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->filled('assignee_ids')) {
            $validAssignees = Profile::whereIn('id', $request->assignee_ids)
                ->where('organization_id', $organizationId)
                ->count();

            if ($validAssignees !== count($request->assignee_ids)) {
                return response()->json([
                    'message' => 'Algunos usuarios no pertenecen a tu organización.'
                ], 403);
            }
        }

        if ($groupId) {
            $group = Group::where('id', $groupId)
                ->where('organization_id', $organizationId)
                ->first();

            if (!$group) {
                return response()->json([
                    'message' => 'El grupo no pertenece a tu organización.'
                ], 403);
            }
        }

        return DB::transaction(function () use ($request, $groupId, $user, $organizationId) {
            
            $isIndividual = $request->boolean('is_individual', false);
            
            $task = Task::create([
                'id' => (string) Str::uuid(),
                'organization_id' => $organizationId,
                'group_id' => $groupId,
                'created_by' => $user->profile->id,
                'title' => $request->title,
                'description' => $request->description,
                'due_at' => $request->due_date,
                'status' => TaskStatus::PENDING,
                'is_individual' => $isIndividual 
            ]);

            $assignments = [];

            if ($isIndividual && $request->filled('assignee_ids')) {
                foreach ($request->assignee_ids as $assigneeId) {
                    $assignments[] = [
                        'task_id' => $task->id,
                        'user_id' => $assigneeId,
                        'status' => 'pending',
                    ];
                }
            } elseif ($groupId) {
                $memberIds = DB::table('group_members')
                    ->where('group_id', $groupId)
                    ->pluck('user_id');

                foreach ($memberIds as $memberId) {
                    $assignments[] = [
                        'task_id' => $task->id,
                        'user_id' => $memberId,
                        'status' => 'pending',
                    ];
                }
            }

            if (!empty($assignments)) {
                DB::table('task_assignees')->insert($assignments);
            }

            $task->loadCount('assignees');

            return response()->json([
                'message' => $isIndividual 
                    ? 'Tarea individual creada y asignada a ' . count($assignments) . ' miembro(s).'
                    : 'Tarea grupal creada y asignada a ' . count($assignments) . ' miembros.',
                'task' => $task
            ], 201);
        });
    }

    public function storeIndividual(Request $request)
    {
        return $this->store($request, null);
    }

    public function destroy(Request $request, $groupId, $taskId)
    {
        $task = Task::where('id', $taskId)->where('group_id', $groupId)->firstOrFail();
        $task->delete();
        return response()->json(['message' => 'Tarea eliminada']);
    }

    public function destroyIndividual(Request $request, $taskId)
    {
        $user = $request->user();
        $task = Task::where('id', $taskId)
            ->whereNull('group_id')
            ->where('organization_id', $user->profile->organization_id)
            ->firstOrFail();
        
        $task->delete();
        return response()->json(['message' => 'Tarea individual eliminada']);
    }

    public function show(Request $request, $groupId, $taskId)
    {
        $task = Task::where('id', $taskId)
            ->where('group_id', $groupId)
            ->with(['assignees' => function($query) {
                $query->select('profiles.id', 'profiles.display_name', 'profiles.user_id')
                      ->withPivot('status', 'submission_content', 'grade', 'feedback', 'submitted_at');
            }])
            ->firstOrFail();

        return response()->json($task);
    }

    public function showIndividual(Request $request, $taskId)
    {
        $user = $request->user();
        $task = Task::where('id', $taskId)
            ->whereNull('group_id')
            ->where('organization_id', $user->profile->organization_id)
            ->with(['assignees' => function($query) {
                $query->select('profiles.id', 'profiles.display_name', 'profiles.user_id')
                      ->withPivot('status', 'submission_content', 'grade', 'feedback', 'submitted_at');
            }])
            ->firstOrFail();

        return response()->json($task);
    }

    public function gradeStudent(Request $request, $groupId, $taskId)
    {
        $request->validate([
            'user_id' => 'required|exists:profiles,id',
            'grade' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string'
        ]);

        DB::table('task_assignees')
            ->where('task_id', $taskId)
            ->where('user_id', $request->user_id)
            ->update([
                'grade' => $request->grade,
                'feedback' => $request->feedback,
                'status' => 'graded'
            ]);

        return response()->json(['message' => 'Calificación guardada']);
    }

    public function gradeIndividual(Request $request, $taskId)
    {
        $request->validate([
            'user_id' => 'required|exists:profiles,id',
            'grade' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string'
        ]);

        DB::table('task_assignees')
            ->where('task_id', $taskId)
            ->where('user_id', $request->user_id)
            ->update([
                'grade' => $request->grade,
                'feedback' => $request->feedback,
                'status' => 'graded'
            ]);

        return response()->json(['message' => 'Calificación guardada']);
    }
}
