<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PBLAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'pbl_assignments';

    protected $fillable = [
        'project_title',
        'description',
        'teacher_id',
        'class_id',
        'subject_id',
        'rubric_id',
        'project_type',
        'learning_objectives',
        'requirements',
        'group_size',
        'start_date',
        'due_date',
        'presentation_date',
        'total_marks',
        'status',
        'resources',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'presentation_date' => 'date',
        'resources' => 'json',
        'metadata' => 'json',
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
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function rubric()
    {
        return $this->belongsTo(PBLRubric::class, 'rubric_id');
    }

    public function groups()
    {
        return $this->hasMany(PBLStudentGroup::class, 'assignment_id');
    }

    public function submissions()
    {
        return $this->hasMany(PBLSubmission::class, 'assignment_id');
    }

    public function evaluations()
    {
        return $this->hasMany(PBLEvaluation::class, 'assignment_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orWhere('status', 'in-progress');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function getGroupCount()
    {
        return $this->groups()->count();
    }

    public function getSubmissionCount()
    {
        return $this->submissions()->count();
    }

    public function isOverdue()
    {
        return $this->due_date < now()->toDateString() && $this->status !== 'completed';
    }
}
