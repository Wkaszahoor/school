<?php

namespace App\Policies;

use App\Models\TrainingCourse;
use App\Models\User;

class TrainingCoursePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('training_courses', 'view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TrainingCourse $course): bool
    {
        return $user->hasPermission('training_courses', 'view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('training_courses', 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TrainingCourse $course): bool
    {
        // Only creator or admin/principal can edit
        if ($user->hasPermission('training_courses', 'edit')) {
            return $user->id === $course->created_by_id || $user->role === 'admin' || $user->role === 'principal';
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TrainingCourse $course): bool
    {
        return $user->hasPermission('training_courses', 'delete');
    }

    /**
     * Determine whether the user can download materials.
     */
    public function downloadMaterials(User $user, TrainingCourse $course): bool
    {
        return $user->hasPermission('training_courses', 'download');
    }

    /**
     * Determine whether the user can enroll in the course.
     */
    public function enroll(User $user, TrainingCourse $course): bool
    {
        return $user->hasPermission('training_courses', 'enroll');
    }

    /**
     * Determine whether the user can view their progress.
     */
    public function viewProgress(User $user): bool
    {
        return $user->hasPermission('training_courses', 'view_progress');
    }
}
