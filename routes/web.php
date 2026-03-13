<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Principal;
use App\Http\Controllers\Teacher;
use App\Http\Controllers\Doctor;
use App\Http\Controllers\Receptionist;
use App\Http\Controllers\Helper;
use App\Http\Controllers\Inventory;
use App\Http\Controllers\NoticeReadController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// ─── Public ─────────────────────────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'))->name('home');
Route::get('/login', [LoginController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [LoginController::class, 'login'])->name('login.post')->middleware('guest');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// ─── Test Endpoint ──────────────────────────────────────────────────────────

// ─── Admin ───────────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('students', Admin\StudentsController::class);
    Route::get('students/{student}/pdf', [Admin\StudentsController::class, 'pdf'])->name('students.pdf');
    Route::get('import-students', [Admin\StudentImportController::class, 'index'])->name('import-students.index');
    Route::post('import-students', [Admin\StudentImportController::class, 'import'])->name('import-students.store');
    Route::post('bulk-import-students', [Admin\BulkStudentImportController::class, 'importFromJson'])->name('bulk-import-students');
    Route::resource('teachers', Admin\TeachersController::class);
    Route::get('import-teachers', [Admin\TeacherImportController::class, 'index'])->name('import-teachers.index');
    Route::post('import-teachers', [Admin\TeacherImportController::class, 'import'])->name('import-teachers.store');
    Route::post('teachers/{teacher}/devices', [Principal\TeacherDevicesController::class, 'store'])->name('teachers.devices.store');
    Route::put('teachers/devices/{device}', [Principal\TeacherDevicesController::class, 'update'])->name('teachers.devices.update');
    Route::delete('teachers/devices/{device}', [Principal\TeacherDevicesController::class, 'destroy'])->name('teachers.devices.destroy');
    Route::resource('users', Admin\StaffUsersController::class);
    Route::post('users/{user}/reset-password', [Admin\StaffUsersController::class, 'resetPassword'])->name('users.reset-password');

    Route::get('classes', [Admin\ClassesController::class, 'index'])->name('classes.index');
    Route::get('classes/{class}', [Admin\ClassesController::class, 'show'])->name('classes.show');
    Route::post('classes', [Admin\ClassesController::class, 'store'])->name('classes.store');
    Route::put('classes/{class}', [Admin\ClassesController::class, 'update'])->name('classes.update');
    Route::delete('classes/{class}', [Admin\ClassesController::class, 'destroy'])->name('classes.destroy');
    Route::get('classes/{class}/subjects', [Admin\ClassesController::class, 'subjects'])->name('classes.subjects');
    Route::post('classes/{class}/subjects', [Admin\ClassesController::class, 'syncSubjects'])->name('classes.subjects.sync');

    // Subject Management (Classes 9-12 with groups)
    Route::get('subject-management', [Admin\SubjectManagementController::class, 'index'])->name('subject-management.index');
    Route::get('subject-management/class/{class}', [Admin\SubjectManagementController::class, 'showClass'])->name('subject-management.class');
    Route::put('subject-management/group/{group}/subjects', [Admin\SubjectManagementController::class, 'updateGroupSubjects'])->name('subject-management.group.update-subjects');
    Route::post('subject-management/group', [Admin\SubjectManagementController::class, 'storeGroup'])->name('subject-management.group.store');
    Route::delete('subject-management/group/{group}', [Admin\SubjectManagementController::class, 'destroyGroup'])->name('subject-management.group.destroy');
    Route::get('subject-management/subjects', [Admin\SubjectManagementController::class, 'subjectsIndex'])->name('subject-management.subjects.index');
    Route::post('subject-management/subject', [Admin\SubjectManagementController::class, 'storeSubject'])->name('subject-management.subject.store');
    Route::delete('subject-management/subject/{subject}', [Admin\SubjectManagementController::class, 'destroySubject'])->name('subject-management.subject.destroy');

    Route::get('university-students', [Admin\UniversityStudentsController::class, 'index'])->name('university-students.index');
    Route::get('university-students/{student}', [Admin\UniversityStudentsController::class, 'show'])->name('university-students.show');

    Route::get('audit-logs', [Admin\AuditLogsController::class, 'index'])->name('audit-logs');

    // Holiday Management
    Route::resource('holidays', Admin\HolidayController::class);
    Route::get('holidays/calendar/view', [Admin\HolidayController::class, 'calendar'])->name('holidays.calendar');
    Route::resource('holiday-types', Admin\HolidayTypeController::class);
});

