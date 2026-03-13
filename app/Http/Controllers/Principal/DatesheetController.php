<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{StudentDatesheet, SchoolClass};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DatesheetController extends Controller
{
    public function index(Request $request)
    {
        $academicYear = $request->get('academic_year', config('school.current_academic_year', '2025-26'));
        $examPeriod = $request->get('exam_period', '');
        $className = $request->get('class_name', '');

        $query = StudentDatesheet::query()
            ->where('academic_year', $academicYear)
            ->orderBy('exam_period')
            ->orderBy('exam_date');

        if ($examPeriod) {
            $query->where('exam_period', $examPeriod);
        }

        if ($className) {
            $query->where('class_name', 'like', "%{$className}%");
        }

        $datesheets = $query->paginate(20);

        // Get distinct exam periods and class names for filters
        $examPeriods = StudentDatesheet::where('academic_year', $academicYear)
            ->distinct('exam_period')
            ->orderBy('exam_period')
            ->pluck('exam_period');

        $classNames = StudentDatesheet::where('academic_year', $academicYear)
            ->distinct('class_name')
            ->orderBy('class_name')
            ->pluck('class_name');

        return Inertia::render('Principal/Datesheets/Index', [
            'datesheets' => $datesheets,
            'examPeriods' => $examPeriods,
            'classNames' => $classNames,
            'academicYear' => $academicYear,
            'selectedExamPeriod' => $examPeriod,
            'selectedClassName' => $className,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:30',
            'subject_name' => 'required|string|max:120',
            'exam_date' => 'required|date',
            'exam_time' => 'nullable|string|max:30',
            'room_no' => 'nullable|string|max:30',
            'total_marks' => 'required|numeric|min:1|max:999',
            'exam_period' => 'required|string|max:50',
            'academic_year' => 'required|string|max:10',
        ]);

        StudentDatesheet::create($validated);

        return redirect()->route('principal.datesheets.index')
            ->with('success', 'Datesheet entry created successfully');
    }

    public function update(Request $request, StudentDatesheet $datesheet)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:30',
            'subject_name' => 'required|string|max:120',
            'exam_date' => 'required|date',
            'exam_time' => 'nullable|string|max:30',
            'room_no' => 'nullable|string|max:30',
            'total_marks' => 'required|numeric|min:1|max:999',
            'exam_period' => 'required|string|max:50',
            'academic_year' => 'required|string|max:10',
        ]);

        $datesheet->update($validated);

        return redirect()->route('principal.datesheets.index')
            ->with('success', 'Datesheet entry updated successfully');
    }

    public function destroy(StudentDatesheet $datesheet)
    {
        $datesheet->delete();

        return redirect()->route('principal.datesheets.index')
            ->with('success', 'Datesheet entry deleted successfully');
    }
}
