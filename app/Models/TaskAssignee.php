<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TaskAssignee extends Pivot
{
    protected $table = 'task_assignees';
    public $timestamps = false;

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    protected $fillable = [
        'task_id',
        'user_id',
        'status',
        'submission_content',
        'grade',
        'feedback',
        'submitted_at',
    ];
}

