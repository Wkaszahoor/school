<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'student_id', 'class_id', 'subject_id', 'attendance_date', 'status', 'marked_by', 'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'P' => 'green',
            'A' => 'red',
            'L' => 'yellow',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'P' => 'Present',
            'A' => 'Absent',
            'L' => 'Leave',
            default => 'Unknown',
        };
    }
}
