<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PBLEvaluation extends Model
{
    use SoftDeletes;

    protected $table = 'pbl_evaluations';

    protected $fillable = [
        'submission_id',
        'group_id',
        'evaluator_id',
        'rubric_id',
        'evaluation_type',
        'total_score',
        'total_marks',
        'percentage',
        'grade',
        'general_feedback',
        'criteria_scores',
        'strength_areas',
        'improvement_areas',
        'status',
        'evaluated_at',
    ];

    protected $casts = [
        'total_score' => 'float',
        'total_marks' => 'float',
        'percentage' => 'float',
        'criteria_scores' => 'json',
        'strength_areas' => 'json',
        'improvement_areas' => 'json',
        'evaluated_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(PBLSubmission::class, 'submission_id');
    }

    public function group()
    {
        return $this->belongsTo(PBLStudentGroup::class, 'group_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function rubric()
    {
        return $this->belongsTo(PBLRubric::class, 'rubric_id');
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    public function scopeByEvaluator($query, $evaluatorId)
    {
        return $query->where('evaluator_id', $evaluatorId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('evaluation_type', $type);
    }

    public function getGradeAttribute()
    {
        if (!$this->percentage) {
            return null;
        }

        $score = $this->percentage;
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    public function calculatePercentage()
    {
        if ($this->total_marks && $this->total_marks > 0) {
            return ($this->total_score / $this->total_marks) * 100;
        }
        return 0;
    }
}
