<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCompletion extends Model
{
    protected $table = 'course_completions';

    public $timestamps = true;

    protected $fillable = [
        'enrollment_id',
        'course_id',
        'teacher_id',
        'final_score',
        'grade',
        'hours_attended',
        'completion_date',
        'is_certified',
        'instructor_feedback',
        'completion_metrics',
    ];

    protected $casts = [
        'completion_date' => 'datetime',
        'is_certified' => 'boolean',
        'completion_metrics' => 'json',
    ];

    public function enrollment()
    {
        return $this->belongsTo(CourseEnrollment::class, 'enrollment_id');
    }

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getGradeAttribute()
    {
        if (!$this->final_score) {
            return null;
        }

        $score = $this->final_score;
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    public function scopeCertified($query)
    {
        return $query->where('is_certified', true);
    }
}
