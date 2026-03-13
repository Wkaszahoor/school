<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseEnrollment extends Model
{
    use SoftDeletes;

    protected $table = 'course_enrollments';

    protected $fillable = [
        'course_id',
        'teacher_id',
        'enrollment_status',
        'enrolled_at',
        'completed_at',
        'progress_percentage',
        'attendance_count',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'float',
        'metadata' => 'json',
    ];

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function completion()
    {
        return $this->hasOne(CourseCompletion::class, 'enrollment_id');
    }

    public function scopeEnrolled($query)
    {
        return $query->where('enrollment_status', 'enrolled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('enrollment_status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('enrollment_status', 'pending');
    }

    public function getIsCompleteAttribute()
    {
        return $this->enrollment_status === 'completed';
    }

    public function getIsInProgressAttribute()
    {
        return $this->enrollment_status === 'enrolled' && $this->progress_percentage < 100;
    }
}
