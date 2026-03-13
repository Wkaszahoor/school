import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Line, Bar } from 'react-chartjs-2';
import {
    Chart as ChartJS, CategoryScale, LinearScale, PointElement,
    LineElement, BarElement, Title, Tooltip, Legend, Filler,
} from 'chart.js';
import { CalendarDaysIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend, Filler);

interface ClassPerformance {
    class: string;
    total: number;
    present: number;
    absent: number;
    leave: number;
    presentRate: number;
    absentRate: number;
}

interface AttendanceTrendData {
    date: string;
    total: number;
    present: number;
    rate: number;
}

interface DailyBreakdownRecord {
    id: number;
    attendance_date: string;
    status: string;
    student: { id: number; full_name: string; admission_no: string };
    class: { id: number; class: string; section: string | null };
}

interface DashboardProps extends PageProps {
    classPerformance: ClassPerformance[];
    attendanceTrend: AttendanceTrendData[];
    dailyBreakdown: { data: DailyBreakdownRecord[]; pagination: Record<string, unknown> };
    allClasses: Array<{ id: number; name: string }>;
    startDate: string;
    endDate: string;
    selectedClass: string;
}

export default function AttendancePerformance({
    classPerformance, attendanceTrend, dailyBreakdown, allClasses, startDate, endDate, selectedClass,
}: DashboardProps) {
    const [filterStartDate, setFilterStartDate] = useState(startDate.split('T')[0]);
    const [filterEndDate, setFilterEndDate] = useState(endDate.split('T')[0]);
    const [filterClass, setFilterClass] = useState(selectedClass);

    const handleFilter = () => {
        router.get(route('principal.attendance-performance.index'), {
            start_date: filterStartDate,
            end_date: filterEndDate,
            class: filterClass,
        }, { preserveState: true });
    };

    // Chart data for classwise performance
    const classChartData = {
        labels: classPerformance.map(c => c.class),
        datasets: [
            {
                label: 'Present',
                data: classPerformance.map(c => c.present),
                backgroundColor: '#10b981',
                borderRadius: 6,
            },
            {
                label: 'Absent',
                data: classPerformance.map(c => c.absent),
                backgroundColor: '#f87171',
                borderRadius: 6,
            },
            {
                label: 'Leave',
                data: classPerformance.map(c => c.leave),
                backgroundColor: '#f59e0b',
                borderRadius: 6,
            },
        ],
    };

    // Chart data for attendance trend
    const trendChartData = {
        labels: attendanceTrend.map(d => {
            try {
                const dateObj = new Date(d.date);
                return dateObj.toLocaleDateString('en-GB', { month: 'short', day: '2-digit' });
            } catch {
                return d.date || 'N/A';
            }
        }),
        datasets: [
            {
                label: 'Attendance Rate (%)',
                data: attendanceTrend.map(d => d.rate),
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#4f46e5',
            },
        ],
    };

    // Chart data for classwise rate comparison
    const rateChartData = {
        labels: classPerformance.map(c => c.class),
        datasets: [
            {
                label: 'Present Rate (%)',
                data: classPerformance.map(c => c.presentRate),
                backgroundColor: '#10b981',
                borderRadius: 6,
            },
            {
                label: 'Absent Rate (%)',
                data: classPerformance.map(c => c.absentRate),
                backgroundColor: '#f87171',
                borderRadius: 6,
            },
        ],
    };

    return (
        <AppLayout title="Attendance Performance">
            <Head title="Attendance Performance" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Attendance Performance</h1>
                    <p className="page-subtitle">Classwise attendance analytics and trends</p>
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-6">
                <div className="card-header">
                    <p className="card-title">Filters</p>
                </div>
                <div className="card-body">
                    <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <div className="relative">
                                <CalendarDaysIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    type="date"
                                    value={filterStartDate}
                                    onChange={(e) => setFilterStartDate(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <div className="relative">
                                <CalendarDaysIcon className="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
                                <input
                                    type="date"
                                    value={filterEndDate}
                                    onChange={(e) => setFilterEndDate(e.target.value)}
                                    className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">Class</label>
                            <select
                                value={filterClass}
                                onChange={(e) => setFilterClass(e.target.value)}
                                className="w-full px-4 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                            >
                                <option value="">All Classes</option>
                                {allClasses.map(cls => (
                                    <option key={cls.id} value={cls.id}>{cls.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex items-end">
                            <button
                                onClick={handleFilter}
                                className="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors font-medium"
                            >
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Charts Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">
                {/* Classwise Performance */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Classwise Attendance Count</p>
                        <p className="card-subtitle">Present, Absent, Leave breakdown</p>
                    </div>
                    <div className="card-body">
                        <Bar data={classChartData} options={{
                            responsive: true,
                            plugins: { legend: { position: 'bottom' as const, labels: { boxWidth: 10 } } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            },
                        }} height={100} />
                    </div>
                </div>

                {/* Attendance Trend */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Attendance Trend</p>
                        <p className="card-subtitle">Daily attendance rate over period</p>
                    </div>
                    <div className="card-body">
                        <Line data={trendChartData} options={{
                            responsive: true,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { min: 0, max: 100, ticks: { callback: (v) => `${v}%` }, grid: { color: '#f3f4f6' } },
                                x: { grid: { display: false } },
                            },
                        }} height={100} />
                    </div>
                </div>

                {/* Classwise Rate Comparison */}
                <div className="card lg:col-span-2">
                    <div className="card-header">
                        <p className="card-title">Classwise Attendance Rate</p>
                        <p className="card-subtitle">Present and Absent percentage comparison</p>
                    </div>
                    <div className="card-body">
                        <Bar data={rateChartData} options={{
                            responsive: true,
                            plugins: { legend: { position: 'bottom' as const, labels: { boxWidth: 10 } } },
                            scales: {
                                x: { grid: { display: false } },
                                y: { max: 100, ticks: { callback: (v) => `${v}%` }, grid: { color: '#f3f4f6' } },
                            },
                        }} height={80} />
                    </div>
                </div>
            </div>

            {/* Performance Summary Table */}
            <div className="card mb-5">
                <div className="card-header">
                    <p className="card-title">Performance Summary by Class</p>
                    <p className="card-subtitle">{classPerformance.length} classes</p>
                </div>
                <div className="table-wrapper rounded-none overflow-x-auto">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Total</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Leave</th>
                                <th>Present Rate</th>
                                <th>Absent Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            {classPerformance.map(perf => (
                                <tr key={perf.class}>
                                    <td className="font-medium text-gray-900">{perf.class}</td>
                                    <td className="text-sm text-gray-600">{perf.total}</td>
                                    <td className="text-sm text-green-600 font-medium">{perf.present}</td>
                                    <td className="text-sm text-red-600 font-medium">{perf.absent}</td>
                                    <td className="text-sm text-amber-600 font-medium">{perf.leave}</td>
                                    <td className="text-sm">
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-green-500 h-2 rounded-full"
                                                style={{ width: `${perf.presentRate}%` }}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-600">{perf.presentRate}%</span>
                                    </td>
                                    <td className="text-sm">
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div
                                                className="bg-red-500 h-2 rounded-full"
                                                style={{ width: `${perf.absentRate}%` }}
                                            />
                                        </div>
                                        <span className="text-xs text-gray-600">{perf.absentRate}%</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Daily Breakdown */}
            <div className="card">
                <div className="card-header">
                    <p className="card-title">Daily Attendance Records</p>
                    <p className="card-subtitle">{dailyBreakdown.pagination.total as number} total records</p>
                </div>
                <div className="table-wrapper rounded-none overflow-x-auto">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {dailyBreakdown.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5}>
                                        <div className="empty-state py-8">
                                            <p className="empty-state-text">No attendance records found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : dailyBreakdown.data.map(record => (
                                <tr key={`${record.id}-${record.attendance_date}`}>
                                    <td className="text-sm text-gray-600">
                                        {new Date(record.attendance_date).toLocaleDateString('en-GB')}
                                    </td>
                                    <td className="text-sm font-medium text-gray-900">{record.student.full_name}</td>
                                    <td className="text-sm text-gray-600">{record.student.admission_no}</td>
                                    <td className="text-sm">{record.class.class} {record.class.section && `- ${record.class.section}`}</td>
                                    <td>
                                        <span className={`text-xs font-medium px-2.5 py-0.5 rounded-full ${
                                            record.status === 'P' ? 'bg-green-100 text-green-800' :
                                            record.status === 'A' ? 'bg-red-100 text-red-800' :
                                            'bg-amber-100 text-amber-800'
                                        }`}>
                                            {record.status === 'P' ? 'Present' : record.status === 'A' ? 'Absent' : 'Leave'}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
