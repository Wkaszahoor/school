<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProficiencyTestAttempt extends Model
{
    protected $fillable = [
        'assignment_id', 'user_id', 'started_at', 'completed_at',
        'total_score', 'max_score', 'percentage', 'band_score',
        'status', 'section_scores',
    ];

    protected $casts = [
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'section_scores' => 'array',
    ];

    public function assignment()
    {
        return $this->belongsTo(ProficiencyTestAssignment::class, 'assignment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function answers()
    {
        return $this->hasMany(ProficiencyTestAnswer::class, 'attempt_id');
    }

    public static function calculateBandScore(float $percentage): float
    {
        return match(true) {
            $percentage >= 93 => 9.0,
            $percentage >= 86 => 8.0,
            $percentage >= 76 => 7.0,
            $percentage >= 66 => 6.0,
            $percentage >= 56 => 5.0,
            $percentage >= 41 => 4.0,
            $percentage >= 26 => 3.0,
            $percentage >= 11 => 2.0,
            default           => 1.0,
        };
    }

    public function getBandLabelAttribute(): string
    {
        return match(true) {
            $this->band_score >= 9  => 'Expert',
            $this->band_score >= 8  => 'Very Good',
            $this->band_score >= 7  => 'Good',
            $this->band_score >= 6  => 'Competent',
            $this->band_score >= 5  => 'Modest',
            $this->band_score >= 4  => 'Limited',
            $this->band_score >= 3  => 'Extremely Limited',
            $this->band_score >= 2  => 'Intermittent',
            default                 => 'Non-User',
        };
    }
}
