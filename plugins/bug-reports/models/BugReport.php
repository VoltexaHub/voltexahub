<?php

namespace Plugins\BugReports\Models;

use Illuminate\Database\Eloquent\Model;

class BugReport extends Model
{
    protected $table = 'bug_reports';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'steps_to_reproduce',
        'severity',
        'status',
        'priority',
        'assigned_to',
        'staff_notes',
        'attachments',
        'environment',
        'url',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function reporter()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }
}
