<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCriteria extends Model
{
    protected $table = 'attendance_criteria';

    protected $fillable = [
        'class_id',
        'subject_id',
        'criteria_type',
        'min_attendance_percent',
        'max_allowed_absences',
        'academic_year',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'min_attendance_percent' => 'integer',
        'max_allowed_absences' => 'integer',
    ];

    // Relationships
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeForClass($query, $classId, $academicYear)
    {
        return $query->where('class_id', $classId)
                     ->where('academic_year', $academicYear);
    }

    public function scopeForAcademicYear($query, $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }
}
