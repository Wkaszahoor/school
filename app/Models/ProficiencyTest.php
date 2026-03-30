<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProficiencyTest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'duration_minutes', 'passing_score',
        'total_marks', 'status', 'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections()
    {
        return $this->hasMany(ProficiencyTestSection::class, 'test_id')->orderBy('order');
    }

    public function assignments()
    {
        return $this->hasMany(ProficiencyTestAssignment::class, 'test_id');
    }

    public function recalculateTotalMarks(): void
    {
        $this->total_marks = $this->sections()->sum('marks');
        $this->save();
    }
}
