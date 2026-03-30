<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProficiencyTestAssignment extends Model
{
    protected $fillable = [
        'test_id', 'user_id', 'assigned_by', 'due_date', 'status',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function test()
    {
        return $this->belongsTo(ProficiencyTest::class, 'test_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function attempt()
    {
        return $this->hasOne(ProficiencyTestAttempt::class, 'assignment_id')->latest();
    }
}
