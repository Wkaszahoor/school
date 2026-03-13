<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, AuditLog};
use Illuminate\Http\Request;

class BulkStudentImportController extends Controller
{
    public function importFromJson(Request $request)
    {
        try {
            // Read the import_data.json file
            $filePath = base_path('import_data.json');

            if (!file_exists($filePath)) {
                return back()->with('error', 'Import data file not found. Run the Excel import script first.');
            }

            $jsonData = json_decode(file_get_contents($filePath), true);

            if (!is_array($jsonData) || empty($jsonData)) {
                return back()->with('error', 'Invalid or empty import data');
            }

            // Build class mapping
            $classes = SchoolClass::where('is_active', true)->get();
            $classMap = [];
            foreach ($classes as $class) {
                $classMap[$class->class] = $class->id;
            }

            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($jsonData as $index => $studentData) {
                try {
                    // Get class_id from class_name
                    $className = $studentData['class_name'] ?? null;
                    if (!$className || !isset($classMap[$className])) {
                        $failed++;
                        $errors[] = "Student " . ($index + 1) . ": Class '{$className}' not found";
                        continue;
                    }

                    // Prepare student data
                    $data = [
                        'full_name' => $studentData['full_name'] ?? '',
                        'admission_no' => $studentData['admission_no'] ?? 'ADM' . date('Y') . rand(10000, 99999),
                        'student_cnic' => $studentData['student_cnic'] ?? '',
                        'father_name' => $studentData['father_name'] ?? '',
                        'father_cnic' => $studentData['father_cnic'] ?? '',
                        'mother_name' => $studentData['mother_name'] ?? '',
                        'mother_cnic' => $studentData['mother_cnic'] ?? '',
                        'guardian_name' => $studentData['guardian_name'] ?? '',
                        'guardian_cnic' => $studentData['guardian_cnic'] ?? '',
                        'guardian_address' => $studentData['guardian_address'] ?? '',
                        'phone' => $studentData['phone'] ?? '',
                        'dob' => $studentData['dob'] ?? null,
                        'favorite_color' => $studentData['favorite_color'] ?? '',
                        'favorite_food' => $studentData['favorite_food'] ?? '',
                        'favorite_subject' => $studentData['favorite_subject'] ?? '',
                        'ambition' => $studentData['ambition'] ?? '',
                        'gender' => $studentData['gender'] ?? 'male',
                        'is_active' => $studentData['is_active'] ?? true,
                        'group_stream' => $studentData['group_stream'] ?? '',
                        'semester' => $studentData['semester'] ?? '',
                        'join_date_kort' => $studentData['join_date_kort'] ?? null,
                        'class_id' => $classMap[$className],
                    ];

                    // Check if student already exists
                    if (Student::where('admission_no', $data['admission_no'])->exists()) {
                        $failed++;
                        $errors[] = "Student {$data['full_name']} ({$data['admission_no']}): Already exists";
                        continue;
                    }

                    // Create student
                    Student::create($data);
                    AuditLog::log('bulk_import', 'Student', null, null, $data);
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Student " . ($index + 1) . ": " . $e->getMessage();
                }
            }

            // Clean up
            unlink($filePath);

            $message = "Imported {$imported} students successfully";
            if ($failed > 0) {
                $message .= ". {$failed} failed.";
            }

            return back()->with([
                'success' => $message,
                'warning' => !empty($errors) ? 'First error: ' . $errors[0] : null,
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }
}
