<?php

namespace App\Providers;

use App\Models\Certification;
use App\Models\PBLAssignment;
use App\Models\TeachingResource;
use App\Models\TrainingCourse;
use App\Policies\CertificationPolicy;
use App\Policies\PBLAssignmentPolicy;
use App\Policies\TeachingResourcePolicy;
use App\Policies\TrainingCoursePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        TrainingCourse::class => TrainingCoursePolicy::class,
        PBLAssignment::class => PBLAssignmentPolicy::class,
        Certification::class => CertificationPolicy::class,
        TeachingResource::class => TeachingResourcePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
