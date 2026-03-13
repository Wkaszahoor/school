<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DisciplineRecord extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'category', 'severity', 'incident_date',
        'title', 'description', 'status', 'recorded_by', 'report_to_principal',
    ];

    protected $casts = [
        'incident_date'         => 'date',
        'report_to_principal'   => 'boolean',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function actions()
    {
        return $this->hasMany(DisciplineAction::class);
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'achievement'  => 'green',
            'warning'      => 'yellow',
            'suspension'   => 'red',
            'other'        => 'gray',
            default        => 'gray',
        };
    }
}