// ─── Principal ───────────────────────────────────────────────────────────────
Route::prefix('principal')->name('principal.')->middleware(['auth', 'role:principal,admin'])->group(function () {
    Route::get('dashboard', [Principal\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('students', Principal\StudentsController::class);
    Route::get('students/{student}/pdf', [Principal\StudentsController::class, 'pdf'])->name('students.pdf');
    Route::post('students/bulk-assign', [Principal\StudentsController::class, 'bulkAssign'])->name('students.bulk-assign');

    Route::get('attendance-report', [Principal\AttendanceReportController::class, 'index'])->name('attendance-report.index');
    Route::get('attendance-performance', [Principal\AttendancePerformanceController::class, 'index'])->name('attendance-performance.index');

    Route::get('leave', [Principal\LeaveController::class, 'index'])->name('leave.index');
    Route::post('leave/{leave}/approve', [Principal\LeaveController::class, 'approve'])->name('leave.approve');
    Route::post('leave/{leave}/reject', [Principal\LeaveController::class, 'reject'])->name('leave.reject');

    Route::get('lesson-plans', [Principal\LessonPlansController::class, 'index'])->name('lesson-plans.index');
    Route::get('lesson-plans/{lessonPlan}', [Principal\LessonPlansController::class, 'show'])->name('lesson-plans.show');
    Route::post('lesson-plans/{lessonPlan}/approve', [Principal\LessonPlansController::class, 'approve'])->name('lesson-plans.approve');
    Route::post('lesson-plans/{lessonPlan}/reject', [Principal\LessonPlansController::class, 'reject'])->name('lesson-plans.reject');

    Route::get('results', [Principal\ResultsController::class, 'index'])->name('results.index');
    Route::get('results/report-cards', [Principal\ResultsController::class, 'reportCards'])->name('results.report-cards');
    Route::get('results/export', [Principal\ResultsController::class, 'export'])->name('results.export');
    Route::post('results/{result}/approve', [Principal\ResultsController::class, 'approve'])->name('results.approve');
    Route::post('results/{result}/reject', [Principal\ResultsController::class, 'reject'])->name('results.reject');
    Route::post('results/bulk-approve', [Principal\ResultsController::class, 'bulkApprove'])->name('results.bulk-approve');
    Route::post('results/{result}/lock', [Principal\ResultsController::class, 'lock'])->name('results.lock');
    Route::post('results/{result}/unlock', [Principal\ResultsController::class, 'unlock'])->name('results.unlock');

    Route::get('discipline', [Principal\DisciplineController::class, 'index'])->name('discipline.index');
    Route::post('discipline', [Principal\DisciplineController::class, 'store'])->name('discipline.store');
    Route::put('discipline/{discipline}', [Principal\DisciplineController::class, 'update'])->name('discipline.update');

    Route::resource('notices', Principal\NoticesController::class)->except(['edit', 'update', 'show', 'create']);
    Route::post('notices/{notice}/toggle', [Principal\NoticesController::class, 'toggle'])->name('notices.toggle');

    Route::get('teacher-assignments', [Principal\TeacherAssignmentsController::class, 'index'])->name('teacher-assignments.index');
    Route::post('teacher-assignments', [Principal\TeacherAssignmentsController::class, 'store'])->name('teacher-assignments.store');
    Route::put('teacher-assignments/{assignment}', [Principal\TeacherAssignmentsController::class, 'update'])->name('teacher-assignments.update');
    Route::delete('teacher-assignments/{assignment}', [Principal\TeacherAssignmentsController::class, 'destroy'])->name('teacher-assignments.destroy');

    Route::get('subject-groups', [Principal\SubjectGroupsController::class, 'index'])->name('subject-groups.index');
    Route::get('subject-groups/{group}', [Principal\SubjectGroupsController::class, 'show'])->name('subject-groups.show');
    Route::post('subject-groups', [Principal\SubjectGroupsController::class, 'store'])->name('subject-groups.store');
    Route::put('subject-groups/{group}', [Principal\SubjectGroupsController::class, 'update'])->name('subject-groups.update');
    Route::delete('subject-groups/{group}', [Principal\SubjectGroupsController::class, 'destroy'])->name('subject-groups.destroy');
    Route::post('subject-groups/{group}/subjects', [Principal\SubjectGroupsController::class, 'addSubject'])->name('subject-groups.add-subject');
    Route::delete('subject-groups/{group}/subjects/{subject}', [Principal\SubjectGroupsController::class, 'removeSubject'])->name('subject-groups.remove-subject');
    Route::put('subject-groups/{group}/subjects/{subject}/type', [Principal\SubjectGroupsController::class, 'updateSubjectType'])->name('subject-groups.update-subject-type');
    Route::post('subject-groups/{group}/students', [Principal\SubjectGroupsController::class, 'addStudent'])->name('subject-groups.add-student');
    Route::delete('subject-groups/{group}/students/{student}', [Principal\SubjectGroupsController::class, 'removeStudent'])->name('subject-groups.remove-student');
    Route::put('students/{student}/stream', [Principal\SubjectGroupsController::class, 'updateStudentStream'])->name('students.update-stream');
    Route::post('subject-groups/{group}/teachers', [Principal\SubjectGroupsController::class, 'addTeacher'])->name('subject-groups.add-teacher');
    Route::delete('subject-groups/{group}/teachers/{teacher}', [Principal\SubjectGroupsController::class, 'removeTeacher'])->name('subject-groups.remove-teacher');

    Route::get('attendance-criteria', [Principal\AttendanceCriteriaController::class, 'index'])->name('attendance-criteria.index');
    Route::post('attendance-criteria', [Principal\AttendanceCriteriaController::class, 'store'])->name('attendance-criteria.store');
    Route::put('attendance-criteria/{criterion}', [Principal\AttendanceCriteriaController::class, 'update'])->name('attendance-criteria.update');
    Route::delete('attendance-criteria/{criterion}', [Principal\AttendanceCriteriaController::class, 'destroy'])->name('attendance-criteria.destroy');

    Route::get('datesheets', [Principal\DatesheetController::class, 'index'])->name('datesheets.index');
    Route::post('datesheets', [Principal\DatesheetController::class, 'store'])->name('datesheets.store');
    Route::put('datesheets/{datesheet}', [Principal\DatesheetController::class, 'update'])->name('datesheets.update');
    Route::delete('datesheets/{datesheet}', [Principal\DatesheetController::class, 'destroy'])->name('datesheets.destroy');

    Route::get('admission-cards', [Principal\AdmissionCardsController::class, 'index'])->name('admission-cards.index');
    Route::post('admission-cards/generate', [Principal\AdmissionCardsController::class, 'generate'])->name('admission-cards.generate');
    Route::get('admission-cards/{card}/download', [Principal\AdmissionCardsController::class, 'download'])->name('admission-cards.download');
    Route::post('admission-cards/bulk-download', [Principal\AdmissionCardsController::class, 'bulkDownload'])->name('admission-cards.bulk-download');

    // Timetable Routes
    Route::resource('timetables', Principal\TimetablesController::class);
    Route::post('timetables/{timetable}/generate', [Principal\TimetablesController::class, 'generate'])->name('timetables.generate');
    Route::post('timetables/{timetable}/publish', [Principal\TimetablesController::class, 'publish'])->name('timetables.publish');
    Route::post('timetables/{timetable}/archive', [Principal\TimetablesController::class, 'archive'])->name('timetables.archive');

    // Timetable Views (by class, teacher, room)
    Route::get('timetables/{timetable}/by-class/{class}', [Principal\TimetableViewController::class, 'byClass'])->name('timetables.by-class');
    Route::get('timetables/{timetable}/by-teacher/{teacher}', [Principal\TimetableViewController::class, 'byTeacher'])->name('timetables.by-teacher');
    Route::get('timetables/{timetable}/by-room/{room}', [Principal\TimetableViewController::class, 'byRoom'])->name('timetables.by-room');
    Route::get('timetables/{timetable}/export/pdf', [Principal\TimetableViewController::class, 'exportPdf'])->name('timetables.export-pdf');
    Route::get('timetables/{timetable}/export/excel', [Principal\TimetableViewController::class, 'exportExcel'])->name('timetables.export-excel');

    // Timetable Entry Management (drag-drop, manual editing)
    Route::put('timetable-entries/{entry}', [Principal\TimetableEntryController::class, 'update'])->name('timetable-entries.update');
    Route::post('timetable-entries/{entry}/lock', [Principal\TimetableEntryController::class, 'lock'])->name('timetable-entries.lock');
    Route::post('timetable-entries/{entry}/unlock', [Principal\TimetableEntryController::class, 'unlock'])->name('timetable-entries.unlock');
    Route::delete('timetable-entries/{entry}', [Principal\TimetableEntryController::class, 'destroy'])->name('timetable-entries.destroy');
    Route::get('timetable-entries/{entry}/options', [Principal\TimetableEntryController::class, 'getAvailableOptions'])->name('timetable-entries.options');

    // Time Slots Management
    Route::get('time-slots', [Principal\TimeSlotsController::class, 'index'])->name('time-slots.index');
    Route::post('time-slots', [Principal\TimeSlotsController::class, 'store'])->name('time-slots.store');
    Route::put('time-slots/{slot}', [Principal\TimeSlotsController::class, 'update'])->name('time-slots.update');
    Route::delete('time-slots/{slot}', [Principal\TimeSlotsController::class, 'destroy'])->name('time-slots.destroy');

    // Room Configuration
    Route::get('rooms', [Principal\RoomsController::class, 'index'])->name('rooms.index');
    Route::post('rooms', [Principal\RoomsController::class, 'store'])->name('rooms.store');
    Route::put('rooms/{room}', [Principal\RoomsController::class, 'update'])->name('rooms.update');
    Route::delete('rooms/{room}', [Principal\RoomsController::class, 'destroy'])->name('rooms.destroy');

    // Teacher Availabilities
    Route::get('teacher-availabilities', [Principal\TeacherAvailabilitiesController::class, 'index'])->name('teacher-availabilities.index');
    Route::post('teacher-availabilities', [Principal\TeacherAvailabilitiesController::class, 'store'])->name('teacher-availabilities.store');
    Route::put('teacher-availabilities/{availability}', [Principal\TeacherAvailabilitiesController::class, 'update'])->name('teacher-availabilities.update');
    Route::delete('teacher-availabilities/{availability}', [Principal\TeacherAvailabilitiesController::class, 'destroy'])->name('teacher-availabilities.destroy');

    // Academic Calendar Routes
    Route::resource('academic-calendars', Principal\AcademicCalendarsController::class);
    Route::get('academic-calendars/calendar/view', [Principal\AcademicCalendarsController::class, 'calendar'])->name('academic-calendars.calendar');

    // Teacher Management Routes
    Route::get('teachers', [Principal\TeachersController::class, 'index'])->name('teachers.index');
    Route::get('teachers/{teacher}', [Principal\TeachersController::class, 'show'])->name('teachers.show');
    Route::post('teachers/{teacher}/devices', [Principal\TeacherDevicesController::class, 'store'])->name('teachers.devices.store');
    Route::put('teachers/devices/{device}', [Principal\TeacherDevicesController::class, 'update'])->name('teachers.devices.update');
    Route::delete('teachers/devices/{device}', [Principal\TeacherDevicesController::class, 'destroy'])->name('teachers.devices.destroy');

    // Student Subject Selections Routes
    Route::get('student-selections', [Principal\StudentSelectionsController::class, 'index'])->name('student-selections.index');
    Route::get('student-selections/{student}', [Principal\StudentSelectionsController::class, 'show'])->name('student-selections.show');
    Route::get('student-selections-reports', [Principal\StudentSelectionsController::class, 'reports'])->name('student-selections.reports');

    // Professional Development & Certification Routes
    Route::prefix('professional-development')->name('professional-development.')->group(function () {
        // Training Courses
        Route::get('training-courses', [Principal\TrainingCoursesController::class, 'index'])->name('training-courses.index');
        Route::get('training-courses/create', [Principal\TrainingCoursesController::class, 'create'])->name('training-courses.create');
        Route::post('training-courses', [Principal\TrainingCoursesController::class, 'store'])->name('training-courses.store');
        Route::get('training-courses/{course}', [Principal\TrainingCoursesController::class, 'show'])->name('training-courses.show');
        Route::get('training-courses/{course}/edit', [Principal\TrainingCoursesController::class, 'edit'])->name('training-courses.edit');
        Route::put('training-courses/{course}', [Principal\TrainingCoursesController::class, 'update'])->name('training-courses.update');
        Route::delete('training-courses/{course}', [Principal\TrainingCoursesController::class, 'destroy'])->name('training-courses.destroy');
        Route::post('training-courses/{course}/enroll-teacher', [Principal\TrainingCoursesController::class, 'enrollTeacher'])->name('training-courses.enroll-teacher');
        Route::get('training-courses/{course}/enrollments', [Principal\TrainingCoursesController::class, 'viewEnrollments'])->name('training-courses.enrollments');
        Route::get('training-courses/{course}/materials', [Principal\TrainingCoursesController::class, 'downloadMaterials'])->name('training-courses.materials');

        // PBL Assignments
        Route::get('pbl-assignments', [Principal\PBLAssignmentsController::class, 'index'])->name('pbl-assignments.index');
        Route::get('pbl-assignments/create', [Principal\PBLAssignmentsController::class, 'create'])->name('pbl-assignments.create');
        Route::post('pbl-assignments', [Principal\PBLAssignmentsController::class, 'store'])->name('pbl-assignments.store');
        Route::get('pbl-assignments/{assignment}', [Principal\PBLAssignmentsController::class, 'show'])->name('pbl-assignments.show');
        Route::get('pbl-assignments/{assignment}/edit', [Principal\PBLAssignmentsController::class, 'edit'])->name('pbl-assignments.edit');
        Route::put('pbl-assignments/{assignment}', [Principal\PBLAssignmentsController::class, 'update'])->name('pbl-assignments.update');
        Route::delete('pbl-assignments/{assignment}', [Principal\PBLAssignmentsController::class, 'destroy'])->name('pbl-assignments.destroy');
        Route::get('pbl-assignments/{assignment}/submissions', [Principal\PBLAssignmentsController::class, 'viewSubmissions'])->name('pbl-assignments.submissions');
        Route::post('pbl-assignments/{assignment}/groups', [Principal\PBLAssignmentsController::class, 'createGroup'])->name('pbl-assignments.create-group');
        Route::post('pbl-submissions/{submission}/evaluate', [Principal\PBLAssignmentsController::class, 'evaluateSubmission'])->name('pbl-submissions.evaluate');

        // Certifications
        Route::get('certifications', [Principal\CertificationsController::class, 'index'])->name('certifications.index');
        Route::get('certifications/{certification}', [Principal\CertificationsController::class, 'show'])->name('certifications.show');
        Route::get('certifications/{certification}/download', [Principal\CertificationsController::class, 'downloadCertificate'])->name('certifications.download');
        Route::post('certifications/{certification}/revoke', [Principal\CertificationsController::class, 'revokeCertificate'])->name('certifications.revoke');
        Route::post('certifications/bulk-download', [Principal\CertificationsController::class, 'bulkDownloadCertificates'])->name('certifications.bulk-download');
        Route::get('certifications/report', [Principal\CertificationsController::class, 'generateReport'])->name('certifications.report');
    });
});

// ─── Teacher ─────────────────────────────────────────────────────────────────
Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('dashboard', [Teacher\DashboardController::class, 'index'])->name('dashboard');

    Route::get('attendance', [Teacher\AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('attendance', [Teacher\AttendanceController::class, 'store'])->name('attendance.store');
    Route::post('attendance/report-absence', [Teacher\AttendanceController::class, 'reportAbsence'])->name('attendance.report-absence');
    Route::get('attendance/report', [Teacher\AttendanceController::class, 'report'])->name('attendance.report');
    Route::get('attendance/report/pdf', [Teacher\AttendanceController::class, 'reportPdf'])->name('attendance.report.pdf');

    Route::get('results', [Teacher\ResultsController::class, 'index'])->name('results.index');
    Route::get('results/create', [Teacher\ResultsController::class, 'create'])->name('results.create');
    Route::post('results', [Teacher\ResultsController::class, 'store'])->name('results.store');
    Route::post('results/class-teacher-bulk-approve', [Teacher\ResultsController::class, 'classTeacherBulkApprove'])->name('results.class-teacher-bulk-approve');
    Route::post('results/{result}/class-teacher-approve', [Teacher\ResultsController::class, 'classTeacherApprove'])->name('results.class-teacher-approve');
    Route::post('results/{result}/class-teacher-reject', [Teacher\ResultsController::class, 'classTeacherReject'])->name('results.class-teacher-reject');

    Route::get('lesson-plans', [Teacher\LessonPlansController::class, 'index'])->name('lesson-plans.index');
    Route::get('lesson-plans/create', [Teacher\LessonPlansController::class, 'create'])->name('lesson-plans.create');
    Route::post('lesson-plans', [Teacher\LessonPlansController::class, 'store'])->name('lesson-plans.store');
    Route::get('lesson-plans/{lessonPlan}/edit', [Teacher\LessonPlansController::class, 'edit'])->name('lesson-plans.edit');
    Route::put('lesson-plans/{lessonPlan}', [Teacher\LessonPlansController::class, 'update'])->name('lesson-plans.update');

    Route::get('attendance-criteria', [Teacher\AttendanceCriteriaController::class, 'index'])->name('attendance-criteria.index');
    Route::post('attendance-criteria', [Teacher\AttendanceCriteriaController::class, 'store'])->name('attendance-criteria.store');
    Route::delete('attendance-criteria', [Teacher\AttendanceCriteriaController::class, 'destroy'])->name('attendance-criteria.destroy');

    Route::get('profile', [Teacher\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile/name', [Teacher\ProfileController::class, 'updateName'])->name('profile.update-name');
    Route::post('profile/password', [Teacher\ProfileController::class, 'updatePassword'])->name('profile.update-password');

    Route::post('teacher-reports', [Teacher\TeacherReportController::class, 'store'])->name('teacher-reports.store');
    Route::delete('teacher-reports/{report}', [Teacher\TeacherReportController::class, 'destroy'])->name('teacher-reports.destroy');

    Route::get('class-management', [Teacher\ClassManagementController::class, 'index'])->name('class-management.index');

    // Professional Development & Certification Routes
    Route::prefix('professional-development')->name('professional-development.')->group(function () {
        // Training Courses
        Route::get('training-courses', [Teacher\TrainingCoursesController::class, 'index'])->name('training-courses.index');
        Route::get('training-courses/{course}', [Teacher\TrainingCoursesController::class, 'show'])->name('training-courses.show');
        Route::post('training-courses/{course}/enroll', [Teacher\TrainingCoursesController::class, 'enroll'])->name('training-courses.enroll');
        Route::post('training-courses/{enrollment}/unenroll', [Teacher\TrainingCoursesController::class, 'unenroll'])->name('training-courses.unenroll');
        Route::get('training-courses/{enrollment}/progress', [Teacher\TrainingCoursesController::class, 'viewProgress'])->name('training-courses.progress');
        Route::get('training-courses/{course}/materials', [Teacher\TrainingCoursesController::class, 'downloadMaterials'])->name('training-courses.materials');
        Route::get('materials/{material}/download', [Teacher\TrainingCoursesController::class, 'downloadMaterial'])->name('materials.download');

        // PBL Assignments
        Route::get('pbl-assignments', [Teacher\PBLAssignmentsController::class, 'index'])->name('pbl-assignments.index');
        Route::get('pbl-assignments/create', [Teacher\PBLAssignmentsController::class, 'create'])->name('pbl-assignments.create');
        Route::post('pbl-assignments', [Teacher\PBLAssignmentsController::class, 'store'])->name('pbl-assignments.store');
        Route::get('pbl-assignments/{assignment}', [Teacher\PBLAssignmentsController::class, 'show'])->name('pbl-assignments.show');
        Route::get('pbl-assignments/{assignment}/edit', [Teacher\PBLAssignmentsController::class, 'edit'])->name('pbl-assignments.edit');
        Route::put('pbl-assignments/{assignment}', [Teacher\PBLAssignmentsController::class, 'update'])->name('pbl-assignments.update');
        Route::delete('pbl-assignments/{assignment}', [Teacher\PBLAssignmentsController::class, 'destroy'])->name('pbl-assignments.destroy');
        Route::post('pbl-assignments/{assignment}/groups', [Teacher\PBLAssignmentsController::class, 'storeGroup'])->name('pbl-assignments.store-group');
        Route::get('pbl-assignments/{assignment}/submissions', [Teacher\PBLAssignmentsController::class, 'viewSubmissions'])->name('pbl-assignments.submissions');
        Route::post('pbl-submissions/{submission}/evaluate', [Teacher\PBLAssignmentsController::class, 'evaluateSubmission'])->name('pbl-submissions.evaluate');
        Route::put('pbl-evaluations/{evaluation}/feedback', [Teacher\PBLAssignmentsController::class, 'provideFeedback'])->name('pbl-evaluations.feedback');

        // Teaching Resources
        Route::get('resources', [Teacher\MyResourcesController::class, 'index'])->name('resources.index');
        Route::get('resources/create', [Teacher\MyResourcesController::class, 'create'])->name('resources.create');
        Route::post('resources', [Teacher\MyResourcesController::class, 'store'])->name('resources.store');
        Route::get('resources/{resource}', [Teacher\MyResourcesController::class, 'show'])->name('resources.show');
        Route::get('resources/{resource}/edit', [Teacher\MyResourcesController::class, 'edit'])->name('resources.edit');
        Route::put('resources/{resource}', [Teacher\MyResourcesController::class, 'update'])->name('resources.update');
        Route::delete('resources/{resource}', [Teacher\MyResourcesController::class, 'destroy'])->name('resources.destroy');
        Route::post('resources/{resource}/download', [Teacher\MyResourcesController::class, 'downloadResource'])->name('resources.download');
        Route::post('resources/search', [Teacher\MyResourcesController::class, 'search'])->name('resources.search');

        // Teacher Certifications (view only)
        Route::get('certifications', [Teacher\CertificationsController::class, 'index'])->name('certifications.index');
        Route::get('certifications/{certification}/download', [Teacher\CertificationsController::class, 'downloadCertificate'])->name('certifications.download');
    });
});

// ─── Doctor ──────────────────────────────────────────────────────────────────
Route::prefix('doctor')->name('doctor.')->middleware(['auth', 'role:doctor'])->group(function () {
    Route::get('dashboard', [Doctor\DashboardController::class, 'index'])->name('dashboard');
    Route::get('records', [Doctor\DashboardController::class, 'records'])->name('records');
    Route::post('records', [Doctor\DashboardController::class, 'store'])->name('records.store');
});

// ─── Receptionist ────────────────────────────────────────────────────────────
Route::prefix('receptionist')->name('receptionist.')->middleware(['auth', 'role:receptionist'])->group(function () {
    Route::get('dashboard', [Receptionist\DashboardController::class, 'index'])->name('dashboard');
    Route::get('students', [Receptionist\DashboardController::class, 'students'])->name('students');
    Route::get('students/create', [Receptionist\DashboardController::class, 'createStudent'])->name('students.create');
    Route::post('students', [Receptionist\DashboardController::class, 'storeStudent'])->name('students.store');
    Route::get('import-students', [Receptionist\StudentImportController::class, 'index'])->name('import-students.index');
    Route::post('import-students', [Receptionist\StudentImportController::class, 'import'])->name('import-students.store');
});

// ─── Principal Helper ────────────────────────────────────────────────────────
Route::prefix('helper')->name('helper.')->middleware(['auth', 'role:principal_helper'])->group(function () {
    Route::get('dashboard', [Helper\DashboardController::class, 'index'])->name('dashboard');
    Route::get('students', [Helper\DashboardController::class, 'students'])->name('students');
    Route::post('students/{student}/assign-group', [Helper\DashboardController::class, 'assignGroup'])->name('students.assign-group');
});

// ─── Inventory ───────────────────────────────────────────────────────────────
Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'role:inventory_manager,admin'])->group(function () {
    Route::get('dashboard', [Inventory\DashboardController::class, 'index'])->name('dashboard');
    Route::get('items', [Inventory\DashboardController::class, 'items'])->name('items');
    Route::post('items', [Inventory\DashboardController::class, 'storeItem'])->name('items.store');
    Route::post('stock-in', [Inventory\DashboardController::class, 'stockIn'])->name('stock-in');
    Route::post('stock-out', [Inventory\DashboardController::class, 'stockOut'])->name('stock-out');
});

// ─── Shared Notice Routes (all authenticated users) ────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('notices/my', [NoticeReadController::class, 'myNotices'])->name('notices.my');
    Route::post('notices/{notice}/read', [NoticeReadController::class, 'markRead'])->name('notices.mark-read');
    Route::post('notices/mark-all-read', [NoticeReadController::class, 'markAllRead'])->name('notices.mark-all-read');
});

