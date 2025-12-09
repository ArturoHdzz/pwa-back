<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Group;
use App\Models\Task;
use App\Models\Profile; 
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->profile->role === 'Alumno' || $user->profile->role === 'User') {
            return response()->json([
                'is_student' => true,
                'organization' => $user->profile->organization,
                'stats' => null
            ]);
        }

        $orgId = $user->profile->organization_id;
        
        $organization = $user->profile->organization;

        $stats = [
            'students' => Profile::where('organization_id', $organization->id)->where('role', 'student')->count(),
            'groups' => Group::where('organization_id', $organization->id)->count(),
            'needs_grading' => DB::table('task_assignees')
                ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
                ->where('tasks.organization_id', $organization->id)
                ->where('task_assignees.status', 'submitted')
                ->count(),
            'pending_submission' => DB::table('task_assignees')
                ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
                ->where('tasks.organization_id', $organization->id)
                ->whereIn('task_assignees.status', ['pending', 'in_progress']) 
                ->count(),
        ];

        $studentsPerGroup = Group::where('organization_id', $orgId)
            ->withCount(['members as student_count'])
            ->get()
            ->map(function($group) {
                return [
                    'name' => $group->name,
                    'student_count' => $group->student_count
                ];
            });

        $recentSubmissions = DB::table('task_assignees')
            ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
            ->join('profiles', 'task_assignees.user_id', '=', 'profiles.id')
            ->where('tasks.organization_id', $orgId)
            ->whereIn('task_assignees.status', ['submitted', 'graded'])
            ->select(
                'profiles.display_name as student',
                'tasks.title as task_title',
                'tasks.group_id',
                'tasks.id as task_id',
                'task_assignees.status',
                'task_assignees.grade',
                'task_assignees.submitted_at'
            )
            ->orderBy('task_assignees.submitted_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'organization' => $organization,
            'stats' => $stats,
            'students_per_group' => $studentsPerGroup,
            'performance' => [], 
            'recent_submissions' => $recentSubmissions
        ]);
    }
}
