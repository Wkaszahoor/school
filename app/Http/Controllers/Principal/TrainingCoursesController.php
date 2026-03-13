<?php

namespace App\Http\Controllers\Principal;

use App\Http\Controllers\Controller;
use App\Models\{TrainingCourse, CourseEnrollment, CourseMaterial, Certification, AuditLog, User};
use Illuminate\Http\Request;
use Inertia\Inertia;

class TrainingCoursesController extends Controller
{
    public function index(Request $request)
    {
        $courses = TrainingCourse::with(['instructor:id,name,email', 'enrollments', 'completions'])
            ->when($request->search, fn($q) => $q->where('course_name', 'like', '%' . $request->search . '%')
                ->orWhere('course_code', 'like', '%' . $request->search . '%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->type, fn($q) => $q->where('course_type', $request->type))
            ->when($request->level, fn($q) => $q->where('level', $request->level))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Index', compact('courses'));
    }

    public function show(TrainingCourse $course)
    {
        $course->load([
            'instructor:id,name,email',
            'materials' => fn($q) => $q->orderBy('sequence_order'),
            'enrollments.teacher:id,name,email',
            'enrollments.completion',
        ]);

        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Show', compact('course'));
    }

    public function create()
    {
        $instructors = User::where('role', 'teacher')->get(['id', 'name', 'email']);
        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Create', compact('instructors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_code' => 'required|string|unique:training_courses,course_code',
            'course_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructor_id' => 'nullable|exists:users,id',
            'course_type' => 'required|in:workshop,certification,seminar,online,conference',
            'level' => 'required|in:beginner,intermediate,advanced',
            'objectives' => 'nullable|string',
            'duration_hours' => 'required|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,completed,cancelled',
            'cost' => 'required|numeric|min:0',
        ]);

        $course = TrainingCourse::create($data);
        AuditLog::log('create', 'TrainingCourse', $course->id, null, $data);

        return redirect()->route('principal.professional-development.training-courses.show', $course->id)
            ->with('success', 'Training course created successfully.');
    }

    public function edit(TrainingCourse $course)
    {
        $instructors = User::where('role', 'teacher')->get(['id', 'name', 'email']);
        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Create', compact('course', 'instructors'));
    }

    public function update(Request $request, TrainingCourse $course)
    {
        $data = $request->validate([
            'course_code' => 'required|string|unique:training_courses,course_code,' . $course->id,
            'course_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructor_id' => 'nullable|exists:users,id',
            'course_type' => 'required|in:workshop,certification,seminar,online,conference',
            'level' => 'required|in:beginner,intermediate,advanced',
            'objectives' => 'nullable|string',
            'duration_hours' => 'required|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:draft,active,completed,cancelled',
            'cost' => 'required|numeric|min:0',
        ]);

        $oldValues = $course->getAttributes();
        $course->update($data);
        AuditLog::log('update', 'TrainingCourse', $course->id, $oldValues, $data);

        return back()->with('success', 'Training course updated successfully.');
    }

    public function destroy(TrainingCourse $course)
    {
        $oldValues = $course->getAttributes();
        $course->delete();
        AuditLog::log('delete', 'TrainingCourse', $course->id, $oldValues, null);

        return back()->with('success', 'Training course deleted successfully.');
    }

    public function enrollTeacher(Request $request, TrainingCourse $course)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
        ]);

        $exists = CourseEnrollment::where('course_id', $course->id)
            ->where('teacher_id', $data['teacher_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Teacher is already enrolled in this course.']);
        }

        $enrollment = CourseEnrollment::create([
            'course_id' => $course->id,
            'teacher_id' => $data['teacher_id'],
            'enrollment_status' => 'pending',
        ]);

        AuditLog::log('enroll_teacher', 'CourseEnrollment', $enrollment->id, null, $data);

        return back()->with('success', 'Teacher enrolled successfully.');
    }

    public function viewEnrollments(TrainingCourse $course)
    {
        $course->load(['enrollments.teacher:id,name,email', 'enrollments.completion']);

        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Show', compact('course'));
    }

    public function downloadMaterials(TrainingCourse $course)
    {
        $course->load(['materials' => fn($q) => $q->orderBy('sequence_order')]);

        return Inertia::render('Principal/ProfessionalDevelopment/TrainingCourses/Show', compact('course'));
    }
}