// ─── Global Search Routes (principal and teachers) ──────────────────────────────
Route::middleware(['auth', 'role:principal,teacher'])->group(function () {
    Route::get('search', [\App\Http\Controllers\SearchController::class, 'global'])->name('search.global');
});

// ─── Student Routes ──────────────────────────────────────────────────────────────
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('subject-selection', [\App\Http\Controllers\Student\SubjectSelectionController::class, 'index'])->name('subject-selection.index');
    Route::post('subject-selection', [\App\Http\Controllers\Student\SubjectSelectionController::class, 'store'])->name('subject-selection.store');
});

// ─── Shared To-Do Routes (principal and teachers) ────────────────────────────────
Route::middleware(['auth', 'role:principal,teacher,admin'])->group(function () {
    Route::get('todos/pending-count', [\App\Http\Controllers\TodoController::class, 'pendingCount'])->name('todos.pending-count');
    Route::get('todos/pending', [\App\Http\Controllers\TodoController::class, 'getPending'])->name('todos.pending');
    Route::get('todos', [\App\Http\Controllers\TodoController::class, 'index'])->name('todos.index');
    Route::get('todos/create', [\App\Http\Controllers\TodoController::class, 'create'])->name('todos.create');
    Route::post('todos', [\App\Http\Controllers\TodoController::class, 'store'])->name('todos.store');
    Route::get('todos/{todo}/edit', [\App\Http\Controllers\TodoController::class, 'edit'])->name('todos.edit');
    Route::patch('todos/{todo}', [\App\Http\Controllers\TodoController::class, 'update'])->name('todos.update');
    Route::delete('todos/{todo}', [\App\Http\Controllers\TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('todos/{todo}/mark-complete', [\App\Http\Controllers\TodoController::class, 'markComplete'])->name('todos.mark-complete');
});

// ─── Chat Routes (all authenticated users) ────────────────────────────────────────
Route::middleware('auth')->prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Chat\InboxController::class, 'index'])->name('index');
    Route::get('/unread', [\App\Http\Controllers\Chat\InboxController::class, 'unreadCount'])->name('unread');
    Route::get('/users', [\App\Http\Controllers\Chat\InboxController::class, 'users'])->name('users');
    Route::get('/{userId}', [\App\Http\Controllers\Chat\InboxController::class, 'show'])->name('show');
    Route::post('/', [\App\Http\Controllers\Chat\InboxController::class, 'store'])->name('store');
    Route::post('/{userId}/read', [\App\Http\Controllers\Chat\InboxController::class, 'markRead'])->name('mark-read');
});
