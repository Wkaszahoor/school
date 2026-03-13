<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BehaviourRecord extends Model
{
    protected $fillable = [
        'student_id', 'class_id', 'teacher_id', 'record_date',
        'behaviour_type', 'title', 'description',
    ];

    protected $casts = ['record_date' => 'date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
