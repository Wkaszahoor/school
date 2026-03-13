<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassStreamSubjectGroup extends Model
{
    protected $table = 'class_stream_subject_groups';

    protected $fillable = ['class_id', 'stream_key', 'group_id'];

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function group()
    {
        return $this->belongsTo(SubjectGroup::class, 'group_id');
    }
}
