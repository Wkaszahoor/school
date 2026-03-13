<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{Student, SchoolClass, SubjectGroup, StudentSubjectSelection};
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentSelectionsController extends Controller
{
    /**
     * Show all student selections with filtering
     */
    public function index(Request $request)
    {
        $query = StudentSubjectSelection::with(['student.class', 'subject', 'subjectGroup'])
            ->latest();

        // Filter by class
        if ($request->filled('class_id')) {
            $query->whereHas('student', fn($q) => $q->where('class_id', $request->class_id));
        }

        // Filter by stream
        if ($request->filled('stream')) {
            $query->whereHas('student', fn($q) => $q->where('group_stream', $request->stream));
        }

        // Filter by subject group
        if ($request->filled('group_id')) {
            $query->where('subject_group_id', $request->group_id);
        }

        $selections = $query->paginate(50)->withQueryString();
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);
        $subjectGroups = SubjectGroup::where('is_active', true)->get(['id', 'group_name', 'stream']);

        return Inertia::render('Principal/StudentSelections/Index', compact('selections', 'classes', 'subjectGroups'));
    }

    /**
     * Show student's detailed selections
     */
    public function show(Student $student)
    {
        $student->load(['class', 'subjectSelections.subject', 'subjectSelections.subjectGroup']);

        $selections = $student->subjectSelections()
            ->with('subject', 'subjectGroup')
            ->get()
            ->groupBy('subjectGroup.id');

        return Inertia::render('Principal/StudentSelections/Show', compact('student', 'selections'));
    }

    /**
     * Show selection reports/statistics
     */
    public function reports(Request $request)
    {
        $classId = $request->class_id;
        $stream = $request->stream;

        $query = Student::query();

        if ($classId) {
            $query->where('class_id', $classId);
        }

        if ($stream) {
            $query->where('group_stream', $stream);
        }

        $students = $query->with('subjectSelections.subject', 'subjectSelections.subjectGroup')->get();
        $classes = SchoolClass::where('is_active', true)->get(['id', 'class', 'section']);

        // Calculate statistics
        $statistics = $this->calculateStatistics($students);

        // Get selection summaries by subject
        $subjectSummary = $this->getSubjectSelectionSummary($students);

        // Get selection summaries by group
        $groupSummary = $this->getGroupSelectionSummary($students);

        return Inertia::render('Principal/StudentSelections/Reports', compact(
            'statistics',
            'subjectSummary',
            'groupSummary',
            'classes',
            'students',
            'classId',
            'stream'
        ));
    }

    /**
     * Calculate selection statistics
     */
    private function calculateStatistics($students): array
    {
        $totalStudents = $students->count();
        $completedSelections = $students->filter(fn($s) => $s->subjectSelections->count() > 0)->count();

        return [
            'total_students' => $totalStudents,
            'completed_selections' => $completedSelections,
            'pending_selections' => $totalStudents - $completedSelections,
            'completion_percentage' => $totalStudents > 0 ? round(($completedSelections / $totalStudents) * 100, 2) : 0,
        ];
    }

    /**
     * Get subject selection summary
     */
    private function getSubjectSelectionSummary($students): array
    {
        $summary = [];

        foreach ($students as $student) {
            foreach ($student->subjectSelections as $selection) {
                $subjectId = $selection->subject_id;
                if (!isset($summary[$subjectId])) {
                    $summary[$subjectId] = [
                        'subject_name' => $selection->subject->subject_name,
                        'count' => 0,
                        'percentage' => 0,
                    ];
                }
                $summary[$subjectId]['count']++;
            }
        }

        // Calculate percentages
        $totalSelections = array_sum(array_column($summary, 'count'));
        foreach ($summary as &$item) {
            $item['percentage'] = $totalSelections > 0 ? round(($item['count'] / $totalSelections) * 100, 2) : 0;
        }

        return array_values($summary);
    }

    /**
     * Get group selection summary
     */
    private function getGroupSelectionSummary($students): array
    {
        $summary = [];

        foreach ($students as $student) {
            $groupSelections = $student->subjectSelections->groupBy('subject_group_id');

            foreach ($groupSelections as $groupId => $selections) {
                if (!isset($summary[$groupId])) {
                    $group = $selections->first()->subjectGroup;
                    $summary[$groupId] = [
                        'group_name' => $group->group_name,
                        'students_count' => 0,
                        'avg_subjects_selected' => 0,
                        'total_selections' => 0,
                    ];
                }

                $summary[$groupId]['students_count']++;
                $summary[$groupId]['total_selections'] += $selections->count();
            }
        }

        // Calculate averages
        foreach ($summary as &$item) {
            $item['avg_subjects_selected'] = $item['students_count'] > 0
                ? round($item['total_selections'] / $item['students_count'], 2)
                : 0;
        }

        return array_values($summary);
    }
}
