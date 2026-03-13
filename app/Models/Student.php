<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'admission_no', 'full_name', 'student_cnic', 'father_name', 'father_cnic', 'mother_name', 'mother_cnic',
        'guardian_name', 'guardian_relation', 'guardian_phone', 'guardian_cnic', 'guardian_address', 'dob', 'gender',
        'class_id', 'group_stream', 'semester', 'blood_group', 'photo', 'join_date_kort', 'is_orphan', 'trust_notes',
        'previous_school', 'phone', 'email', 'is_active', 'subject_group_id', 'stream',
        'favorite_color', 'favorite_food', 'favorite_subject', 'ambition', 'reason_left_kort', 'leaving_date',
    ];

    protected $casts = [
        'dob'            => 'date',
        'join_date_kort' => 'date',
        'leaving_date'   => 'date',
        'is_orphan'      => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subjectGroup()
    {
        return $this->belongsTo(SubjectGroup::class);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function behaviourRecords()
    {
        return $this->hasMany(BehaviourRecord::class);
    }

    public function disciplineRecords()
    {
        return $this->hasMany(DisciplineRecord::class);
    }

    public function sickRecords()
    {
        return $this->hasMany(SickRecord::class);
    }

    public function documents()
    {
        return $this->hasMany(StudentDocument::class);
    }

    // PBL Relationships
    public function pblGroupMemberships()
    {
        return $this->hasMany(PBLGroupMember::class, 'student_id');
    }

    public function pblGroupsAsLeader()
    {
        return $this->hasMany(PBLStudentGroup::class, 'group_leader_id');
    }

    public function subjectSelections()
    {
        return $this->hasMany(StudentSubjectSelection::class);
    }

    public function getAgeAttribute(): int
    {
        return $this->dob ? $this->dob->age : 0;
    }
}
