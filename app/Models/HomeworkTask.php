<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeworkTask extends Model
{
    protected $fillable = [
        'teacher_id', 'class_id', 'subject_id',
        'homework_date', 'title', 'description', 'due_date',
    ];

    protected $casts = [
        'homework_date' => 'date',
        'due_date'      => 'date',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
