<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Mail\StudentSubjectSelectionChanged;
use App\Models\{Student, SubjectGroup, StudentSubjectSelection, Subject};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class SubjectSelectionController extends Controller
{
    /**
     * Show subject selection form for student
     */
    public function index()
    {
        $student = auth()->user()->student;
        if (!$student) abort(403, 'Not a student');

        $class = $student->class;
        if (!$class) abort(404, 'No class assigned');

        // Get all subject groups for the student's stream
        $subjectGroups = SubjectGroup::where('stream', $student->group_stream)
            ->where('is_active', true)
            ->with('subjects')
            ->get();

        // Get student's current selections
        $selectedSubjects = $student->subjectSelections()
            ->pluck('subject_id')
            ->toArray();

        return Inertia::render('Student/SubjectSelection', compact('student', 'subjectGroups', 'selectedSubjects', 'class'));
    }

    /**
     * Save student's subject selections
     */
    public function store(Request $request)
    {
        $student = auth()->user()->student;
        if (!$student) abort(403, 'Not a student');

        $selections = $request->validate([
            'selections' => 'required|array',
            'selections.*.subject_id' => 'required|exists:subjects,id',
            'selections.*.subject_group_id' => 'required|exists:subject_groups,id',
        ]);

        // Get all subject groups for validation
        $subjectGroups = SubjectGroup::where('stream', $student->group_stream)->get();

        // Validate selections against group rules
        $validationErrors = $this->validateSelections($selections['selections'], $student, $subjectGroups);

        if (!empty($validationErrors)) {
            return back()->withErrors(['selections' => implode('; ', $validationErrors)]);
        }

        // Clear existing selections
        $student->subjectSelections()->delete();

        // Save new selections
        $selectedSubjects = [];
        foreach ($selections['selections'] as $selection) {
            $subject = Subject::find($selection['subject_id']);
            $group = SubjectGroup::find($selection['subject_group_id']);
            $subjectType = $group->subjects()
                ->where('subject_id', $selection['subject_id'])
                ->first()?->pivot->subject_type ?? 'compulsory';

            StudentSubjectSelection::create([
                'student_id' => $student->id,
                'subject_id' => $selection['subject_id'],
                'subject_group_id' => $selection['subject_group_id'],
                'subject_type' => $subjectType,
            ]);

            $selectedSubjects[] = [
                'name' => $subject->subject_name,
                'group' => $group->group_name,
                'type' => $subjectType,
            ];
        }

        // Send notification email to principal
        try {
            $principal = \App\Models\User::where('role', 'principal')->first();
            if ($principal && $principal->email) {
                Mail::to($principal->email)->queue(
                    new StudentSubjectSelectionChanged($student, $selectedSubjects)
                );
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send subject selection email', ['error' => $e->getMessage()]);
        }

        return redirect()->route('student.subject-selection.index')
            ->with('success', 'Subject selections saved successfully! Principal has been notified.');
    }

    /**
     * Validate selections against group rules
     */
    private function validateSelections($selections, Student $student, $subjectGroups): array
    {
        $errors = [];
        $selectionsByGroup = [];
        $selectedSubjectIds = [];

        // Group selections by subject_group_id
        foreach ($selections as $selection) {
            $groupId = $selection['subject_group_id'];
            if (!isset($selectionsByGroup[$groupId])) {
                $selectionsByGroup[$groupId] = [];
            }
            $selectionsByGroup[$groupId][] = $selection['subject_id'];
            $selectedSubjectIds[] = $selection['subject_id'];
        }

        // Validate each group's selections
        foreach ($subjectGroups as $group) {
            $selectedCount = isset($selectionsByGroup[$group->id]) ? count($selectionsByGroup[$group->id]) : 0;

            // Check compulsory groups (all subjects must be selected)
            if (!$group->is_optional_group && $group->min_select > 0) {
                if ($selectedCount < $group->min_select) {
                    $errors[] = "Group '{$group->group_name}': You must select at least {$group->min_select} subjects (selected {$selectedCount})";
                }
            }

            // Check optional groups (choose 1 from many)
            if ($group->is_optional_group) {
                if ($selectedCount < $group->min_select) {
                    $errors[] = "Group '{$group->group_name}': You must select at least {$group->min_select} subject (selected {$selectedCount})";
                }
                if ($selectedCount > $group->max_select) {
                    $errors[] = "Group '{$group->group_name}': You can select maximum {$group->max_select} subjects (selected {$selectedCount})";
                }
            }
        }

        // Ensure all compulsory subjects are selected
        $compulsoryErrors = $this->validateCompulsorySubjects($selectionsByGroup, $subjectGroups);
        $errors = array_merge($errors, $compulsoryErrors);

        // Validate stream-specific subject rules
        $streamErrors = $this->validateStreamSpecificRules($student, $selectedSubjectIds);
        $errors = array_merge($errors, $streamErrors);

        return $errors;
    }

    /**
     * Validate that all compulsory subjects are selected
     */
    private function validateCompulsorySubjects($selectionsByGroup, $subjectGroups): array
    {
        $errors = [];

        foreach ($subjectGroups as $group) {
            $groupSubjects = $group->subjects()->where('subject_type', 'compulsory')->get();

            foreach ($groupSubjects as $subject) {
                $isSelected = isset($selectionsByGroup[$group->id]) &&
                              in_array($subject->id, $selectionsByGroup[$group->id]);

                if (!$isSelected) {
                    $errors[] = "{$subject->subject_name} is a required subject";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate stream-specific subject rules
     * ICS students: Must select Computer Science (not Biology)
     * Pre-Medical students: Must select Biology (not Computer Science)
     */
    private function validateStreamSpecificRules(Student $student, array $selectedSubjectIds): array
    {
        $errors = [];
        $stream = $student->stream ?? $student->group_stream;

        // Get subject IDs for validation
        $biologySubject = Subject::where('subject_code', 'BIO')->first();
        $computerScienceSubject = Subject::where('subject_code', 'CS')->first();

        if (!$biologySubject || !$computerScienceSubject) {
            return $errors; // Exit if subjects not found
        }

        $biologySelected = in_array($biologySubject->id, $selectedSubjectIds);
        $csSelected = in_array($computerScienceSubject->id, $selectedSubjectIds);

        // ICS Stream Rules
        if ($stream === 'ICS') {
            // ICS students MUST select Computer Science
            if (!$csSelected) {
                $errors[] = "🖥️ ICS Stream: You must select Computer Science";
            }
            // ICS students should NOT select Biology if Computer Science is selected
            if ($csSelected && $biologySelected) {
                $errors[] = "🖥️ ICS Stream: You cannot select both Computer Science and Biology. Choose one only.";
            }
        }

        // Pre-Medical Stream Rules
        if ($stream === 'Pre-Medical') {
            // Pre-Medical students MUST select Biology
            if (!$biologySelected) {
                $errors[] = "🔬 Pre-Medical Stream: You must select Biology";
            }
            // Pre-Medical students should NOT select Computer Science
            if ($csSelected) {
                $errors[] = "🔬 Pre-Medical Stream: Computer Science is not available for Pre-Medical students. Biology is required instead.";
            }
        }

        return $errors;
    }
}
