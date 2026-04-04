<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProficiencyTestQuestion extends Model
{
    protected $fillable = [
        'section_id', 'question_text', 'question_type',
        'options', 'correct_answer', 'marks', 'order',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function section()
    {
        return $this->belongsTo(ProficiencyTestSection::class, 'section_id');
    }

    public function answers()
    {
        return $this->hasMany(ProficiencyTestAnswer::class, 'question_id');
    }
}
