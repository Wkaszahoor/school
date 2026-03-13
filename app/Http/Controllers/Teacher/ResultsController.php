<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{Result, Student, SchoolClass, Subject, TeacherAssignment, AuditLog, SubjectGroup};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResultsController extends Controller
{
    public function index(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $myAssignments = TeacherAssignment::where('teacher_id', $teacher?->user_id)
            ->with(['class', 'subject'])
            ->get();

        // Check if teacher is a class teacher
        $myClassTeacherAssignment = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first(['id', 'class', 'section']);

        // Determine which class to view
        $viewingOwnClass = false;
        $classTeacherStudents = collect();
        $classTeacherSubjects = collect();
        $classTeacherResults = [];
        $classTeacherStudentsByGroup = [];
        $allClassSubjects = collect();
        $subjectGroupsWithSubjects = [];

        if ($myClassTeacherAssignment) {
            $classIdToView = $request->class_id ? (int)$request->class_id : $myClassTeacherAssignment->id;
            $viewingOwnClass = ($classIdToView == $myClassTeacherAssignment->id);

            // Load all students in this class with their subject groups
            $classTeacherStudents = Student::where('class_id', $classIdToView)
                ->where('is_active', true)
                ->with(['subjectGroup:id,group_name,stream'])
                ->select('id', 'full_name', 'admission_no', 'stream', 'subject_group_id')
                ->get();

            // Load ONLY subjects this teacher is assigned to teach in this class
            $classTeacherSubjects = TeacherAssignment::where('teacher_id', $teacher?->user_id)
                ->where('class_id', $classIdToView)
                ->with('subject')
                ->get()
                ->pluck('subject')
                ->unique('id')
                ->values();

            // For results matrix, load ALL subjects in class results (not just teacher's subjects)
            $allSubjectIds = TeacherAssignment::where('class_id', $classIdToView)
                ->select('subject_id')
                ->distinct()
                ->pluck('subject_id')
                ->unique()
                ->values();

            $classTeacherResults = Result::where('class_id', $classIdToView)
                ->whereIn('subject_id', $allSubjectIds)
                ->with(['student', 'subject'])
                ->when($request->exam_type, fn($q) => $q->where('exam_type', $request->exam_type))
                ->get()
                ->groupBy(fn($r) => $r->student_id)
                ->mapWithKeys(fn($group, $studentId) => [
                    $studentId => $group->keyBy(fn($r) => $r->subject_id)
                ])
                ->all();

            // Group students by their subject group (for classes 9-12)
            $studentsByGroupName = $classTeacherStudents
                ->groupBy(fn($student) => $student->subjectGroup?->group_name ?? ($student->stream ?? 'No Group'));

            $classTeacherStudentsByGroup = $studentsByGroupName
                ->mapWithKeys(fn($group, $groupName) => [
                    $groupName => $group->count()
                ])
                ->all();

            // Build a map of student ID to group name for filtering
            $studentToGroupMap = [];
            foreach ($studentsByGroupName as $groupName => $students) {
                foreach ($students as $student) {
                    $studentToGroupMap[$student->id] = $groupName;
                }
            }

            // Load ALL subjects in this class (with deduplication)
            $allClassSubjects = Subject::whereIn('id', $allSubjectIds)
                ->select('id', 'subject_name')
                ->orderBy('subject_name')
                ->get()
                ->unique('id')
                ->values();

            // Load subject groups with their subjects for filtering (all subject groups, not just ICS/Pre-Medical)
            $subjectGroupsWithSubjects = SubjectGroup::with(['subjects' => fn($q) => $q->select('subjects.id', 'subjects.subject_name')])
                ->select('id', 'group_name', 'stream')
                ->get()
                ->keyBy('group_name')
                ->map(fn($group) => [
                    'name' => $group->group_name,
                    'stream' => $group->stream,
                    'subjects' => $group->subjects->pluck('id')->toArray()
                ])
                ->all();
        }

        // Pending class teacher review results (for class teacher)
        $pendingClassTeacherReview = [];
        if ($myClassTeacherAssignment) {
            $pendingClassTeacherReview = Result::where('class_id', $myClassTeacherAssignment->id)
                ->where('approval_status', 'pending')
                ->with(['student:id,full_name,admission_no', 'subject:id,subject_name', 'teacher:id,name'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
        }

        // Regular results query for all other cases or general viewing
        $results = Result::with(['student', 'subject', 'class'])
            ->where('teacher_id', auth()->id())
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->exam_type, fn($q) => $q->where('exam_type', $request->exam_type))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $examTypes = config('school.exam_types');
        $filters = $request->only(['class_id', 'exam_type']);

        // Convert Eloquent models to arrays for proper Inertia serialization
        $classTeacherStudentsArray = $classTeacherStudents->map(fn($student) => [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'admission_no' => $student->admission_no,
            'stream' => $student->stream,
            'subject_group_id' => $student->subject_group_id,
            'subjectGroup' => $student->subjectGroup ? [
                'id' => $student->subjectGroup->id,
                'group_name' => $student->subjectGroup->group_name,
                'stream' => $student->subjectGroup->stream,
            ] : null,
        ])->toArray();

        return Inertia::render('Teacher/Results/Index', [
            'results' => $results,
            'myAssignments' => $myAssignments,
            'examTypes' => $examTypes,
            'filters' => $filters,
            'myClassTeacherAssignment' => $myClassTeacherAssignment,
            'viewingOwnClass' => $viewingOwnClass,
            'classTeacherStudents' => $classTeacherStudentsArray,
            'classTeacherSubjects' => $classTeacherSubjects,
            'classTeacherResults' => $classTeacherResults,
            'classTeacherStudentsByGroup' => $classTeacherStudentsByGroup,
            'allClassSubjects' => $allClassSubjects,
            'subjectGroupsWithSubjects' => $subjectGroupsWithSubjects,
            'studentToGroupMap' => $studentToGroupMap ?? [],
            'pendingClassTeacherReview' => $pendingClassTeacherReview,
        ]);
    }

    public function create(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;
        $assignments = TeacherAssignment::where('teacher_id', $teacher?->user_id)
            ->with(['class', 'subject'])
            ->get();

        // Get class teacher info for each class
        $classTeachers = SchoolClass::whereIn('id', $assignments->pluck('class_id')->unique())
            ->with(['classTeacher' => fn($q) => $q->select('id', 'name')])
            ->get(['id', 'class_teacher_id'])
            ->keyBy('id');

        // Merge class teacher info into assignments
        $assignments = $assignments->map(function($assignment) use ($classTeachers) {
            $assignment->class_teacher_name = $classTeachers[$assignment->class_id]?->classTeacher?->name ?? 'Not assigned';
            return $assignment;
        });

        // Check if teacher is a class teacher
        $myClassTeacherAssignment = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first(['id']);
        $classIdToLoad = $request->class_id ?? $myClassTeacherAssignment?->id;

        $students = collect();
        $allSubjectsInClass = collect();
        if ($classIdToLoad) {
            $students = Student::where('class_id', $classIdToLoad)
                ->where('is_active', true)
                ->get(['id', 'full_name', 'admission_no']);

            // Load only the current teacher's assigned subjects in this class
            $subjectIds = TeacherAssignment::where('class_id', $classIdToLoad)
                ->where('teacher_id', $teacher?->user_id)
                ->distinct()
                ->pluck('subject_id');

            // Get the full subject details for those IDs
            if ($subjectIds->isNotEmpty()) {
                $allSubjectsInClass = Subject::whereIn('id', $subjectIds)
                    ->select('id', 'subject_name')
                    ->get();
            }
        }

        $examTypes = array_keys(config('school.exam_types'));
        $terms = array_keys(config('school.terms'));
        $filters = ['class_id' => $classIdToLoad ?? $request->class_id];

        return Inertia::render('Teacher/Results/Create', compact('assignments', 'students', 'examTypes', 'terms', 'filters', 'myClassTeacherAssignment', 'allSubjectsInClass'));
    }

    public function store(Request $request)
    {
        // Validate based on submission mode
        $isMatrixSubmission = empty($request->subject_id) && !empty($request->results);

        if ($isMatrixSubmission) {
            // Matrix submission - each result has its own subject_id
            $request->validate([
                'class_id'      => 'required|exists:classes,id',
                'exam_type'     => 'required',
                'academic_year' => 'required|string',
                'term'          => 'required',
                'results'       => 'required|array|min:1',
                'results.*.student_id'     => 'required|exists:students,id',
                'results.*.subject_id'     => 'required|exists:subjects,id',
                'results.*.total_marks'    => 'required|numeric|min:0',
                'results.*.obtained_marks' => 'required|numeric|min:0',
            ]);
        } else {
            // Subject-by-subject submission
            $request->validate([
                'class_id'      => 'required|exists:classes,id',
                'subject_id'    => 'required|exists:subjects,id',
                'exam_type'     => 'required',
                'academic_year' => 'required|string',
                'term'          => 'required',
                'results'       => 'required|array',
                'results.*.student_id'     => 'required|exists:students,id',
                'results.*.total_marks'    => 'required|numeric|min:0',
                'results.*.obtained_marks' => 'required|numeric|min:0',
            ]);
        }

        $subjectId = $request->subject_id;
        $resultCount = 0;

        foreach ($request->results as $row) {
            // For matrix submission, use subject_id from row; otherwise use the form subject_id
            $rowSubjectId = $isMatrixSubmission ? $row['subject_id'] : $subjectId;

            // Check if result is already locked
            $existingResult = Result::where('student_id', $row['student_id'])
                ->where('class_id', $request->class_id)
                ->where('subject_id', $rowSubjectId)
                ->where('exam_type', $request->exam_type)
                ->where('academic_year', $request->academic_year)
                ->where('term', $request->term)
                ->first();

            if ($existingResult && $existingResult->is_locked) {
                return back()->with('error', 'Cannot edit locked results. Contact the principal.');
            }

            $percentage = $row['total_marks'] > 0
                ? round(($row['obtained_marks'] / $row['total_marks']) * 100, 2)
                : 0;

            [$grade, $gpa] = $this->calculateGrade($percentage);

            Result::updateOrCreate(
                [
                    'student_id'    => $row['student_id'],
                    'class_id'      => $request->class_id,
                    'subject_id'    => $rowSubjectId,
                    'exam_type'     => $request->exam_type,
                    'academic_year' => $request->academic_year,
                    'term'          => $request->term,
                ],
                [
                    'teacher_id'     => auth()->id(),
                    'total_marks'    => $row['total_marks'],
                    'obtained_marks' => $row['obtained_marks'],
                    'percentage'     => $percentage,
                    'grade'          => $grade,
                    'gpa_point'      => $gpa,
                    'approval_status' => 'pending',
                ]
            );

            $resultCount++;
        }

        $logSubject = $isMatrixSubmission ? 'Multiple Subjects' : $subjectId;
        AuditLog::log('bulk_create', 'Result', $logSubject, null, ['exam_type' => $request->exam_type, 'count' => $resultCount]);

        // Check if class has a class teacher
        $classHasTeacher = SchoolClass::where('id', $request->class_id)
            ->whereNotNull('class_teacher_id')
            ->exists();

        $message = $classHasTeacher
            ? "Results saved successfully ($resultCount entries). Awaiting class teacher review."
            : "Results saved successfully ($resultCount entries). Awaiting principal approval.";

        return redirect()->route('teacher.results.index')
            ->with('success', $message);
    }

    private function calculateGrade(float $percentage): array
    {
        $scale = config('school.gpa_scale');
        foreach ($scale as $min => [$grade, $gpa]) {
            if ($percentage >= $min) return [$grade, $gpa];
        }
        return ['F', 0.0];
    }

    /**
     * Class teacher approves a pending result
     */
    public function classTeacherApprove(Request $request, Result $result)
    {
        $teacher = auth()->user()->teacherProfile;

        // Verify this teacher is the class teacher
        $myClassTeacherAssignment = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first();
        if (!$myClassTeacherAssignment || $result->class_id !== $myClassTeacherAssignment->id) {
            abort(403, 'Unauthorized');
        }

        // Only pending results can be approved
        if ($result->approval_status !== 'pending') {
            return back()->with('error', 'Only pending results can be approved.');
        }

        $request->validate([
            'remarks' => 'nullable|string|max:500',
        ]);

        $result->update([
            'approval_status' => 'class_teacher_approved',
            'class_teacher_reviewed_by' => auth()->id(),
            'class_teacher_reviewed_at' => now(),
            'class_teacher_remarks' => $request->remarks,
        ]);

        AuditLog::log('update', 'Result', $result->id, null, [
            'action' => 'class_teacher_approve',
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result approved and forwarded to principal.');
    }

    /**
     * Class teacher rejects a pending result
     */
    public function classTeacherReject(Request $request, Result $result)
    {
        $teacher = auth()->user()->teacherProfile;

        // Verify this teacher is the class teacher
        $myClassTeacherAssignment = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first();
        if (!$myClassTeacherAssignment || $result->class_id !== $myClassTeacherAssignment->id) {
            abort(403, 'Unauthorized');
        }

        // Only pending results can be rejected
        if ($result->approval_status !== 'pending') {
            return back()->with('error', 'Only pending results can be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'remarks' => 'nullable|string|max:500',
        ]);

        $result->update([
            'approval_status' => 'rejected',
            'class_teacher_reviewed_by' => auth()->id(),
            'class_teacher_reviewed_at' => now(),
            'class_teacher_remarks' => $request->remarks,
            'rejection_reason' => $request->rejection_reason,
        ]);

        AuditLog::log('update', 'Result', $result->id, null, [
            'action' => 'class_teacher_reject',
            'rejection_reason' => $request->rejection_reason,
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result rejected and returned to subject teacher.');
    }

    /**
     * Bulk approve multiple pending results by class teacher
     */
    public function classTeacherBulkApprove(Request $request)
    {
        $teacher = auth()->user()->teacherProfile;

        // Verify this teacher is the class teacher
        $myClassTeacherAssignment = SchoolClass::where('class_teacher_id', $teacher?->user_id)->first();
        if (!$myClassTeacherAssignment) {
            abort(403, 'You are not assigned as a class teacher.');
        }

        $request->validate([
            'result_ids' => 'required|array|min:1',
            'result_ids.*' => 'exists:results,id',
        ]);

        $results = Result::whereIn('id', $request->result_ids)
            ->where('class_id', $myClassTeacherAssignment->id)
            ->where('approval_status', 'pending')
            ->get();

        $approvedCount = 0;
        foreach ($results as $result) {
            $result->update([
                'approval_status' => 'class_teacher_approved',
                'class_teacher_reviewed_by' => auth()->id(),
                'class_teacher_reviewed_at' => now(),
            ]);
            $approvedCount++;
        }

        AuditLog::log('bulk_update', 'Result', 'Bulk Approval', null, [
            'action' => 'class_teacher_bulk_approve',
            'count' => $approvedCount,
        ]);

        return back()->with('success', "$approvedCount results approved and forwarded to principal.");
    }
}
