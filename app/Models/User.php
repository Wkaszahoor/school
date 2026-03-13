<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
        'avatar', 'teacher_profile_id', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'is_active'         => 'boolean',
        ];
    }

    public function setPasswordAttribute($value)
    {
        if (is_null($value)) {
            return;
        }
        // Only hash if the value is not already hashed
        if (!str_starts_with($value, '$2y$')) {
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    public function can($ability, $arguments = []): bool
    {
        if ($this->role === 'admin') return true;
        $permissions = config('school.permissions');
        if (isset($permissions[$ability])) {
            // ability is a resource — check any action
            foreach ($permissions[$ability] as $roles) {
                if (in_array($this->role, $roles)) return true;
            }
            return false;
        }
        // Check resource.action format
        if (str_contains($ability, '.')) {
            [$resource, $action] = explode('.', $ability, 2);
            return isset($permissions[$resource][$action]) &&
                   in_array($this->role, $permissions[$resource][$action]);
        }
        return false;
    }

    public function hasPermission(string $resource, string $action): bool
    {
        if ($this->role === 'admin') return true;
        $permissions = config('school.permissions');
        return isset($permissions[$resource][$action]) &&
               in_array($this->role, $permissions[$resource][$action]);
    }

    public function dashboardRoute(): string
    {
        return match($this->role) {
            'admin'             => route('admin.dashboard'),
            'principal'         => route('principal.dashboard'),
            'teacher'           => route('teacher.dashboard'),
            'receptionist'      => route('receptionist.dashboard'),
            'principal_helper'  => route('helper.dashboard'),
            'inventory_manager' => route('inventory.dashboard'),
            'doctor'            => route('doctor.dashboard'),
            default             => route('login'),
        };
    }

    public function teacherProfile()
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    // Professional Development Relationships
    public function courseEnrollments()
    {
        return $this->hasMany(CourseEnrollment::class, 'teacher_id');
    }

    public function instructedCourses()
    {
        return $this->hasMany(TrainingCourse::class, 'instructor_id');
    }

    public function teachingResources()
    {
        return $this->hasMany(TeachingResource::class, 'created_by');
    }

    public function resourceDownloads()
    {
        return $this->hasMany(ResourceDownload::class, 'downloaded_by');
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class, 'teacher_id');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class, 'teacher_id');
    }

    public function pblAssignments()
    {
        return $this->hasMany(PBLAssignment::class, 'teacher_id');
    }

    public function devices()
    {
        return $this->hasMany(TeacherDevice::class, 'teacher_id');
    }

    public function getRoleColorAttribute(): string
    {
        return match($this->role) {
            'admin'             => 'red',
            'principal'         => 'purple',
            'teacher'           => 'blue',
            'receptionist'      => 'green',
            'principal_helper'  => 'teal',
            'inventory_manager' => 'orange',
            'doctor'            => 'pink',
            default             => 'gray',
        };
    }

    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'admin'             => 'Administrator',
            'principal'         => 'Principal',
            'teacher'           => 'Teacher',
            'receptionist'      => 'Receptionist',
            'principal_helper'  => 'Principal Helper',
            'inventory_manager' => 'Inventory Manager',
            'doctor'            => 'Doctor',
            default             => ucfirst($this->role),
        };
    }
}
