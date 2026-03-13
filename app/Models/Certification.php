<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certification extends Model
{
    use SoftDeletes;

    protected $table = 'certifications';

    protected $fillable = [
        'teacher_id',
        'course_id',
        'certificate_number',
        'certificate_name',
        'issuing_organization',
        'issue_date',
        'expiry_date',
        'is_renewable',
        'certificate_file_path',
        'certification_level',
        'score',
        'description',
        'status',
        'revoked_at',
        'revocation_reason',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'revoked_at' => 'datetime',
        'is_renewable' => 'boolean',
        'metadata' => 'json',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function course()
    {
        return $this->belongsTo(TrainingCourse::class, 'course_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere('expiry_date', '<', now());
    }

    public function scopeRenewable($query)
    {
        return $query->where('is_renewable', true)
            ->where('status', 'active');
    }

    public function scopeExpiringWithinDays($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function isExpired()
    {
        return $this->status === 'expired' || ($this->expiry_date && $this->expiry_date < now()->toDateString());
    }

    public function isExpiringWithinDays($days = 30)
    {
        if (!$this->expiry_date || $this->status !== 'active') {
            return false;
        }
        return $this->expiry_date <= now()->addDays($days)->toDateString()
            && $this->expiry_date > now()->toDateString();
    }

    public function daysUntilExpiry()
    {
        if (!$this->expiry_date) {
            return null;
        }
        return now()->toDateTimeString() < $this->expiry_date->toDateTimeString()
            ? now()->diffInDays($this->expiry_date)
            : -now()->diffInDays($this->expiry_date);
    }
}
