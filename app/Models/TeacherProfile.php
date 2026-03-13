<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    protected $fillable = [
        'user_id', 'qualification', 'specialization', 'specialisation', 'joining_date', 'bio',
        'phone', 'cnic', 'gender', 'dob', 'certifications', 'experience_years',
        'previous_school', 'achievements', 'date_joined', 'is_active',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'dob'          => 'date',
        'date_joined'  => 'date',
        'is_active'    => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id', 'user_id');
    }

    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'teacher_id', 'user_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'teacher_id', 'user_id');
    }

    // Handle both British (specialisation) and American (specialization) spellings
    public function setSpecialisationAttribute($value)
    {
        $this->attributes['specialization'] = $value;
    }

    public function getSpecialisationAttribute()
    {
        return $this->attributes['specialization'] ?? null;
    }

    // Handle employee_id (computed from user_id, not persisted to DB)
    public function getEmployeeIdAttribute()
    {
        // Generate from user_id
        return $this->user_id ? 'EMP-' . str_pad($this->user_id, 5, '0', STR_PAD_LEFT) : null;
    }

    public function setEmployeeIdAttribute($value)
    {
        // Ignore attempts to set - it's generated from user_id
        // This prevents validation errors when the import controller tries to set it
    }
}
