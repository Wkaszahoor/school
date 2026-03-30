<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{
    ProficiencyTest, ProficiencyTestSection, ProficiencyTestQuestion,
    ProficiencyTestAssignment, ProficiencyTestAttempt, User, AuditLog
};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProficiencyTestsController extends Controller
{
    public function index()
    {
        $tests = ProficiencyTest::with('creator:id,name')
            ->withCount('assignments')
            ->latest()
            ->paginate(20);

        return Inertia::render('Principal/ProficiencyTests/Index', compact('tests'));
    }

    public function create()
    {
        return Inertia::render('Principal/ProficiencyTests/Form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'duration_minutes' => 'required|integer|min:5',
            'passing_score'    => 'required|integer|min:1|max:100',
            'status'           => 'required|in:draft,active,archived',
            'sections'         => 'required|array|min:1',
            'sections.*.name'         => 'required|string|max:100',
            'sections.*.instructions' => 'nullable|string',
            'sections.*.passage'      => 'nullable|string',
            'sections.*.questions'    => 'required|array|min:1',
            'sections.*.questions.*.question_text'   => 'required|string',
            'sections.*.questions.*.question_type'   => 'required|in:mcq,true_false,fill_blank,essay',
            'sections.*.questions.*.options'         => 'nullable|array',
            'sections.*.questions.*.correct_answer'  => 'nullable|string',
            'sections.*.questions.*.marks'           => 'required|integer|min:1',
        ]);

        $test = ProficiencyTest::create([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'passing_score'    => $data['passing_score'],
            'status'           => $data['status'],
            'created_by'       => auth()->id(),
        ]);

        $totalMarks = 0;
        foreach ($data['sections'] as $i => $sectionData) {
            $sectionMarks = collect($sectionData['questions'])->sum('marks');
            $totalMarks += $sectionMarks;

            $section = ProficiencyTestSection::create([
                'test_id'      => $test->id,
                'name'         => $sectionData['name'],
                'instructions' => $sectionData['instructions'] ?? null,
                'passage'      => $sectionData['passage'] ?? null,
                'order'        => $i,
                'marks'        => $sectionMarks,
            ]);

            foreach ($sectionData['questions'] as $j => $q) {
                ProficiencyTestQuestion::create([
                    'section_id'     => $section->id,
                    'question_text'  => $q['question_text'],
                    'question_type'  => $q['question_type'],
                    'options'        => $q['options'] ?? null,
                    'correct_answer' => $q['correct_answer'] ?? null,
                    'marks'          => $q['marks'],
                    'order'          => $j,
                ]);
            }
        }

        $test->update(['total_marks' => $totalMarks]);
        AuditLog::log('create', 'ProficiencyTest', $test->id, null, ['title' => $test->title]);

        return redirect()->route('principal.proficiency-tests.show', $test->id)
            ->with('success', 'Proficiency test created successfully.');
    }

    public function show(ProficiencyTest $proficiencyTest)
    {
        $proficiencyTest->load([
            'sections.questions',
            'assignments.user:id,name,email,role',
            'assignments.attempt',
        ]);

        $stats = [
            'total_assigned' => $proficiencyTest->assignments->count(),
            'completed'      => $proficiencyTest->assignments->where('status', 'completed')->count(),
            'pending'        => $proficiencyTest->assignments->where('status', 'pending')->count(),
            'avg_score'      => $proficiencyTest->assignments
                ->map(fn($a) => $a->attempt?->percentage ?? null)
                ->filter()
                ->avg() ?? 0,
        ];

        return Inertia::render('Principal/ProficiencyTests/Show', [
            'test'  => $proficiencyTest,
            'stats' => $stats,
        ]);
    }

    public function edit(ProficiencyTest $proficiencyTest)
    {
        $proficiencyTest->load('sections.questions');
        return Inertia::render('Principal/ProficiencyTests/Form', ['test' => $proficiencyTest]);
    }

    public function update(Request $request, ProficiencyTest $proficiencyTest)
    {
        $data = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'duration_minutes' => 'required|integer|min:5',
            'passing_score'    => 'required|integer|min:1|max:100',
            'status'           => 'required|in:draft,active,archived',
            'sections'         => 'required|array|min:1',
            'sections.*.name'         => 'required|string|max:100',
            'sections.*.instructions' => 'nullable|string',
            'sections.*.passage'      => 'nullable|string',
            'sections.*.questions'    => 'required|array|min:1',
            'sections.*.questions.*.question_text'   => 'required|string',
            'sections.*.questions.*.question_type'   => 'required|in:mcq,true_false,fill_blank,essay',
            'sections.*.questions.*.options'         => 'nullable|array',
            'sections.*.questions.*.correct_answer'  => 'nullable|string',
            'sections.*.questions.*.marks'           => 'required|integer|min:1',
        ]);

        $old = $proficiencyTest->getAttributes();
        $proficiencyTest->update([
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'passing_score'    => $data['passing_score'],
            'status'           => $data['status'],
        ]);

        // Rebuild sections
        $proficiencyTest->sections()->each(fn($s) => $s->questions()->delete());
        $proficiencyTest->sections()->delete();

        $totalMarks = 0;
        foreach ($data['sections'] as $i => $sectionData) {
            $sectionMarks = collect($sectionData['questions'])->sum('marks');
            $totalMarks += $sectionMarks;

            $section = ProficiencyTestSection::create([
                'test_id'      => $proficiencyTest->id,
                'name'         => $sectionData['name'],
                'instructions' => $sectionData['instructions'] ?? null,
                'passage'      => $sectionData['passage'] ?? null,
                'order'        => $i,
                'marks'        => $sectionMarks,
            ]);

            foreach ($sectionData['questions'] as $j => $q) {
                ProficiencyTestQuestion::create([
                    'section_id'     => $section->id,
                    'question_text'  => $q['question_text'],
                    'question_type'  => $q['question_type'],
                    'options'        => $q['options'] ?? null,
                    'correct_answer' => $q['correct_answer'] ?? null,
                    'marks'          => $q['marks'],
                    'order'          => $j,
                ]);
            }
        }

        $proficiencyTest->update(['total_marks' => $totalMarks]);
        AuditLog::log('update', 'ProficiencyTest', $proficiencyTest->id, $old, $data);

        return back()->with('success', 'Test updated successfully.');
    }

    public function destroy(ProficiencyTest $proficiencyTest)
    {
        $old = $proficiencyTest->getAttributes();
        $proficiencyTest->delete();
        AuditLog::log('delete', 'ProficiencyTest', $proficiencyTest->id, $old, null);
        return redirect()->route('principal.proficiency-tests.index')
            ->with('success', 'Test deleted.');
    }

    public function assign(ProficiencyTest $proficiencyTest)
    {
        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name', 'email']);
        $students = User::where('role', 'student')->orderBy('name')->get(['id', 'name', 'email']);

        $assigned = $proficiencyTest->assignments()->pluck('user_id')->toArray();

        return Inertia::render('Principal/ProficiencyTests/Assign', [
            'test'     => $proficiencyTest->only('id', 'title'),
            'teachers' => $teachers,
            'students' => $students,
            'assigned' => $assigned,
        ]);
    }

    public function storeAssignment(Request $request, ProficiencyTest $proficiencyTest)
    {
        $data = $request->validate([
            'user_ids'  => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'due_date'  => 'nullable|date|after:today',
        ]);

        $assigned = 0;
        foreach ($data['user_ids'] as $userId) {
            ProficiencyTestAssignment::firstOrCreate(
                ['test_id' => $proficiencyTest->id, 'user_id' => $userId],
                [
                    'assigned_by' => auth()->id(),
                    'due_date'    => $data['due_date'] ?? null,
                    'status'      => 'pending',
                ]
            );
            $assigned++;
        }

        AuditLog::log('assign', 'ProficiencyTest', $proficiencyTest->id, null, ['assigned_count' => $assigned]);

        return back()->with('success', "Test assigned to {$assigned} user(s) successfully.");
    }

    public function removeAssignment(ProficiencyTest $proficiencyTest, User $user)
    {
        ProficiencyTestAssignment::where('test_id', $proficiencyTest->id)
            ->where('user_id', $user->id)
            ->delete();

        return back()->with('success', 'Assignment removed.');
    }

    public function gradeEssay(Request $request, \App\Models\ProficiencyTestAnswer $answer)
    {
        $data = $request->validate([
            'marks_awarded' => 'required|integer|min:0',
            'feedback'      => 'nullable|string',
        ]);

        $question = $answer->question;
        $data['marks_awarded'] = min($data['marks_awarded'], $question->marks);
        $data['is_correct'] = $data['marks_awarded'] >= $question->marks;

        $answer->update($data);

        // Check if all essay answers in this attempt are graded; if so, finalize
        $attempt = $answer->attempt;
        $ungradedEssays = $attempt->answers()
            ->whereHas('question', fn($q) => $q->where('question_type', 'essay'))
            ->whereNull('is_correct')
            ->count();

        if ($ungradedEssays === 0 && $attempt->status === 'grading') {
            $total = $attempt->answers()->sum('marks_awarded');
            $max   = $attempt->max_score;
            $pct   = $max > 0 ? round(($total / $max) * 100, 2) : 0;

            $attempt->update([
                'total_score' => $total,
                'percentage'  => $pct,
                'band_score'  => ProficiencyTestAttempt::calculateBandScore($pct),
                'status'      => 'completed',
            ]);

            ProficiencyTestAssignment::where('id', $attempt->assignment_id)
                ->update(['status' => 'completed']);
        }

        return back()->with('success', 'Essay graded.');
    }
}
