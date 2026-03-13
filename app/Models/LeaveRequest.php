<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'request_type', 'teacher_id', 'student_id', 'class_id',
        'from_date', 'to_date', 'reason', 'status', 'requested_by',
        'approved_by', 'approved_at',
    ];

    protected $casts = [
        'from_date'   => 'date',
        'to_date'     => 'date',
        'approved_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getDaysAttribute(): int
    {
        return $this->from_date->diffInDays($this->to_date) + 1;
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
