<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{
    PBLAssignment, PBLStudentGroup, PBLSubmission, PBLEvaluation, PBLRubric,
    SchoolClass, Subject, AuditLog
};
use Illuminate\Http\Request;
use Inertia\Inertia;

class PBLAssignmentsController extends Controller
{
    public function index(Request $request)
    {
        $assignments = PBLAssignment::with(['teacher:id,name', 'class:id,class,section', 'subject:id,subject_name'])
            ->when($request->search, fn($q) => $q->where('project_title', 'like', '%' . $request->search . '%'))
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        return Inertia::render('Principal/ProfessionalDevelopment/PBLAssignments/Index', compact('assignments', 'classes'));
    }

    public function show(PBLAssignment $assignment)
    {
        $assignment->load([
            'teacher:id,name,email',
            'class:id,class,section',
            'subject:id,subject_name',
            'rubric',
            'groups.activeMembers.student',
            'submissions',
        ]);

        return Inertia::render('Principal/ProfessionalDevelopment/PBLAssignments/Show', compact('assignment'));
    }

    public function create()
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);
        $rubrics = PBLRubric::where('is_active', true)->get(['id', 'rubric_name']);

        return Inertia::render('Principal/ProfessionalDevelopment/PBLAssignments/Form', compact('classes', 'subjects', 'rubrics'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_title' => 'required|string|max:255',
            'description' => 'required|string',
            'teacher_id' => 'required|exists:users,id',
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
            'status' => 'required|in:draft,active,in-progress,evaluation,completed',
        ]);

        $assignment = PBLAssignment::create($data);
        AuditLog::log('create', 'PBLAssignment', $assignment->id, null, $data);

        return redirect()->route('principal.professional-development.pbl-assignments.show', $assignment->id)
            ->with('success', 'PBL assignment created successfully.');
    }

    public function edit(PBLAssignment $assignment)
    {
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjects = Subject::where('is_active', true)->get(['id', 'subject_name']);
        $rubrics = PBLRubric::where('is_active', true)->get(['id', 'rubric_name']);

        return Inertia::render('Principal/ProfessionalDevelopment/PBLAssignments/Form', compact('assignment', 'classes', 'subjects', 'rubrics'));
    }

    public function update(Request $request, PBLAssignment $assignment)
    {
        $data = $request->validate([
            'project_title' => 'required|string|max:255',
            'description' => 'required|string',
            'teacher_id' => 'required|exists:users,id',
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
            'status' => 'required|in:draft,active,in-progress,evaluation,completed',
        ]);

        $oldValues = $assignment->getAttributes();
        $assignment->update($data);
        AuditLog::log('update', 'PBLAssignment', $assignment->id, $oldValues, $data);

        return back()->with('success', 'PBL assignment updated successfully.');
    }

    public function destroy(PBLAssignment $assignment)
    {
        $oldValues = $assignment->getAttributes();
        $assignment->delete();
        AuditLog::log('delete', 'PBLAssignment', $assignment->id, $oldValues, null);

        return back()->with('success', 'PBL assignment deleted successfully.');
    }

    public function viewSubmissions(PBLAssignment $assignment)
    {
        $submissions = PBLSubmission::where('assignment_id', $assignment->id)
            ->with(['group.activeMembers.student', 'evaluation'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Principal/ProfessionalDevelopment/PBLAssignments/Submissions', compact('assignment', 'submissions'));
    }

    public function createGroup(Request $request, PBLAssignment $assignment)
    {
        $data = $request->validate([
            'group_name' => 'required|string|max:255',
            'group_leader_id' => 'required|exists:users,id',
        ]);

        $data['assignment_id'] = $assignment->id;
        $group = PBLStudentGroup::create($data);

        AuditLog::log('create_group', 'PBLStudentGroup', $group->id, null, $data);

        return back()->with('success', 'Student group created successfully.');
    }

    public function evaluateSubmission(Request $request, PBLSubmission $submission)
    {
        $data = $request->validate([
            'total_score' => 'required|numeric|min:0',
            'total_marks' => 'required|numeric|min:1',
            'general_feedback' => 'required|string',
            'evaluation_type' => 'required|in:teacher,peer,self,combined',
            'criteria_scores' => 'nullable|json',
            'strength_areas' => 'nullable|json',
            'improvement_areas' => 'nullable|json',
        ]);

        $data['submission_id'] = $submission->id;
        $data['group_id'] = $submission->group_id;
        $data['evaluator_id'] = auth()->id();
        $data['percentage'] = ($data['total_score'] / $data['total_marks']) * 100;
        $data['status'] = 'finalized';
        $data['evaluated_at'] = now();

        $evaluation = PBLEvaluation::create($data);
        AuditLog::log('evaluate_submission', 'PBLEvaluation', $evaluation->id, null, $data);

        return back()->with('success', 'Submission evaluated successfully.');
    }
}
