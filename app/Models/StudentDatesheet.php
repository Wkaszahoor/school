<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDatesheet extends Model
{
    protected $table = 'student_datesheets';

    protected $fillable = [
        'class_name',
        'subject_name',
        'exam_date',
        'exam_time',
        'room_no',
        'total_marks',
        'exam_period',
        'academic_year',
    ];

    protected $casts = [
        'exam_date' => 'date',
        'total_marks' => 'integer',
    ];

    // Scope to filter by exam period and academic year
    public function scopeForExamPeriod($query, $examPeriod, $academicYear)
    {
        return $query->where('exam_period', $examPeriod)
                     ->where('academic_year', $academicYear);
    }

    // Scope to filter by class name
    public function scopeForClass($query, $className)
    {
        return $query->where('class_name', $className);
    }
}
