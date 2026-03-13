<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SickRecord extends Model
{
    protected $fillable = [
        'student_id', 'sick_date', 'days_off', 'reason', 'doctor_note',
        'referred_by', 'status', 'approved_by', 'approved_at',
        'doctor_prescription', 'doctor_suggestion', 'examined_by', 'examined_at',
    ];

    protected $casts = [
        'sick_date'    => 'date',
        'approved_at'  => 'datetime',
        'examined_at'  => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function referredBy()
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function examinedBy()
    {
        return $this->belongsTo(User::class, 'examined_by');
    }
}
