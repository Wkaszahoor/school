import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Line, Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement,
    LineElement, BarElement, Title, Tooltip, Legend, Filler,
} from 'chart.js';
import {
    UsersIcon, AcademicCapIcon, BuildingLibraryIcon,
    ClipboardDocumentListIcon, CalendarDaysIcon, ExclamationTriangleIcon,
    DocumentTextIcon, CheckCircleIcon, ChartBarIcon, BellAlertIcon,
    SparklesIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import Badge from '@/Components/Badge';
import type { PageProps, LeaveRequest, DisciplineRecord, LessonPlan, Notice, Student, PaginatedData } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

interface DashboardProps extends PageProps {
    stats: {
        total_students: number;
        total_teachers: number;
        total_classes: number;
        pending_leaves: number;
        pending_lesson_plans: number;
        pending_results: number;
        discipline_this_month: number;
        today_attendance_rate: number;
    };
    pendingLeaves: LeaveRequest[];
    recentDiscipline: DisciplineRecord[];
    pendingLessonPlans: LessonPlan[];
    classAttendance: Array<{ class: string; present: number; total: number; rate: number }>;
    attendanceTrend: Array<{ date: string; present: number; absent: number; rate: number }>;
    notices: Notice[];
    allStudents: PaginatedData<Student>;
}

export default function PrincipalDashboard({
    stats, pendingLeaves, recentDiscipline, pendingLessonPlans,
    classAttendance, attendanceTrend, notices, allStudents,
}: DashboardProps) {
    const trendData = {
        labels: attendanceTrend.map(d => {
            const date = new Date(d.date);
            return date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric' });
        }),
        datasets: [{
            label: 'Attendance Rate (%)',
            data: attendanceTrend.map(d => d.rate),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79, 70, 229, 0.1)',
            fill: true,
            tension: 0.4,
            pointRadius: 5,
            pointBackgroundColor: '#4f46e5',
        }],
    };

    const classBarData = {
        labels: classAttendance.map(c => c.class),
        datasets: [{
            label: 'Present',
            data: classAttendance.map(c => c.present),
            backgroundColor: '#10b981',
            borderRadius: 6,
        }, {
            label: 'Absent',
            data: classAttendance.map(c => c.total - c.present),
            backgroundColor: '#f87171',
            borderRadius: 6,
        }],
    };

    return (
        <AppLayout title="Principal Dashboard">
            <Head title="Principal Dashboard" />

            <div className="page-header w-full mb-4">
                <div>
                    <h1 className="page-title">Overview</h1>
                    <p className="page-subtitle">
                        {new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                    </p>
                </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5 w-full">
                <StatCard label="Total Students" value={stats.total_students} icon={UsersIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
                <StatCard label="Teachers" value={stats.total_teachers} icon={AcademicCapIcon}
                          iconBg="bg-purple-50" iconColor="text-purple-600" />
                <StatCard label="Today Attendance" value={stats.today_attendance_rate} suffix="%"
                          icon={ChartBarIcon} iconBg="bg-emerald-50" iconColor="text-emerald-600" />
                <StatCard label="Pending Actions" value={stats.pending_leaves + stats.pending_lesson_plans + stats.pending_results}
                          icon={BellAlertIcon} iconBg="bg-red-50" iconColor="text-red-500" />
            </div>

            {/* Action Required Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5 w-full">
                {[
                    { label: 'Leave Requests', value: stats.pending_leaves, href: route('principal.leave.index'), color: 'text-amber-600', bg: 'bg-amber-50' },
                    { label: 'Lesson Plans',   value: stats.pending_lesson_plans, href: route('principal.lesson-plans.index'), color: 'text-indigo-600', bg: 'bg-indigo-50' },
                    { label: 'Pending Results',value: stats.pending_results, href: route('principal.results.index'), color: 'text-blue-600', bg: 'bg-blue-50' },
                    { label: 'Timetables', value: '0', href: route('principal.timetables.index'), color: 'text-green-600', bg: 'bg-green-50', icon: SparklesIcon },
                ].map(item => (
                    <Link key={item.label} href={item.href}
                          className={`${item.bg} rounded-2xl p-4 w-full flex flex-col items-center justify-center text-center hover:opacity-90 transition-opacity`}>
                        {item.icon && <item.icon className={`w-6 h-6 ${item.color} mb-2`} />}
                        <p className={`font-semibold text-sm ${item.color}`}>{item.label}</p>
                        <p className="text-xs text-gray-500 mt-1">manage schedules</p>
                    </Link>
                ))}
            </div>

            {/* Charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-5 w-full">
                <div className="card w-full">
                    <div className="card-header">
                        <p className="card-title">Attendance Trend</p>
                        <p className="card-subtitle">7 days</p>
                    </div>
                    <div className="card-body">
                        <Line data={trendData} options={{
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` }, grid: { color: '#f3f4f6' } },
                                x: { grid: { display: false } },
                            },
                        }} height={100} />
                    </div>
                </div>
                <div className="card w-full">
                    <div className="card-header">
                        <p className="card-title">Today by Class</p>
                        <p className="card-subtitle">Attendance breakdown</p>
                    </div>
                    <div className="card-body">
                        <Bar data={classBarData} options={{
                            responsive: true,
                            plugins: { legend: { position: 'bottom' as const, labels: { boxWidth: 10 } } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            },
                        }} height={100} />
                    </div>
                </div>
            </div>

            {/* Bottom Tables */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 w-full">
                {/* Pending Leaves */}
                <div className="card w-full">
                    <div className="card-header">
                        <p className="card-title">Pending Leave Requests</p>
                        <Link href={route('principal.leave.index')} className="text-xs text-indigo-600 hover:underline font-medium">
                            View all
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {pendingLeaves.length === 0 ? (
                            <div className="card-body empty-state">
                                <CheckCircleIcon className="empty-state-icon text-emerald-400" />
                                <p className="empty-state-text text-emerald-600">All caught up!</p>
                            </div>
                        ) : pendingLeaves.map(leave => (
                            <div key={leave.id} className="flex items-center gap-3 px-5 py-3.5">
                                <div className="avatar-sm bg-purple-600">
                                    {leave.teacher?.user?.name?.charAt(0) ?? 'T'}
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-semibold text-gray-900">{leave.teacher?.user?.name}</p>
                                    <p className="text-xs text-gray-400">
                                        {leave.leave_type} · {new Date(leave.from_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}
                                        {' – '}
                                        {new Date(leave.to_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}
                                    </p>
                                </div>
                                <Link href={route('principal.leave.index')} className="btn-warning btn-sm">
                                    Review
                                </Link>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Recent Discipline */}
                <div className="card w-full">
                    <div className="card-header">
                        <p className="card-title">Recent Discipline</p>
                        <Link href={route('principal.discipline.index')} className="text-xs text-indigo-600 hover:underline font-medium">
                            View all
                        </Link>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {recentDiscipline.length === 0 ? (
                            <div className="card-body empty-state">
                                <ExclamationTriangleIcon className="empty-state-icon" />
                                <p className="empty-state-text">No recent incidents</p>
                            </div>
                        ) : recentDiscipline.map(record => (
                            <div key={record.id} className="flex items-center gap-3 px-5 py-3.5">
                                <div className="avatar-sm bg-red-100 text-red-600">
                                    {record.student?.name?.charAt(0) ?? 'S'}
                                </div>
                                <div className="flex-1">
                                    <p className="text-sm font-semibold text-gray-900">{record.student?.name}</p>
                                    <p className="text-xs text-gray-400 truncate max-w-xs">{record.description}</p>
                                </div>
                                <Badge color={record.type === 'achievement' ? 'green' : record.type === 'suspension' ? 'red' : 'yellow'}>
                                    {record.type}
                                </Badge>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Pending Lesson Plans */}
                <div className="card lg:col-span-2 w-full">
                    <div className="card-header">
                        <p className="card-title">Lesson Plans Awaiting Review</p>
                        <Link href={route('principal.lesson-plans.index')} className="text-xs text-indigo-600 hover:underline font-medium">
                            View all
                        </Link>
                    </div>
                    <div className="table-wrapper rounded-none">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Teacher</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Week Starting</th>
                                    <th>Topic</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {pendingLessonPlans.length === 0 ? (
                                    <tr>
                                        <td colSpan={6}>
                                            <div className="empty-state py-8">
                                                <DocumentTextIcon className="empty-state-icon" />
                                                <p className="empty-state-text">No pending lesson plans</p>
                                            </div>
                                        </td>
                                    </tr>
                                ) : pendingLessonPlans.map(plan => (
                                    <tr key={plan.id}>
                                        <td className="font-medium text-gray-900">{plan.teacher?.user?.name}</td>
                                        <td>{plan.class?.name}</td>
                                        <td>{plan.subject?.name}</td>
                                        <td className="text-gray-500">{new Date(plan.week_starting).toLocaleDateString('en-GB')}</td>
                                        <td className="max-w-xs truncate">{plan.topic}</td>
                                        <td>
                                            <Link href={route('principal.lesson-plans.show', plan.id)}
                                                  className="btn-primary btn-sm">
                                                Review
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
