import React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    AcademicCapIcon, ClipboardDocumentCheckIcon, DocumentTextIcon,
    CalendarDaysIcon, BookOpenIcon, CheckCircleIcon, ClockIcon,
    ExclamationTriangleIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import Badge from '@/Components/Badge';
import TeacherReportsCard from '@/Components/TeacherReportsCard';
import type { PageProps, TeacherProfile, Result, LessonPlan, SchoolClass, TeacherReport } from '@/types';

interface DashboardProps extends PageProps {
    stats: {
        my_classes: number;
        my_subjects: number;
        pending_plans: number;
        pending_leave: number;
        today_marked: number;
        homework_active: number;
    };
    teacher: TeacherProfile | null;
    recentAttendance: Array<{ id: number; status: string; student?: { name: string } }>;
    pendingPlans: LessonPlan[];
    recentResults: Result[];
    classTeacherAssignments: SchoolClass[];
    reportsReceived: TeacherReport[];
    assignments: Array<{
        id: number;
        class?: { id: number; class: string; section?: string };
        subject?: { id: number; subject_name: string };
        group?: { id: number; name: string } | null;
        academic_year: string;
    }>;
}

const STATUS_COLOR = { approved: 'green' as const, rejected: 'red' as const, pending: 'yellow' as const };

export default function TeacherDashboard({
    stats, teacher, recentAttendance, pendingPlans, recentResults, assignments, classTeacherAssignments, reportsReceived,
}: DashboardProps) {
    if (!teacher) {
        return (
            <AppLayout title="Teacher Dashboard">
                <Head title="Teacher Dashboard" />
                <div className="empty-state mt-20">
                    <AcademicCapIcon className="empty-state-icon w-20 h-20" />
                    <p className="text-lg font-semibold text-gray-600 mt-4">Profile Not Set Up</p>
                    <p className="empty-state-text mt-1">Please contact the administrator to set up your teacher profile.</p>
                </div>
            </AppLayout>
        );
    }

    const todayAttendancePct = recentAttendance.length > 0
        ? Math.round((recentAttendance.filter(a => a.status === 'P').length / recentAttendance.length) * 100)
        : null;

    return (
        <AppLayout title="Teacher Dashboard">
            <Head title="Teacher Dashboard" />

            <div className="page-header mb-4">
                <div>
                    <h1 className="page-title">
                        Good {new Date().getHours() < 12 ? 'morning' : new Date().getHours() < 17 ? 'afternoon' : 'evening'},{' '}
                        {teacher.user?.name?.split(' ')[0]}!
                    </h1>
                    <div className="flex items-center gap-3 mt-1">
                        <p className="page-subtitle">
                            {new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' })}
                        </p>
                        {teacher.primary_subject && (
                            <span className="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                {teacher.primary_subject} Teacher
                            </span>
                        )}
                    </div>
                </div>
            </div>

            {/* Quick Stats */}
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-5">
                <StatCard label="My Classes" value={stats.my_classes} icon={BookOpenIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
                <StatCard label="Subjects" value={stats.my_subjects} icon={AcademicCapIcon}
                          iconBg="bg-purple-50" iconColor="text-purple-600" />
                <StatCard label="Today Marked" value={stats.today_marked} icon={ClipboardDocumentCheckIcon}
                          iconBg="bg-emerald-50" iconColor="text-emerald-600" />
                <StatCard label="Pending Plans" value={stats.pending_plans} icon={ClockIcon}
                          iconBg="bg-amber-50" iconColor="text-amber-600" />
                <StatCard label="Active HW" value={stats.homework_active} icon={DocumentTextIcon}
                          iconBg="bg-indigo-50" iconColor="text-indigo-600" />
                <StatCard label="Leave Pending" value={stats.pending_leave} icon={CalendarDaysIcon}
                          iconBg="bg-gray-50" iconColor="text-gray-500" />
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                {[
                    { label: 'Mark Attendance', href: route('teacher.attendance.index'), icon: ClipboardDocumentCheckIcon, color: 'bg-blue-600' },
                    { label: 'Enter Results',   href: route('teacher.results.create'),   icon: AcademicCapIcon,           color: 'bg-purple-600' },
                    { label: 'Lesson Plan',     href: route('teacher.lesson-plans.create'), icon: DocumentTextIcon,       color: 'bg-indigo-600' },
                    { label: 'View Results',    href: route('teacher.results.index'),     icon: CheckCircleIcon,           color: 'bg-emerald-600' },
                ].map(action => (
                    <Link key={action.label} href={action.href}
                          className={`${action.color} rounded-2xl p-4 flex items-center gap-3 text-white hover:opacity-90 transition-opacity`}>
                        <action.icon className="w-6 h-6 flex-shrink-0" />
                        <span className="font-semibold text-sm">{action.label}</span>
                    </Link>
                ))}
            </div>

            {/* Class Teacher Assignment Card */}
            {classTeacherAssignments.length > 0 && (
                <div className="mb-5">
                    <div className="card bg-gradient-to-r from-amber-50 to-orange-50 border-l-4 border-amber-500">
                        <div className="card-body">
                            <div className="flex items-start gap-3">
                                <div className="avatar-sm bg-amber-100 text-amber-600 flex-shrink-0">
                                    <BookOpenIcon className="w-5 h-5" />
                                </div>
                                <div className="flex-1">
                                    <p className="font-semibold text-gray-900">Class Teacher Assignment</p>
                                    <p className="text-sm text-gray-600 mt-1">
                                        You are the class teacher of:{' '}
                                        <span className="font-semibold text-amber-700">
                                            {classTeacherAssignments.map(c =>
                                                `${c.class}${c.section ? `-${c.section}` : ''}`
                                            ).join(', ')}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {/* Today's Attendance */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Today's Attendance</p>
                        {todayAttendancePct !== null && (
                            <span className={`badge ${todayAttendancePct >= 75 ? 'badge-green' : 'badge-red'}`}>
                                {todayAttendancePct}%
                            </span>
                        )}
                    </div>
                    <div className="divide-y divide-gray-50 max-h-72 overflow-y-auto">
                        {recentAttendance.length === 0 ? (
                            <div className="card-body empty-state">
                                <ClipboardDocumentCheckIcon className="empty-state-icon" />
                                <p className="empty-state-text">No attendance marked today</p>
                                <Link href={route('teacher.attendance.index')} className="btn-primary btn-sm mt-3">
                                    Mark Now
                                </Link>
                            </div>
                        ) : recentAttendance.slice(0, 10).map(att => (
                            <div key={att.id} className="flex items-center gap-3 px-5 py-3">
                                <div className={`avatar-sm ${att.status === 'P' ? 'bg-emerald-500' : att.status === 'A' ? 'bg-red-500' : 'bg-amber-500'}`}>
                                    {att.student?.name?.charAt(0) ?? 'S'}
                                </div>
                                <span className="flex-1 text-sm font-medium text-gray-800">{att.student?.name}</span>
                                <Badge color={att.status === 'P' ? 'green' : att.status === 'A' ? 'red' : 'yellow'}>
                                    {att.status === 'P' ? 'Present' : att.status === 'A' ? 'Absent' : 'Leave'}
                                </Badge>
                            </div>
                        ))}
                    </div>
                    {recentAttendance.length > 0 && (
                        <div className="card-footer">
                            <Link href={route('teacher.attendance.index')} className="text-xs text-indigo-600 hover:underline">
                                Update attendance →
                            </Link>
                        </div>
                    )}
                </div>

                {/* Pending Lesson Plans */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">My Lesson Plans</p>
                        <Link href={route('teacher.lesson-plans.index')} className="text-xs text-indigo-600 hover:underline">
                            View all
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {pendingPlans.length === 0 ? (
                            <div className="card-body empty-state">
                                <CheckCircleIcon className="empty-state-icon text-emerald-400" />
                                <p className="empty-state-text text-emerald-600">No pending plans</p>
                                <Link href={route('teacher.lesson-plans.create')} className="btn-primary btn-sm mt-3">
                                    Create Plan
                                </Link>
                            </div>
                        ) : pendingPlans.map(plan => (
                            <div key={plan.id} className="flex items-start gap-3 px-5 py-3.5">
                                <div className="avatar-sm bg-indigo-100 text-indigo-600 mt-0.5">
                                    <DocumentTextIcon className="w-4 h-4" />
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-semibold text-gray-900">{plan.topic}</p>
                                    <p className="text-xs text-gray-400">
                                        {plan.class?.name} · {plan.subject?.name} · w/c {new Date(plan.week_starting).toLocaleDateString('en-GB')}
                                    </p>
                                </div>
                                <Badge color={STATUS_COLOR[plan.status] ?? 'gray'}>{plan.status}</Badge>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Results */}
                <div className="card lg:col-span-2">
                    <div className="card-header">
                        <p className="card-title">Recent Results Entry</p>
                        <Link href={route('teacher.results.index')} className="text-xs text-indigo-600 hover:underline">
                            View all
                        </Link>
                    </div>
                    <div className="table-wrapper rounded-none">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Exam</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                {recentResults.length === 0 ? (
                                    <tr>
                                        <td colSpan={7}>
                                            <div className="empty-state py-8">
                                                <AcademicCapIcon className="empty-state-icon" />
                                                <p className="empty-state-text">No results entered yet</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : recentResults.map(result => (
                                    <tr key={result.id}>
                                        <td className="font-medium text-gray-900">{result.student?.name}</td>
                                        <td>{result.class?.name}</td>
                                        <td>{result.subject?.name}</td>
                                        <td className="capitalize text-gray-500">{result.exam_type}</td>
                                        <td>{result.obtained_marks}/{result.total_marks}</td>
                                        <td>
                                            <span className={`font-bold ${
                                                result.percentage >= 70 ? 'text-emerald-600' :
                                                result.percentage >= 50 ? 'text-amber-600' : 'text-red-600'
                                            }`}>
                                                {result.grade}
                                            </span>
                                        </td>
                                        <td>
                                            <Badge color={result.is_approved ? 'green' : 'yellow'}>
                                                {result.is_approved ? 'Approved' : 'Pending'}
                                            </Badge>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* My Assignments */}
                <div className="card lg:col-span-2">
                    <div className="card-header">
                        <p className="card-title">My Class & Subject Assignments</p>
                    </div>
                    {assignments.length === 0 ? (
                        <div className="card-body empty-state">
                            <BookOpenIcon className="empty-state-icon" />
                            <p className="empty-state-text">No assignments yet</p>
                            <p className="text-xs text-gray-500 mt-2">Contact the principal to assign classes and subjects</p>
                        </div>
                    ) : (
                        <div className="table-wrapper rounded-none">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th>Class</th>
                                        <th>Subject</th>
                                        <th>Group/Stream</th>
                                        <th>Academic Year</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {assignments.map(assignment => (
                                        <tr key={assignment.id}>
                                            <td className="font-semibold text-gray-900">
                                                {assignment.class?.class}
                                                {assignment.class?.section && ` - ${assignment.class.section}`}
                                            </td>
                                            <td className="text-gray-700">{assignment.subject?.subject_name}</td>
                                            <td>
                                                {assignment.group ? (
                                                    <Badge color="blue">{assignment.group.name}</Badge>
                                                ) : (
                                                    <span className="text-gray-400">—</span>
                                                )}
                                            </td>
                                            <td className="text-gray-600">{assignment.academic_year}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>

                {/* Reports Received */}
                <TeacherReportsCard reports={reportsReceived} />
            </div>
        </AppLayout>
    );
}
