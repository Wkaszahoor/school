import React from 'react';
import { Head } from '@inertiajs/react';
import { Line, Doughnut } from 'react-chartjs-2';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement,
    Title, Tooltip, Legend, Filler, ArcElement,
} from 'chart.js';
import {
    UsersIcon, AcademicCapIcon, BuildingLibraryIcon,
    ShieldCheckIcon, ClipboardDocumentListIcon, UserGroupIcon,
    CheckCircleIcon, XCircleIcon, CalendarDaysIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import type { PageProps, AuditLog, Student } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler, ArcElement);

interface DashboardProps extends PageProps {
    stats: {
        total_students: number;
        total_teachers: number;
        total_classes: number;
        total_users: number;
        pending_leaves: number;
        today_present: number;
        today_absent: number;
    };
    recentStudents: Student[];
    recentLogs: AuditLog[];
    attendanceChart: Array<{ date: string; present: number; absent: number }>;
    roleDistribution: Record<string, number>;
}

function RoleBadge({ role }: { role: string }) {
    const map: Record<string, { cls: string; label: string }> = {
        admin:             { cls: 'badge-red',    label: 'Admin' },
        principal:         { cls: 'badge-purple', label: 'Principal' },
        teacher:           { cls: 'badge-blue',   label: 'Teacher' },
        receptionist:      { cls: 'badge-green',  label: 'Receptionist' },
        principal_helper:  { cls: 'badge-indigo', label: 'Pr. Helper' },
        inventory_manager: { cls: 'badge-orange', label: 'Inventory' },
        doctor:            { cls: 'badge-yellow', label: 'Doctor' },
    };
    const { cls, label } = map[role] ?? { cls: 'badge-gray', label: role };
    return <span className={`badge ${cls}`}>{label}</span>;
}

