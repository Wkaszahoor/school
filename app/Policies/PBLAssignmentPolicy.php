<?php

namespace App\Policies;

use App\Models\PBLAssignment;
use App\Models\User;

class PBLAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('pbl_assignments', 'view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PBLAssignment $assignment): bool
    {
        return $user->hasPermission('pbl_assignments', 'view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('pbl_assignments', 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PBLAssignment $assignment): bool
    {
        // Only creator or admin/principal can edit
        if ($user->hasPermission('pbl_assignments', 'edit')) {
            return $user->id === $assignment->teacher_id ||
                   $user->id === $assignment->created_by_id ||
                   $user->role === 'admin' ||
                   $user->role === 'principal';
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PBLAssignment $assignment): bool
    {
        return $user->hasPermission('pbl_assignments', 'delete');
    }

    /**
     * Determine whether the user can evaluate submissions.
     */
    public function evaluate(User $user, PBLAssignment $assignment): bool
    {
        // Teacher can evaluate their own assignments, principal/admin can evaluate any
        if ($user->hasPermission('pbl_assignments', 'evaluate')) {
            return $user->id === $assignment->teacher_id ||
                   $user->role === 'principal' ||
                   $user->role === 'admin';
        }
        return false;
    }

    /**
     * Determine whether the user can create groups.
     */
    public function createGroup(User $user, PBLAssignment $assignment): bool
    {
        // Only the teacher who created the assignment can create groups
        if ($user->hasPermission('pbl_assignments', 'create_group')) {
            return $user->id === $assignment->teacher_id ||
                   $user->role === 'principal' ||
                   $user->role === 'admin';
        }
        return false;
    }

    /**
     * Determine whether the user can submit for a group.
     */
    public function submit(User $user, PBLAssignment $assignment): bool
    {
        return $user->hasPermission('pbl_assignments', 'submit') && $user->role === 'teacher';
    }
}
