<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PBLGroupMember extends Model
{
    protected $table = 'pbl_group_members';

    public $timestamps = true;

    protected $fillable = [
        'group_id',
        'student_id',
        'role',
        'participation_status',
        'contribution_percentage',
        'contribution_notes',
        'joined_at',
        'left_at',
        'metadata',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function group()
    {
        return $this->belongsTo(PBLStudentGroup::class, 'group_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeActive($query)
    {
        return $query->where('participation_status', 'active');
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isActive()
    {
        return $this->participation_status === 'active';
    }

    public function getStatusColor()
    {
        return match($this->participation_status) {
            'active' => 'green',
            'inactive' => 'yellow',
            'dropped' => 'red',
            'transferred' => 'blue',
            default => 'gray',
        };
    }
}
