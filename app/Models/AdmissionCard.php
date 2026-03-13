<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionCard extends Model
{
    protected $table = 'admission_cards';

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year',
        'exam_period',
        'attendance_eligible',
        'attendance_percent',
        'status',
        'issued_date',
        'approved_by',
        'generated_by',
        'pdf_path',
    ];

    protected $casts = [
        'attendance_eligible' => 'boolean',
        'attendance_percent' => 'decimal:2',
        'issued_date' => 'date',
    ];

    // Relationships
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForExamPeriod($query, $examPeriod, $academicYear)
    {
        return $query->where('exam_period', $examPeriod)
                     ->where('academic_year', $academicYear);
    }

    public function scopeEligible($query)
    {
        return $query->where('attendance_eligible', true);
    }

    public function scopeIssued($query)
    {
        return $query->where('status', 'issued');
    }
}
