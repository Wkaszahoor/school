<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingCourse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_code',
        'course_name',
        'description',
        'instructor_id',
        'course_type',
        'level',
        'objectives',
        'duration_hours',
        'max_participants',
        'start_date',
        'end_date',
        'location',
        'status',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'metadata' => 'json',
    ];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function materials()
    {
        return $this->hasMany(CourseMaterial::class, 'course_id');
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'course_id');
    }

    public function completions()
    {
        return $this->hasMany(CourseCompletion::class, 'course_id');
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class, 'course_id');
    }

    public function getEnrolledTeachersCount()
    {
        return $this->enrollments()->where('enrollment_status', 'enrolled')->count();
    }

    public function getCompletedTeachersCount()
    {
        return $this->enrollments()->where('enrollment_status', 'completed')->count();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())->where('status', '!=', 'cancelled');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('course_type', $type);
    }
}
