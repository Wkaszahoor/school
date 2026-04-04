<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveSetting extends Model
{
    protected $fillable = [
        'academic_year',
        'leave_type',
        'max_days',
        'is_paid',
        'created_by',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
