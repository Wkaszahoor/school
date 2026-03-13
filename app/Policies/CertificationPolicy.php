<?php

namespace App\Policies;

use App\Models\Certification;
use App\Models\User;

class CertificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('certifications', 'view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Certification $certification): bool
    {
        // User can view their own or if they have permission
        return $user->hasPermission('certifications', 'view') &&
               ($user->id === $certification->teacher_id ||
                $user->role === 'principal' ||
                $user->role === 'admin');
    }

    /**
     * Determine whether the user can download the certificate.
     */
    public function download(User $user, Certification $certification): bool
    {
        // User can download their own or if they have permission
        return $user->hasPermission('certifications', 'download') &&
               ($user->id === $certification->teacher_id ||
                $user->role === 'principal' ||
                $user->role === 'admin');
    }

    /**
     * Determine whether the user can revoke the certificate.
     */
    public function revoke(User $user, Certification $certification): bool
    {
        return $user->hasPermission('certifications', 'revoke');
    }

    /**
     * Determine whether the user can generate certificates.
     */
    public function generate(User $user): bool
    {
        return $user->hasPermission('certifications', 'generate');
    }

    /**
     * Determine whether the user can view certification reports.
     */
    public function viewReport(User $user): bool
    {
        return $user->hasPermission('certifications', 'view_report');
    }
}
