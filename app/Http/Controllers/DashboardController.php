<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $orgId = $user->profile->organization_id;

        $totalGroups = Group::where('organization_id', $orgId)->count();
        
        $totalStudents = DB::table('group_members')
            ->join('groups', 'group_members.group_id', '=', 'groups.id')
            ->where('groups.organization_id', $orgId)
            ->distinct('group_members.user_id')
            ->count('group_members.user_id');

        $needsGrading = DB::table('task_assignees')
            ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
            ->where('tasks.organization_id', $orgId)
            ->where('task_assignees.status', 'submitted')
            ->count();

        $pendingSubmission = DB::table('task_assignees')
            ->join('tasks', 'task_assignees.task_id', '=', 'tasks.id')
            ->where('tasks.organization_id', $orgId)
            ->where('task_assignees.status', 'pending')
            ->count();

        $studentsPerGroup = DB::table('groups')
            ->where('groups.organization_id', $orgId)
            ->leftJoin('group_members', 'groups.id', '=', 'group_members.group_id')
            ->select('groups.name', DB::raw('count(group_members.user_id) as student_count'))
            ->groupBy('groups.id', 'groups.name')
            ->orderByDesc('student_count')
            ->get();

        $groupPerformance = DB::table('groups')
            ->where('groups.organization_id', $orgId)
            ->leftJoin('tasks', 'groups.id', '=', 'tasks.group_id')
            ->leftJoin('task_assignees', 'tasks.id', '=', 'task_assignees.task_id')
            ->select('groups.name', DB::raw('AVG(task_assignees.grade) as average_grade'))
            ->groupBy('groups.id', 'groups.name')
            ->havingRaw('average_grade IS NOT NULL')
            ->orderByDesc('average_grade')
            ->limit(5)
            ->get();

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
                'task_assignees.submitted_at',
                'task_assignees.status',
                'task_assignees.grade'
            )
            ->orderByDesc('task_assignees.submitted_at')
            ->limit(5)
            ->get();

        return response()->json([
            'stats' => [
                'groups' => $totalGroups,
                'students' => $totalStudents,
                'needs_grading' => $needsGrading,
                'pending_submission' => $pendingSubmission
            ],
            'students_per_group' => $studentsPerGroup,
            'performance' => $groupPerformance,
            'recent_submissions' => $recentSubmissions
        ]);
    }
}
