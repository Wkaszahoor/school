<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectSelection extends Model
{
    protected $fillable = [
        'student_id',
        'subject_id',
        'subject_group_id',
        'subject_type',
    ];

    protected $casts = [
        'selected_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function subjectGroup(): BelongsTo
    {
        return $this->belongsTo(SubjectGroup::class);
    }
}
