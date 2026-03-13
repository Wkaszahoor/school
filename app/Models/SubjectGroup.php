<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubjectGroup extends Model
{
    protected $fillable = ['group_name', 'group_slug', 'stream', 'description', 'is_active', 'min_select', 'max_select', 'is_optional_group'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_optional_group' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->group_slug && $model->group_name) {
                $model->group_slug = Str::slug($model->group_name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('group_name') && !$model->isDirty('group_slug')) {
                $model->group_slug = Str::slug($model->group_name);
            }
        });
    }

    public function getNameAttribute()
    {
        return $this->group_name;
    }

    public function setNameAttribute($value)
    {
        $this->attributes['group_name'] = $value;
        if (!isset($this->attributes['group_slug']) || !$this->attributes['group_slug']) {
            $this->attributes['group_slug'] = Str::slug($value);
        }
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'subject_group_subjects', 'group_id', 'subject_id')
                    ->withPivot('subject_type')
                    ->withTimestamps();
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'subject_group_id');
    }

    public function studentSelections()
    {
        return $this->hasMany(StudentSubjectSelection::class, 'subject_group_id');
    }

    public function teachers()
    {
        return $this->hasMany(SubjectGroupTeacher::class, 'subject_group_id');
    }
}
