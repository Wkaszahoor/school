<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{
    PBLAssignment, PBLStudentGroup, PBLGroupMember, PBLSubmission, PBLEvaluation,
    Student, AuditLog
};
use Illuminate\Http\Request;
use Inertia\Inertia;

class PBLAssignmentsController extends Controller
{
    public function index(Request $request)
    {
        $assignments = PBLAssignment::where('teacher_id', auth()->id())
            ->with(['class:id,class,section', 'subject:id,subject_name', 'groups'])
            ->when($request->search, fn($q) => $q->where('project_title', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Teacher/ProfessionalDevelopment/PBLAssignments/Index', compact('assignments'));
    }

    public function show(PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $assignment->load([
            'class:id,class,section',
            'subject:id,subject_name',
            'rubric',
            'groups.activeMembers.student:id,first_name,last_name',
            'submissions',
        ]);

        return Inertia::render('Teacher/ProfessionalDevelopment/PBLAssignments/Show', compact('assignment'));
    }

    public function create()
    {
        $subjects = auth()->user()->teacherAssignments()
            ->distinct('subject_id')
            ->with('subject:id,subject_name')
            ->get(['subject_id'])->pluck('subject');

        $classes = auth()->user()->teacherAssignments()
            ->distinct('class_id')
            ->with('class:id,class,section')
            ->get(['class_id'])->pluck('class');

        return Inertia::render('Teacher/ProfessionalDevelopment/PBLAssignments/Form', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_title' => 'required|string|max:255',
            'description' => 'required|string',
            'class_id' => 'nullable|exists:classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'rubric_id' => 'nullable|exists:pbl_rubrics,id',
            'project_type' => 'required|in:individual,group',
            'learning_objectives' => 'nullable|string',
            'requirements' => 'nullable|string',
            'group_size' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date',
            'presentation_date' => 'nullable|date|after:due_date',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:draft,active',
        ]);

        $data['teacher_id'] = auth()->id();
        $assignment = PBLAssignment::create($data);

        AuditLog::log('create', 'PBLAssignment', $assignment->id, null, $data);

        return redirect()->route('teacher.professional-development.pbl-assignments.show', $assignment->id)
            ->with('success', 'PBL assignment created successfully.');
    }

    public function edit(PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $subjects = auth()->user()->teacherAssignments()
            ->distinct('subject_id')
            ->with('subject:id,subject_name')
            ->get(['subject_id'])->pluck('subject');

        $classes = auth()->user()->teacherAssignments()
            ->distinct('class_id')
            ->with('class:id,class,section')
            ->get(['class_id'])->pluck('class');

        return Inertia::render('Teacher/ProfessionalDevelopment/PBLAssignments/Form', compact('assignment', 'classes', 'subjects'));
    }

    public function update(Request $request, PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $data = $request->validate([
            'project_title' => 'required|string|max:255',
            'description' => 'required|string',
            'class_id' => 'nullable|exists:classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'project_type' => 'required|in:individual,group',
            'learning_objectives' => 'nullable|string',
            'requirements' => 'nullable|string',
            'group_size' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'due_date' => 'required|date|after:start_date',
            'presentation_date' => 'nullable|date|after:due_date',
            'total_marks' => 'required|integer|min:1',
            'status' => 'required|in:draft,active,in-progress,evaluation,completed',
        ]);

        $oldValues = $assignment->getAttributes();
        $assignment->update($data);
        AuditLog::log('update', 'PBLAssignment', $assignment->id, $oldValues, $data);

        return back()->with('success', 'PBL assignment updated successfully.');
    }

    public function destroy(PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $oldValues = $assignment->getAttributes();
        $assignment->delete();
        AuditLog::log('delete', 'PBLAssignment', $assignment->id, $oldValues, null);

        return back()->with('success', 'PBL assignment deleted successfully.');
    }

    public function storeGroup(Request $request, PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $data = $request->validate([
            'group_name' => 'required|string|max:255',
            'group_leader_id' => 'required|exists:students,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
        ]);

        $group = PBLStudentGroup::create([
            'assignment_id' => $assignment->id,
            'group_name' => $data['group_name'],
            'group_leader_id' => $data['group_leader_id'],
            'member_count' => count($data['student_ids']),
        ]);

        foreach ($data['student_ids'] as $studentId) {
            PBLGroupMember::create([
                'group_id' => $group->id,
                'student_id' => $studentId,
                'role' => $studentId === $data['group_leader_id'] ? 'leader' : 'member',
                'participation_status' => 'active',
                'joined_at' => now(),
            ]);
        }

        AuditLog::log('create_group', 'PBLStudentGroup', $group->id, null, $data);

        return back()->with('success', 'Student group created successfully.');
    }

    public function viewSubmissions(PBLAssignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $submissions = PBLSubmission::where('assignment_id', $assignment->id)
            ->with(['group.activeMembers.student', 'evaluation'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Teacher/ProfessionalDevelopment/PBLAssignments/Submissions', compact('assignment', 'submissions'));
    }

    public function evaluateSubmission(Request $request, PBLSubmission $submission)
    {
        if ($submission->assignment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $data = $request->validate([
            'total_score' => 'required|numeric|min:0',
            'total_marks' => 'required|numeric|min:1',
            'general_feedback' => 'required|string',
            'criteria_scores' => 'nullable|json',
            'strength_areas' => 'nullable|json',
            'improvement_areas' => 'nullable|json',
        ]);

        $data['submission_id'] = $submission->id;
        $data['group_id'] = $submission->group_id;
        $data['evaluator_id'] = auth()->id();
        $data['evaluation_type'] = 'teacher';
        $data['percentage'] = ($data['total_score'] / $data['total_marks']) * 100;
        $data['status'] = 'finalized';
        $data['evaluated_at'] = now();

        $evaluation = PBLEvaluation::create($data);
        AuditLog::log('evaluate_submission', 'PBLEvaluation', $evaluation->id, null, $data);

        return back()->with('success', 'Submission evaluated successfully.');
    }

    public function provideFeedback(Request $request, PBLEvaluation $evaluation)
    {
        if ($evaluation->evaluator_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $data = $request->validate([
            'general_feedback' => 'required|string',
        ]);

        $oldValues = $evaluation->getAttributes();
        $evaluation->update($data);
        AuditLog::log('update_feedback', 'PBLEvaluation', $evaluation->id, $oldValues, $data);

        return back()->with('success', 'Feedback updated successfully.');
    }
}
