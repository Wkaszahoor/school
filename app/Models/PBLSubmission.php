<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PBLSubmission extends Model
{
    use SoftDeletes;

    protected $table = 'pbl_submissions';

    protected $fillable = [
        'assignment_id',
        'group_id',
        'submission_type',
        'description',
        'file_path',
        'file_url',
        'mime_type',
        'file_size',
        'submitted_at',
        'is_late',
        'days_late',
        'plagiarism_status',
        'plagiarism_score',
        'metadata',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'plagiarism_score' => 'float',
        'metadata' => 'json',
    ];

    public function assignment()
    {
        return $this->belongsTo(PBLAssignment::class, 'assignment_id');
    }

    public function group()
    {
        return $this->belongsTo(PBLStudentGroup::class, 'group_id');
    }

    public function evaluation()
    {
        return $this->hasOne(PBLEvaluation::class, 'submission_id');
    }

    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    public function scopePlagiarismFlagged($query)
    {
        return $query->where('plagiarism_status', 'flagged');
    }

    public function isOnTime()
    {
        return !$this->is_late;
    }

    public function getPlagiarismLevel()
    {
        if (!$this->plagiarism_score) {
            return 'pending';
        }
        if ($this->plagiarism_score < 10) return 'clear';
        if ($this->plagiarism_score < 25) return 'acceptable';
        if ($this->plagiarism_score < 50) return 'concern';
        return 'violation';
    }
}
