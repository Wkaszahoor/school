<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherDevice extends Model
{
    protected $fillable = [
        'teacher_id',
        'device_type',
        'serial_number',
        'model',
        'made_year',
        'assigned_at',
        'unassigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'unassigned_at' => 'date',
        'made_year' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
