<?php

namespace App\Policies;

use App\Models\TeachingResource;
use App\Models\User;

class TeachingResourcePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('teaching_resources', 'view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TeachingResource $resource): bool
    {
        // Can view own resources or published resources
        return $user->hasPermission('teaching_resources', 'view') &&
               ($user->id === $resource->uploaded_by_id ||
                $resource->status === 'published' ||
                $user->role === 'admin' ||
                $user->role === 'principal');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('teaching_resources', 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TeachingResource $resource): bool
    {
        // Only creator or admin/principal can edit
        if ($user->hasPermission('teaching_resources', 'edit')) {
            return $user->id === $resource->uploaded_by_id ||
                   $user->role === 'admin' ||
                   $user->role === 'principal';
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TeachingResource $resource): bool
    {
        // Only creator or admin/principal can delete
        if ($user->hasPermission('teaching_resources', 'delete')) {
            return $user->id === $resource->uploaded_by_id ||
                   $user->role === 'admin' ||
                   $user->role === 'principal';
        }
        return false;
    }

    /**
     * Determine whether the user can download the resource.
     */
    public function download(User $user, TeachingResource $resource): bool
    {
        // Can download own or published resources
        return $user->hasPermission('teaching_resources', 'download') &&
               ($user->id === $resource->uploaded_by_id ||
                $resource->status === 'published' ||
                $user->role === 'admin' ||
                $user->role === 'principal');
    }

    /**
     * Determine whether the user can upload resources.
     */
    public function upload(User $user): bool
    {
        return $user->hasPermission('teaching_resources', 'upload');
    }

    /**
     * Determine whether the user can search resources.
     */
    public function search(User $user): bool
    {
        return $user->hasPermission('teaching_resources', 'search');
    }
}
