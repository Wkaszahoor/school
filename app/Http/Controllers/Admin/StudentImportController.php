<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentImportController extends Controller
{
    public function index()
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        return Inertia::render('Admin/Students/Import', compact('classes'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'students' => 'required|array|min:1',
            'students.*.admission_no' => 'required|unique:students,admission_no',
            'students.*.full_name' => 'required|string|max:120',
            'students.*.dob' => 'required|date',
            'students.*.gender' => 'required|in:male,female,other',
            'students.*.class_id' => 'required|exists:classes,id',
        ]);

        $imported = 0;
        $errors = [];

        foreach ($request->students as $index => $studentData) {
            try {
                // Remove null values to avoid database errors
                $data = array_filter($studentData, fn($v) => $v !== null && $v !== '');

                // Map Excel field names to database field names
                $mappedData = $this->mapExcelToDatabaseFields($data);

                // Validate individual student
                $validated = $this->validateStudentData($mappedData);

                Student::create($validated);
                AuditLog::log('import', 'Student', null, null, $validated);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return back()->with([
            'success' => $imported > 0 ? "Successfully imported {$imported} student(s)." : null,
            'warning' => count($errors) > 0 ? "Errors: " . implode("; ", array_slice($errors, 0, 5)) : null,
        ]);
    }

    private function mapExcelToDatabaseFields(array $data): array
    {
        $mapping = [
            'Student_Name' => 'full_name',
            'Student_CNIC' => 'student_cnic',
            'Father_Name' => 'father_name',
            'Father_CNIC' => 'father_cnic',
            'Mother_Name' => 'mother_name',
            'Mother_CNIC' => 'mother_cnic',
            'Guardian\'s_Name' => 'guardian_name',
            'Guardian\'s_CNIC' => 'guardian_cnic',
            'Address' => 'guardian_address',
            'Contact_Number' => 'phone',
            'Date_of_Birth' => 'dob',
            'Favorite_color' => 'favorite_color',
            'Favorite_Food' => 'favorite_food',
            'Favorite_Subject' => 'favorite_subject',
            'Ambition' => 'ambition',
            'Class' => 'class_id',
            'Course' => 'group_stream',
            'Semester' => 'semester',
            'Joining_Date' => 'join_date_kort',
            'Gender' => 'gender',
            'Status' => 'is_active',
            'Reason_LeftKORT' => 'reason_left_kort',
            'Leaving_Date' => 'leaving_date',
        ];

        $mapped = [];
        foreach ($data as $key => $value) {
            $dbField = $mapping[$key] ?? $key;
            $mapped[$dbField] = $value;
        }

        return $mapped;
    }

    private function validateStudentData(array $data): array
    {
        // Convert boolean-like values
        if (isset($data['is_active'])) {
            $data['is_active'] = in_array($data['is_active'], ['active', '1', 1, true]) ? 1 : 0;
        }

        // Default is_active to true if not specified
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // Ensure required fields are present
        if (!isset($data['admission_no'])) {
            $data['admission_no'] = 'ADM' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }

        // Convert group_stream values
        if (isset($data['group_stream'])) {
            $data['group_stream'] = strtolower(str_replace(' ', '_', $data['group_stream']));
        }

        // Set defaults
        $data['gender'] = $data['gender'] ?? 'male';
        $data['is_orphan'] = $data['is_orphan'] ?? false;

        return $data;
    }
}