export default function Dashboard({ stats, recentStudents, recentLogs, attendanceChart, roleDistribution }: DashboardProps) {
    const chartLabels = attendanceChart.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
    });

    const attendanceLineData = {
        labels: chartLabels,
        datasets: [
            {
                label: 'Present',
                data: attendanceChart.map(d => d.present),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#10b981',
            },
            {
                label: 'Absent',
                data: attendanceChart.map(d => d.absent),
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#ef4444',
            },
        ],
    };

    const roleLabels = Object.keys(roleDistribution);
    const roleColors = ['#ef4444', '#8b5cf6', '#3b82f6', '#10b981', '#14b8a6', '#f97316', '#ec4899'];

    const roleDoughnutData = {
        labels: roleLabels.map(r => r.replace('_', ' ')),
        datasets: [{
            data: Object.values(roleDistribution),
            backgroundColor: roleColors,
            borderWidth: 0,
            hoverOffset: 4,
        }],
    };

    const totalAttendanceToday = stats.today_present + stats.today_absent;
    const attendanceRate = totalAttendanceToday > 0
        ? Math.round((stats.today_present / totalAttendanceToday) * 100)
        : 0;

    return (
        <AppLayout title="Admin Dashboard">
            <Head title="Admin Dashboard" />

            {/* Page Header */}
            <div className="page-header">
                <div>
                    <h1 className="page-title">Dashboard</h1>
                    <p className="page-subtitle">
                        Welcome back! Here's what's happening at KORT today.
                    </p>
                </div>
                <div className="flex items-center gap-2 text-sm text-gray-500 bg-white rounded-xl px-4 py-2 border border-gray-100 shadow-sm">
                    <CalendarDaysIcon className="w-4 h-4" />
                    {new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                </div>
            </div>

            {/* Stat Cards */}
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4 mb-6">
                <StatCard
                    label="Total Students"
                    value={stats.total_students}
                    icon={UsersIcon}
                    iconBg="bg-blue-50"
                    iconColor="text-blue-600"
                />
                <StatCard
                    label="Teachers"
                    value={stats.total_teachers}
                    icon={AcademicCapIcon}
                    iconBg="bg-purple-50"
                    iconColor="text-purple-600"
                />
                <StatCard
                    label="Classes"
                    value={stats.total_classes}
                    icon={BuildingLibraryIcon}
                    iconBg="bg-amber-50"
                    iconColor="text-amber-600"
                />
                <StatCard
                    label="System Users"
                    value={stats.total_users}
                    icon={ShieldCheckIcon}
                    iconBg="bg-indigo-50"
                    iconColor="text-indigo-600"
                />
                <StatCard
                    label="Pending Leaves"
                    value={stats.pending_leaves}
                    icon={ClipboardDocumentListIcon}
                    iconBg="bg-orange-50"
                    iconColor="text-orange-600"
                />
                <StatCard
                    label="Today Present"
                    value={stats.today_present}
                    icon={CheckCircleIcon}
                    iconBg="bg-emerald-50"
                    iconColor="text-emerald-600"
                />
                <StatCard
                    label="Today Absent"
                    value={stats.today_absent}
                    icon={XCircleIcon}
                    iconBg="bg-red-50"
                    iconColor="text-red-600"
                />
            </div>

            {/* Charts Row */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
                {/* Attendance Chart */}
                <div className="card lg:col-span-2">
                    <div className="card-header">
                        <div>
                            <p className="card-title">7-Day Attendance Overview</p>
                            <p className="card-subtitle">Present vs absent over the past week</p>
                        </div>
                    </div>
                    <div className="card-body">
                        <Line
                            data={attendanceLineData}
                            options={{
                                responsive: true,
                                plugins: { legend: { position: 'bottom' as const, labels: { boxWidth: 12, padding: 16 } } },
                                scales: {
                                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                                    x: { grid: { display: false } },
                                },
                            }}
                            height={80}
                        />
                    </div>
                </div>

                {/* Role Distribution */}
                <div className="card">
                    <div className="card-header">
                        <div>
                            <p className="card-title">User Roles</p>
                            <p className="card-subtitle">Distribution by role</p>
                        </div>
                    </div>
                    <div className="card-body flex flex-col items-center">
                        <Doughnut
                            data={roleDoughnutData}
                            options={{
                                responsive: true,
                                cutout: '65%',
                                plugins: {
                                    legend: { position: 'bottom' as const, labels: { boxWidth: 10, padding: 12, font: { size: 11 } } },
                                },
                            }}
                            height={180}
                        />
                        <div className="mt-4 text-center">
                            <p className="text-2xl font-bold text-gray-900">{stats.total_users}</p>
                            <p className="text-xs text-gray-500">total users</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Today's Attendance */}
            <div className="card mb-5">
                <div className="card-header">
                    <p className="card-title">Today's Attendance</p>
                    <span className="badge-indigo badge">{attendanceRate}% present</span>
                </div>
                <div className="card-body">
                    <div className="flex items-center gap-3 mb-3">
                        <div className="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div
                                className="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 rounded-full transition-all duration-700"
                                style={{ width: `${attendanceRate}%` }}
                            />
                        </div>
                        <span className="text-sm font-bold text-gray-700">{attendanceRate}%</span>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <div className="text-center">
                            <p className="text-2xl font-bold text-emerald-600">{stats.today_present}</p>
                            <p className="text-xs text-gray-500">Present</p>
                        </div>
                        <div className="text-center">
                            <p className="text-2xl font-bold text-red-600">{stats.today_absent}</p>
                            <p className="text-xs text-gray-500">Absent</p>
                        </div>
                        <div className="text-center">
                            <p className="text-2xl font-bold text-gray-600">{totalAttendanceToday}</p>
                            <p className="text-xs text-gray-500">Total</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Bottom Row */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                {/* Recent Students */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Recent Admissions</p>
                        <a href={route('admin.students.index')} className="text-xs text-indigo-600 hover:underline font-medium">
                            View all
                        </a>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {recentStudents.length === 0 ? (
                            <div className="card-body empty-state">
                                <UserGroupIcon className="empty-state-icon" />
                                <p className="empty-state-text">No students yet</p>
                            </div>
                        ) : recentStudents.map(student => (
                            <div key={student.id} className="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50/60 transition-colors">
                                <div className="avatar-sm bg-indigo-600">
                                    {student.full_name?.charAt(0) ?? 'S'}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm font-semibold text-gray-800 truncate">{student.full_name}</p>
                                    <p className="text-xs text-gray-400">{student.admission_no} · {student.class?.class}</p>
                                </div>
                                <p className="text-xs text-gray-400 flex-shrink-0">
                                    {new Date(student.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' })}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Audit Logs */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Recent Activity</p>
                        <a href={route('admin.audit-logs')} className="text-xs text-indigo-600 hover:underline font-medium">
                            View all
                        </a>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {recentLogs.length === 0 ? (
                            <div className="card-body empty-state">
                                <ClipboardDocumentListIcon className="empty-state-icon" />
                                <p className="empty-state-text">No activity logged</p>
                            </div>
                        ) : recentLogs.map(log => (
                            <div key={log.id} className="flex items-start gap-3 px-5 py-3.5">
                                <div className="avatar-sm bg-gray-200 text-gray-600 text-xs mt-0.5">
                                    {log.user?.name?.charAt(0) ?? '?'}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="text-sm text-gray-800">
                                        <span className="font-semibold">{log.user?.name ?? 'System'}</span>
                                        {' '}
                                        <span className="text-gray-500">{log.action}</span>
                                    </p>
                                    <p className="text-xs text-gray-400 mt-0.5">
                                        {log.resource}{log.resource_id ? ` #${log.resource_id}` : ''}
                                        {' · '}
                                        {new Date(log.created_at).toLocaleString('en-GB', {
                                            day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                                        })}
                                    </p>
                                </div>
                                <RoleBadge role={log.user?.role ?? 'admin'} />
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
