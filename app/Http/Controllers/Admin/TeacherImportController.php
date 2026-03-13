<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{User, TeacherProfile, TeacherAssignment, Subject, SchoolClass, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class TeacherImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/TeacherImport/Index');
    }

    public function import(Request $request)
    {
        $request->validate([
            'teachers' => 'required|array',
            'teachers.*.name' => 'required|string',
            'teachers.*.teaching_subject' => 'required|string',
            'teachers.*.teaching_class' => 'required|string',
            'teachers.*.is_class_teacher' => 'boolean',
        ]);

        $currentAcademicYear = config('school.current_academic_year', '2025-26');
        $results = [
            'created_users' => 0,
            'updated_users' => 0,
            'created_subjects' => 0,
            'created_classes' => 0,
            'assignments_created' => 0,
            'failed' => 0,
            'errors' => [],
            'details' => [],
        ];

        foreach ($request->teachers as $index => $teacherData) {
            try {
                $name = trim($teacherData['name']);
                $subject = trim($teacherData['teaching_subject']);
                $class = trim($teacherData['teaching_class']);
                $isClassTeacher = $teacherData['is_class_teacher'] ?? false;

                if (empty($name) || empty($subject) || empty($class)) {
                    throw new \Exception("Missing required fields");
                }

                // Create or update teacher user with sanitized email
                // Remove special characters, keep only alphanumeric, dots, hyphens, underscores
                $emailBase = preg_replace('/[^a-zA-Z0-9\s\-\']/i', '', $name); // Remove invalid chars
                $emailBase = preg_replace('/[\s\']+/', '.', $emailBase); // Replace spaces and apostrophes with dots
                $emailBase = preg_replace('/\.+/', '.', $emailBase); // Remove multiple consecutive dots
                $emailBase = strtolower(trim($emailBase, '.')); // Remove leading/trailing dots
                $email = $emailBase . '@kort.org.uk';

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception("Invalid email generated from name: $email");
                }

                $password = 'teacher123';

                $user = User::where('email', $email)->first();
                $isNewUser = false;

                if (!$user) {
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make($password),
                        'role' => 'teacher',
                    ]);
                    $results['created_users']++;
                    $isNewUser = true;
                } else {
                    $results['updated_users']++;
                }

                // Create or update teacher profile
                $profile = TeacherProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'phone' => null,
                        'qualification' => 'Bachelor',
                        'specialisation' => $subject,
                        'date_joined' => now(),
                        'is_active' => true,
                    ]
                );

                // Link the profile back to the user
                if (!$user->teacher_profile_id) {
                    $user->update(['teacher_profile_id' => $profile->id]);
                }

                // Get or create subject using firstOrCreate to avoid duplicates
                if (empty($subject)) {
                    throw new \Exception("Subject name is empty");
                }

                $subjectCode = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $subject), 0, 3) ?: 'XXX');

                // Ensure subject code is not empty
                if (empty($subjectCode) || $subjectCode === 'XXX') {
                    $subjectCode = 'SUB'; // Fallback code
                }

                $subjectModel = Subject::firstOrCreate(
                    ['subject_code' => $subjectCode],
                    [
                        'subject_name' => $subject,
                        'is_active' => true,
                    ]
                );

                if (!$subjectModel || !$subjectModel->id) {
                    throw new \Exception("Failed to create/retrieve subject: $subject");
                }

                if ($subjectModel->wasRecentlyCreated) {
                    $results['created_subjects']++;
                }

                // Get or create class using firstOrCreate to avoid duplicates
                // Extract just the class part if section is included (e.g., "Class 9A" -> "Class 9", "A")
                if (empty($class)) {
                    throw new \Exception("Class name is empty");
                }

                $classModel = SchoolClass::firstOrCreate(
                    ['class' => $class, 'academic_year' => $currentAcademicYear],
                    [
                        'section' => 'A', // Default section if not specified
                        'is_active' => true,
                    ]
                );

                if (!$classModel || !$classModel->id) {
                    throw new \Exception("Failed to create/retrieve class: $class");
                }

                if ($classModel->wasRecentlyCreated) {
                    $results['created_classes']++;
                }

                // Determine assignment type
                $assignmentType = $isClassTeacher ? 'class_teacher' : 'subject_teacher';

                // Validate all required IDs before creating assignment
                if (!$user->id || !$classModel->id || !$subjectModel->id) {
                    throw new \Exception("Invalid IDs - Teacher: {$user->id}, Class: {$classModel->id}, Subject: {$subjectModel->id}");
                }

                // Create teacher assignment
                $assignment = TeacherAssignment::updateOrCreate(
                    [
                        'teacher_id' => $user->id,
                        'class_id' => $classModel->id,
                        'subject_id' => $subjectModel->id,
                        'academic_year' => $currentAcademicYear,
                    ],
                    [
                        'assignment_type' => $assignmentType,
                    ]
                );

                if (!$assignment || !$assignment->id) {
                    throw new \Exception("Failed to create teacher assignment");
                }

                $results['assignments_created']++;

                // If class teacher, update class_teacher_id
                if ($isClassTeacher) {
                    $classModel->update(['class_teacher_id' => $user->id]);
                }

                // Audit log
                AuditLog::create([
                    'user_id' => auth()->id(),
                    'user_role' => auth()->user()->role,
                    'action' => 'import',
                    'resource' => 'teacher',
                    'resource_id' => $user->id,
                    'ip_address' => $request->ip(),
                ]);

                $results['details'][] = [
                    'name' => $name,
                    'email' => $email,
                    'subject' => $subject,
                    'class' => $class,
                    'role' => $isClassTeacher ? 'Class Teacher' : 'Subject Teacher',
                    'status' => $isNewUser ? 'Created' : 'Updated',
                    'password' => $password,
                ];

            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 1,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json($results);
    }
}
