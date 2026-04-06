<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoScholasticGrade extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'academic_year', 'exam_type', 'term',
        'activity', 'term1_grade', 'term2_grade', 'entered_by',
    ];

    public function student() { return $this->belongsTo(Student::class); }
    public function enteredBy() { return $this->belongsTo(User::class, 'entered_by'); }
}
