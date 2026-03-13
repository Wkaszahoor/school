<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PBLStudentGroup extends Model
{
    use SoftDeletes;

    protected $table = 'pbl_student_groups';

    protected $fillable = [
        'assignment_id',
        'group_name',
        'group_leader_id',
        'project_proposal',
        'status',
        'member_count',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
    ];

    public function assignment()
    {
        return $this->belongsTo(PBLAssignment::class, 'assignment_id');
    }

    public function groupLeader()
    {
        return $this->belongsTo(User::class, 'group_leader_id');
    }

    public function members()
    {
        return $this->hasMany(PBLGroupMember::class, 'group_id');
    }

    public function activeMembers()
    {
        return $this->members()->where('participation_status', 'active');
    }

    public function submission()
    {
        return $this->hasOne(PBLSubmission::class, 'group_id');
    }

    public function evaluations()
    {
        return $this->hasMany(PBLEvaluation::class, 'group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orWhere('status', 'submitted');
    }

    public function scopeByAssignment($query, $assignmentId)
    {
        return $query->where('assignment_id', $assignmentId);
    }

    public function getActiveMemberCount()
    {
        return $this->activeMembers()->count();
    }

    public function getAverageContribution()
    {
        $members = $this->activeMembers()->get();
        if ($members->isEmpty()) {
            return 0;
        }
        return $members->avg('contribution_percentage');
    }
}
