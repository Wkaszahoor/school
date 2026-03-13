<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['subject_name', 'subject_code', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['name', 'code'];

    // Attribute aliases for backward compatibility with code that expects 'name' and 'code'
    public function getNameAttribute(): ?string
    {
        return $this->attributes['subject_name'] ?? null;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['subject_code'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['subject_name'] = $value;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['subject_code'] = $value;
    }

    public function classes()
    {
        return $this->belongsToMany(SchoolClass::class, 'class_subjects');
    }

    public function subjectGroups()
    {
        return $this->belongsToMany(SubjectGroup::class, 'subject_group_subjects');
    }
}
