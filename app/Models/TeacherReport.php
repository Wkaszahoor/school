<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherReport extends Model
{
    protected $fillable = [
        'subject_teacher_id',
        'class_teacher_id',
        'class_id',
        'report_type',
        'notes',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function subjectTeacher()
    {
        return $this->belongsTo(User::class, 'subject_teacher_id');
    }

    public function classTeacher()
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
