<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'subject_id', 'teacher_id',
        'exam_type', 'academic_year', 'term', 'total_marks',
        'obtained_marks', 'percentage', 'grade', 'gpa_point',
        'is_locked', 'lock_group_id', 'approval_status', 'approved_by', 'approved_at',
        'class_teacher_reviewed_by', 'class_teacher_reviewed_at', 'class_teacher_remarks',
        'principal_remarks', 'rejection_reason',
    ];

    protected $casts = [
        'percentage'              => 'decimal:2',
        'gpa_point'               => 'decimal:2',
        'is_locked'               => 'boolean',
        'approved_at'             => 'datetime',
        'class_teacher_reviewed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function classTeacherReviewedBy()
    {
        return $this->belongsTo(User::class, 'class_teacher_reviewed_by');
    }

    public function getGradeColorAttribute(): string
    {
        return match(true) {
            $this->percentage >= 90 => 'green',
            $this->percentage >= 70 => 'blue',
            $this->percentage >= 50 => 'yellow',
            $this->percentage >= 33 => 'orange',
            default                 => 'red',
        };
    }
}
