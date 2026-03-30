<?php

namespace App\Http\Controllers;

use App\Models\{
    ProficiencyTestAssignment, ProficiencyTestAttempt,
    ProficiencyTestAnswer, ProficiencyTestQuestion
};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProficiencyTestTakeController extends Controller
{
    /** List tests assigned to the current user */
    public function myTests()
    {
        $assignments = ProficiencyTestAssignment::where('user_id', auth()->id())
            ->with([
                'test:id,title,description,duration_minutes,passing_score,total_marks,status',
                'attempt',
            ])
            ->latest()
            ->get();

        return Inertia::render('ProficiencyTests/MyTests', compact('assignments'));
    }

    /** Start or resume a test */
    public function take(ProficiencyTestAssignment $assignment)
    {
        abort_if($assignment->user_id !== auth()->id(), 403);
        abort_if($assignment->status === 'completed', 403, 'You have already completed this test.');

        $test = $assignment->test()->with('sections.questions')->first();

        // Create or fetch attempt
        $attempt = ProficiencyTestAttempt::firstOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => auth()->id()],
            [
                'started_at' => now(),
                'max_score'  => $test->total_marks,
                'status'     => 'in_progress',
            ]
        );

        if ($attempt->status === 'completed') {
            return redirect()->route('proficiency-tests.result', $attempt->id);
        }

        $assignment->update(['status' => 'in_progress']);

        // Existing answers (for resume)
        $existingAnswers = $attempt->answers()->pluck('answer', 'question_id');

        return Inertia::render('ProficiencyTests/Take', [
            'assignment'      => $assignment->only('id', 'due_date'),
            'test'            => $test,
            'attempt'         => $attempt->only('id', 'started_at', 'status'),
            'existingAnswers' => $existingAnswers,
        ]);
    }

    /** Submit answers */
    public function submit(Request $request, ProficiencyTestAttempt $attempt)
    {
        abort_if($attempt->user_id !== auth()->id(), 403);
        abort_if($attempt->status === 'completed', 403);

        $data = $request->validate([
            'answers'              => 'required|array',
            'answers.*.question_id' => 'required|exists:proficiency_test_questions,id',
            'answers.*.answer'      => 'nullable|string',
        ]);

        $totalScore  = 0;
        $hasEssay    = false;
        $sectionScores = [];

        foreach ($data['answers'] as $ans) {
            $question = ProficiencyTestQuestion::find($ans['question_id']);
            if (!$question) continue;

            $isCorrect    = null;
            $marksAwarded = 0;

            if ($question->question_type !== 'essay') {
                $userAnswer = trim(strtolower($ans['answer'] ?? ''));
                $correct    = trim(strtolower($question->correct_answer ?? ''));
                $isCorrect  = $userAnswer !== '' && $userAnswer === $correct;
                $marksAwarded = $isCorrect ? $question->marks : 0;
            } else {
                $hasEssay = true;
            }

            $totalScore += $marksAwarded;

            ProficiencyTestAnswer::updateOrCreate(
                ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                [
                    'answer'        => $ans['answer'] ?? null,
                    'is_correct'    => $isCorrect,
                    'marks_awarded' => $marksAwarded,
                ]
            );

            // Track per-section
            $sectionId = $question->section_id;
            $sectionScores[$sectionId] = ($sectionScores[$sectionId] ?? 0) + $marksAwarded;
        }

        $max        = $attempt->max_score ?: 1;
        $percentage = round(($totalScore / $max) * 100, 2);
        $status     = $hasEssay ? 'grading' : 'completed';

        $attempt->update([
            'completed_at'  => now(),
            'total_score'   => $totalScore,
            'percentage'    => $hasEssay ? null : $percentage,
            'band_score'    => $hasEssay ? null : ProficiencyTestAttempt::calculateBandScore($percentage),
            'status'        => $status,
            'section_scores' => $sectionScores,
        ]);

        ProficiencyTestAssignment::where('id', $attempt->assignment_id)
            ->update(['status' => $hasEssay ? 'in_progress' : 'completed']);

        return redirect()->route('proficiency-tests.result', $attempt->id);
    }

    /** View result */
    public function result(ProficiencyTestAttempt $attempt)
    {
        abort_if($attempt->user_id !== auth()->id() && !in_array(auth()->user()->role, ['admin', 'principal']), 403);

        $attempt->load([
            'assignment.test.sections.questions',
            'answers',
        ]);

        return Inertia::render('ProficiencyTests/Result', compact('attempt'));
    }
}
