<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\{TrainingCourse, CourseEnrollment, CourseMaterial, Certification, AuditLog};
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingCoursesController extends Controller
{
    public function index(Request $request)
    {
        $enrolledCourses = auth()->user()->courseEnrollments()
            ->with(['course.instructor:id,name', 'completion'])
            ->when($request->search, fn($q) => $q->whereHas('course', fn($q) => $q->where('course_name', 'like', '%' . $request->search . '%')))
            ->when($request->status, fn($q) => $q->where('enrollment_status', $request->status))
            ->latest()
            ->paginate(15);

        $availableCourses = TrainingCourse::where('status', 'active')
            ->whereDoesntHave('enrollments', fn($q) => $q->where('teacher_id', auth()->id()))
            ->with('instructor:id,name')
            ->latest()
            ->get();

        return Inertia::render('Teacher/ProfessionalDevelopment/TrainingCourses/Index', compact('enrolledCourses', 'availableCourses'));
    }

    public function show(TrainingCourse $course)
    {
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('teacher_id', auth()->id())
            ->with('completion')
            ->first();

        $course->load([
            'instructor:id,name,email',
            'materials' => fn($q) => $q->orderBy('sequence_order'),
        ]);

        return Inertia::render('Teacher/ProfessionalDevelopment/TrainingCourses/Show', compact('course', 'enrollment'));
    }

    public function enroll(TrainingCourse $course)
    {
        $exists = CourseEnrollment::where('course_id', $course->id)
            ->where('teacher_id', auth()->id())
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'You are already enrolled in this course.']);
        }

        if ($course->max_participants) {
            $currentEnrollments = $course->enrollments()->where('enrollment_status', 'enrolled')->count();
            if ($currentEnrollments >= $course->max_participants) {
                return back()->withErrors(['error' => 'This course has reached maximum participant capacity.']);
            }
        }

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'teacher_id' => auth()->id(),
            'enrollment_status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        AuditLog::log('enroll_course', 'CourseEnrollment', $enrollment->id, null, [
            'course_id' => $course->id,
            'teacher_id' => auth()->id(),
        ]);

        return back()->with('success', 'You have been enrolled in this course.');
    }

    public function unenroll(CourseEnrollment $enrollment)
    {
        if ($enrollment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $oldValues = $enrollment->getAttributes();
        $enrollment->update(['enrollment_status' => 'withdrawn']);
        AuditLog::log('unenroll_course', 'CourseEnrollment', $enrollment->id, $oldValues, [
            'enrollment_status' => 'withdrawn',
        ]);

        return back()->with('success', 'You have been unenrolled from this course.');
    }

    public function viewProgress(CourseEnrollment $enrollment)
    {
        if ($enrollment->teacher_id !== auth()->id()) {
            return back()->withErrors(['error' => 'Unauthorized action.']);
        }

        $enrollment->load([
            'course.materials',
            'course.instructor:id,name',
            'completion',
        ]);

        return Inertia::render('Teacher/ProfessionalDevelopment/TrainingCourses/Progress', compact('enrollment'));
    }

    public function downloadMaterials(TrainingCourse $course)
    {
        $enrollment = CourseEnrollment::where('course_id', $course->id)
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        $materials = $course->materials()
            ->orderBy('sequence_order')
            ->get();

        return Inertia::render('Teacher/ProfessionalDevelopment/TrainingCourses/Materials', compact('course', 'materials', 'enrollment'));
    }

    public function downloadMaterial(CourseMaterial $material)
    {
        $enrollment = CourseEnrollment::where('course_id', $material->course_id)
            ->where('teacher_id', auth()->id())
            ->firstOrFail();

        if ($material->file_path) {
            return response()->download(storage_path('app/' . $material->file_path), $material->material_name);
        }

        return back()->withErrors(['error' => 'File not found.']);
    }
}
