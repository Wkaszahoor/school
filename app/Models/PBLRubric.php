<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PBLRubric extends Model
{
    use SoftDeletes;

    protected $table = 'pbl_rubrics';

    protected $fillable = [
        'rubric_name',
        'description',
        'created_by',
        'total_points',
        'rubric_type',
        'criteria',
        'is_template',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'json',
        'is_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evaluations()
    {
        return $this->hasMany(PBLEvaluation::class, 'rubric_id');
    }

    public function assignments()
    {
        return $this->hasMany(PBLAssignment::class, 'rubric_id');
    }

    public function scopeTemplate($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('rubric_type', $type);
    }

    public function getCriteriaCount()
    {
        return $this->criteria ? count($this->criteria) : 0;
    }
}
