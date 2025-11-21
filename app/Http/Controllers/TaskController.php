<?php

// app/Http/Controllers/TaskController.php
namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orgId = $user->profile->organization_id;

        $query = Task::query()->where('organization_id', $orgId);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->orderBy('due_at')->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;

        $data = $request->validate([
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'group_id' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ]);

        $task = Task::create([
            'id' => (string) Str::uuid(),
            'organization_id' => $profile->organization_id,
            'group_id' => $data['group_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'created_by' => $profile->id,
        ]);

        return response()->json($task, 201);
    }
}
