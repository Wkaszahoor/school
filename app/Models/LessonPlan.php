<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonPlan extends Model
{
    protected $fillable = [
        'teacher_id', 'class_id', 'subject_id', 'week_start',
        'lesson_plan', 'work_plan', 'approval_status', 'principal_comment',
        'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'week_start'  => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'approved' => 'green',
            'rejected' => 'red',
            'pending'  => 'yellow',
            default    => 'gray',
        };
    }
}
