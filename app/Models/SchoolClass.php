<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = ['class', 'section', 'academic_year', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['name'];

    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_subjects');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'class_id');
    }

    public function subjectGroups()
    {
        return $this->hasMany(ClassStreamSubjectGroup::class, 'class_id');
    }

    public function classTeacher()
    {
        return $this->belongsTo(User::class, 'class_teacher_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->section ? "Class {$this->class} - {$this->section}" : "Class {$this->class}";
    }

    // Attribute alias for backward compatibility with code that expects 'name'
    public function getNameAttribute(): string
    {
        return $this->attributes['class'] ?? '';
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['class'] = $value;
    }
}
