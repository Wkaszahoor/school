<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectGroupTeacher extends Model
{
    protected $fillable = ['subject_group_id', 'user_id', 'role', 'subject_id'];
    protected $casts = ['created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function subjectGroup()
    {
        return $this->belongsTo(SubjectGroup::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->nullable();
    }
}
