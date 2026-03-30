<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProficiencyTestAnswer extends Model
{
    protected $fillable = [
        'attempt_id', 'question_id', 'answer',
        'is_correct', 'marks_awarded', 'feedback',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function attempt()
    {
        return $this->belongsTo(ProficiencyTestAttempt::class, 'attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(ProficiencyTestQuestion::class, 'question_id');
    }
}
