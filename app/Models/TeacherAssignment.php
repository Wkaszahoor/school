<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $fillable = ['teacher_id', 'class_id', 'subject_id', 'academic_year', 'assignment_type', 'group_id'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function teacherProfile()
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id', 'user_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'group_id');
    }
}
