<?php

return [
    /*
    |--------------------------------------------------------------------------
    | School Identity
    |--------------------------------------------------------------------------
    */
    'name' => env('SCHOOL_NAME', 'KORT School'),
    'url'  => env('SCHOOL_URL', 'https://kort.org.uk'),

    /*
    |--------------------------------------------------------------------------
    | Admission Number
    |--------------------------------------------------------------------------
    */
    'admission_prefix' => 'SCH',

    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */
    'attendance_alert_threshold' => 75,

    /*
    |--------------------------------------------------------------------------
    | Academic Streams (Classes 9-12)
    |--------------------------------------------------------------------------
    */
    'streams' => [
        'pre_medical'      => 'Pre-Medical',
        'pre_engineering'  => 'Pre-Engineering',
        'computer_science' => 'Computer Science',
        'arts'             => 'Arts',
        'general'          => 'General',
    ],

    /*
    |--------------------------------------------------------------------------
    | GPA Scale
    |--------------------------------------------------------------------------
    | percentage_min => [grade, gpa_point]
    */
    'gpa_scale' => [
        90 => ['A+', 4.0],
        80 => ['A',  4.0],
        70 => ['B',  3.0],
        60 => ['C',  2.0],
        50 => ['D',  1.0],
        33 => ['E',  0.5],
        0  => ['F',  0.0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Exam Types
    |--------------------------------------------------------------------------
    */
    'exam_types' => [
        'midterm'  => 'Mid-Term',
        'final'    => 'Final',
        'quarterly' => 'Quarterly',
        'annual'   => 'Annual',
    ],

    /*
    |--------------------------------------------------------------------------
    | Terms
    |--------------------------------------------------------------------------
    */
    'terms' => [
        'term1' => 'Term 1',
        'term2' => 'Term 2',
        'term3' => 'Term 3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'admin',
        'principal',
        'teacher',
        'receptionist',
        'principal_helper',
        'inventory_manager',
        'doctor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Dashboard mapping
    |--------------------------------------------------------------------------
    */
    'role_dashboards' => [
        'admin'             => '/admin/dashboard',
        'principal'         => '/principal/dashboard',
        'teacher'           => '/teacher/dashboard',
        'receptionist'      => '/receptionist/dashboard',
        'principal_helper'  => '/helper/dashboard',
        'inventory_manager' => '/inventory/dashboard',
        'doctor'            => '/doctor/dashboard',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Matrix
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'class_year' => [
            'view'   => ['admin', 'principal'],
            'create' => ['admin'],
            'edit'   => ['admin'],
            'delete' => ['admin'],
        ],
        'students' => [
            'view'         => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'create'       => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'edit'         => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'delete'       => ['admin', 'principal'],
            'import'       => ['admin', 'receptionist'],
            'export'       => ['admin', 'principal', 'receptionist', 'principal_helper'],
            'assign_group' => ['admin', 'principal', 'principal_helper'],
            'populate'     => ['admin'],
        ],
        'teachers' => [
            'view'   => ['admin', 'principal'],
            'create' => ['admin', 'principal'],
            'edit'   => ['admin'],
            'delete' => ['admin'],
            'assign' => ['admin', 'principal'],
        ],
        'subjects' => [
            'view'   => ['admin', 'principal', 'principal_helper'],
            'create' => ['admin', 'principal'],
            'edit'   => ['admin', 'principal'],
            'delete' => ['admin', 'principal'],
            'seed'   => ['admin', 'principal'],
        ],
        'attendance_reports' => [
            'view'    => ['admin', 'principal', 'teacher'],
            'create'  => ['teacher'],
            'edit'    => ['teacher', 'admin'],
            'approve' => ['principal'],
            'export'  => ['admin', 'principal'],
        ],
        'results_reports' => [
            'view'    => ['admin', 'principal', 'teacher'],
            'create'  => ['teacher'],
            'edit'    => ['teacher', 'admin'],
            'approve' => ['principal'],
            'export'  => ['admin', 'principal', 'teacher'],
        ],
        'staff_users' => [
            'view'   => ['admin'],
            'create' => ['admin'],
            'edit'   => ['admin'],
            'delete' => ['admin'],
        ],
        'admin_dashboard' => [
            'view' => ['admin', 'principal'],
        ],
        'medical_records' => [
            'view'    => ['admin', 'principal', 'doctor'],
            'create'  => ['admin', 'principal'],
            'edit'    => ['admin', 'doctor'],
            'approve' => ['principal'],
        ],
        'leave_requests' => [
            'view'    => ['admin', 'principal', 'teacher'],
            'create'  => ['admin', 'principal', 'teacher'],
            'approve' => ['principal'],
        ],
        'teacher_profile' => [
            'view'            => ['teacher'],
            'edit'            => ['teacher'],
            'change_password' => ['teacher'],
        ],
        'teacher_workspace' => [
            'view'   => ['teacher'],
            'create' => ['teacher'],
            'edit'   => ['teacher'],
            'export' => ['teacher'],
        ],
        'inbox_messages' => [
            'view'   => ['principal', 'teacher'],
            'create' => ['principal', 'teacher'],
            'edit'   => ['principal', 'teacher'],
        ],
        'doctor_records' => [
            'view'    => ['doctor', 'admin', 'principal'],
            'approve' => ['principal'],
            'examine' => ['doctor'],
        ],
        'inventory' => [
            'view'   => ['inventory_manager', 'admin', 'principal'],
            'create' => ['inventory_manager', 'admin'],
            'edit'   => ['inventory_manager', 'admin'],
            'delete' => ['inventory_manager', 'admin'],
            'export' => ['inventory_manager', 'admin', 'principal'],
        ],
        'lesson_plans' => [
            'view'    => ['admin', 'principal', 'teacher'],
            'create'  => ['teacher'],
            'approve' => ['principal', 'admin'],
            'edit'    => ['teacher', 'principal', 'admin'],
            'export'  => ['principal', 'admin'],
        ],
        'discipline' => [
            'view'    => ['admin', 'principal', 'teacher'],
            'create'  => ['admin', 'principal', 'teacher'],
            'edit'    => ['admin', 'principal', 'teacher'],
            'delete'  => ['admin', 'principal'],
            'meeting' => ['admin', 'principal'],
        ],
        'attendance_criteria' => [
            'view'   => ['admin', 'principal', 'teacher'],
            'create' => ['admin', 'principal', 'teacher'],
            'edit'   => ['admin', 'principal', 'teacher'],
            'delete' => ['admin', 'principal'],
        ],
        'datesheets' => [
            'view'   => ['admin', 'principal'],
            'create' => ['admin', 'principal'],
            'edit'   => ['admin', 'principal'],
            'delete' => ['admin', 'principal'],
        ],
        'admission_cards' => [
            'view'     => ['admin', 'principal'],
            'generate' => ['admin', 'principal'],
            'download' => ['admin', 'principal'],
        ],
        'training_courses' => [
            'view'         => ['admin', 'principal', 'teacher'],
            'create'       => ['admin', 'principal'],
            'edit'         => ['admin', 'principal'],
            'delete'       => ['admin', 'principal'],
            'enroll'       => ['admin', 'principal', 'teacher'],
            'download'     => ['admin', 'principal', 'teacher'],
            'view_progress'=> ['admin', 'principal', 'teacher'],
        ],
        'pbl_assignments' => [
            'view'         => ['admin', 'principal', 'teacher'],
            'create'       => ['admin', 'principal', 'teacher'],
            'edit'         => ['admin', 'principal', 'teacher'],
            'delete'       => ['admin', 'principal'],
            'evaluate'     => ['teacher', 'principal', 'admin'],
            'create_group' => ['teacher', 'principal', 'admin'],
            'submit'       => ['teacher'],
        ],
        'certifications' => [
            'view'         => ['admin', 'principal', 'teacher'],
            'download'     => ['admin', 'principal', 'teacher'],
            'revoke'       => ['admin', 'principal'],
            'generate'     => ['admin', 'principal'],
            'view_report'  => ['admin', 'principal'],
        ],
        'teaching_resources' => [
            'view'         => ['admin', 'principal', 'teacher'],
            'create'       => ['teacher', 'principal', 'admin'],
            'edit'         => ['teacher', 'principal', 'admin'],
            'delete'       => ['teacher', 'principal', 'admin'],
            'download'     => ['admin', 'principal', 'teacher'],
            'upload'       => ['teacher', 'principal', 'admin'],
            'search'       => ['admin', 'principal', 'teacher'],
        ],
    ],
];
