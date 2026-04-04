<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicYear extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function schoolClasses()
    {
        return $this->hasMany(SchoolClass::class, 'academic_year', 'year');
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, SchoolClass::class, 'academic_year', 'class_id', 'year', 'id');
    }

    public function leaveSettings()
    {
        return $this->hasMany(LeaveSetting::class, 'academic_year', 'year');
    }

    public function academicCalendars()
    {
        return $this->hasMany(AcademicCalendar::class, 'academic_year', 'year');
    }

    public function getDisplayNameAttribute()
    {
        return "Academic Year {$this->year}";
    }

    public function getDurationAttribute()
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }
}
