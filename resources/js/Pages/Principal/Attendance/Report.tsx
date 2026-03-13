import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, FunnelIcon, MagnifyingGlassIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import type { PageProps } from '@/types';

interface StudentAttendance {
    id: number;
    full_name: string;
    admission_no: string;
    class_name: string;
    total: number;
    present: number;
    absent: number;
    leave: number;
    rate: number;
    subject_wise: Array<{
        subject_name: string;
        total: number;
        present: number;
        absent: number;
        leave: number;
        rate: number;
    }>;
    calendar_data: Record<string, {
        status: string;
        subject_name: string;
        remarks?: string;
    }>;
}

interface ClassOption {
    id: number;
    name: string;
}

interface Props extends PageProps {
    students: {
        data: StudentAttendance[];
        links: any;
        current_page: number;
        per_page: number;
        total: number;
    };
    classes: ClassOption[];
    filters: {
        search?: string;
        class_id?: string;
        start_date?: string;
        end_date?: string;
    };
}

export default function AttendanceReport({ students, classes, filters }: Props) {
    const [searchInput, setSearchInput] = useState(filters.search || '');
    const [selectedClass, setSelectedClass] = useState(filters.class_id || '');
    const [startDate, setStartDate] = useState(filters.start_date || '');
    const [endDate, setEndDate] = useState(filters.end_date || '');
    const [expandedStudent, setExpandedStudent] = useState<number | null>(null);
    const [calendarMonth, setCalendarMonth] = useState(new Date());

    const handleFilter = () => {
        const params = new URLSearchParams();
        if (searchInput) params.append('search', searchInput);
        if (selectedClass) params.append('class_id', selectedClass);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);

        router.get(route('principal.attendance-report.index'), Object.fromEntries(params));
    };

    const handleReset = () => {
        setSearchInput('');
        setSelectedClass('');
        setStartDate('');
        setEndDate('');
        router.get(route('principal.attendance-report.index'));
    };

    const getRateColor = (rate: number) => {
        if (rate >= 75) return 'green';
        if (rate >= 50) return 'yellow';
        return 'red';
    };

    const getStatusColor = (status?: string) => {
        switch (status) {
            case 'P':
                return 'bg-emerald-100 border-emerald-300 text-emerald-700';
            case 'A':
                return 'bg-red-100 border-red-300 text-red-700';
            case 'L':
                return 'bg-amber-100 border-amber-300 text-amber-700';
            default:
                return 'bg-gray-50 border-gray-200 text-gray-400';
        }
    };

    const getStatusLabel = (status?: string) => {
        switch (status) {
            case 'P': return 'Present';
            case 'A': return 'Absent';
            case 'L': return 'Leave';
            default: return 'No record';
        }
    };

    const getDaysInMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const getFirstDayOfMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    const monthName = calendarMonth.toLocaleString('default', { month: 'long', year: 'numeric' });

    const renderCalendar = (student: StudentAttendance) => {
        const days = [];
        const firstDay = getFirstDayOfMonth(calendarMonth);
        const daysInMonth = getDaysInMonth(calendarMonth);

        // Empty cells for days before month starts
        for (let i = 0; i < firstDay; i++) {
            days.push(null);
        }

        // Days of the month
        for (let i = 1; i <= daysInMonth; i++) {
            const dateStr = new Date(calendarMonth.getFullYear(), calendarMonth.getMonth(), i)
                .toISOString().split('T')[0];
            const attendance = student.calendar_data[dateStr];
            days.push({ day: i, attendance });
        }

        return days;
    };

    return (
        <AppLayout title="Attendance Report">
            <Head title="Attendance Report" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('principal.students.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Attendance Report</h1>
                        <p className="page-subtitle">View and analyze student attendance</p>
                    </div>
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        {/* Search */}
                        <div>
                            <label className="form-label">Search Student</label>
                            <div className="relative">
                                <MagnifyingGlassIcon className="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                <input
                                    type="text"
                                    value={searchInput}
                                    onChange={(e) => setSearchInput(e.target.value)}
                                    placeholder="Name or admission no..."
                                    className="form-input pl-9"
                                />
                            </div>
                        </div>

                        {/* Class Filter */}
                        <div>
                            <label className="form-label">Class</label>
                            <select
                                value={selectedClass}
                                onChange={(e) => setSelectedClass(e.target.value)}
                                className="form-select"
                            >
                                <option value="">All Classes</option>
                                {classes.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Start Date */}
                        <div>
                            <label className="form-label">Start Date</label>
                            <input
                                type="date"
                                value={startDate}
                                onChange={(e) => setStartDate(e.target.value)}
                                className="form-input"
                            />
                        </div>

                        {/* End Date */}
                        <div>
                            <label className="form-label">End Date</label>
                            <input
                                type="date"
                                value={endDate}
                                onChange={(e) => setEndDate(e.target.value)}
                                className="form-input"
                            />
                        </div>

                        {/* Buttons */}
                        <div className="flex gap-2 items-end">
                            <button onClick={handleFilter} className="btn-primary flex-1">
                                Apply
                            </button>
                            <button onClick={handleReset} className="btn-secondary flex-1">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Summary Stats */}
            {students.data.length > 0 && (
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
                    <div className="card p-4 text-center">
                        <p className="text-sm text-gray-600">Total Students</p>
                        <p className="text-2xl font-bold text-gray-900">{students.total}</p>
                    </div>
                    <div className="card p-4 text-center bg-emerald-50">
                        <p className="text-sm text-emerald-600 font-medium">Avg Present</p>
                        <p className="text-2xl font-bold text-emerald-700">
                            {(students.data.reduce((sum, s) => sum + s.present, 0) / Math.max(students.data.length, 1)).toFixed(1)}
                        </p>
                    </div>
                    <div className="card p-4 text-center bg-red-50">
                        <p className="text-sm text-red-600 font-medium">Avg Absent</p>
                        <p className="text-2xl font-bold text-red-700">
                            {(students.data.reduce((sum, s) => sum + s.absent, 0) / Math.max(students.data.length, 1)).toFixed(1)}
                        </p>
                    </div>
                    <div className="card p-4 text-center bg-indigo-50">
                        <p className="text-sm text-indigo-600 font-medium">Avg Rate</p>
                        <p className="text-2xl font-bold text-indigo-700">
                            {(students.data.reduce((sum, s) => sum + s.rate, 0) / Math.max(students.data.length, 1)).toFixed(1)}%
                        </p>
                    </div>
                </div>
            )}

            {/* Report Table */}
            <div className="card">
                <div className="card-body">
                    {students.data.length > 0 ? (
                        <>
                            <div className="table-wrapper">
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Class</th>
                                            <th>Total</th>
                                            <th>Present</th>
                                            <th>Absent</th>
                                            <th>Leave</th>
                                            <th>Rate</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {students.data.map((student) => (
                                            <React.Fragment key={student.id}>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <p className="font-medium text-gray-900">{student.full_name}</p>
                                                            <p className="text-xs text-gray-500">{student.admission_no}</p>
                                                        </div>
                                                    </td>
                                                    <td>{student.class_name}</td>
                                                    <td className="font-medium">{student.total}</td>
                                                    <td className="text-emerald-600 font-medium">{student.present}</td>
                                                    <td className="text-red-600 font-medium">{student.absent}</td>
                                                    <td className="text-amber-600 font-medium">{student.leave}</td>
                                                    <td>
                                                        <Badge color={getRateColor(student.rate)}>
                                                            {student.rate}%
                                                        </Badge>
                                                    </td>
                                                    <td>
                                                        <button
                                                            onClick={() =>
                                                                setExpandedStudent(
                                                                    expandedStudent === student.id ? null : student.id
                                                                )
                                                            }
                                                            className="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
                                                        >
                                                            {expandedStudent === student.id ? 'Hide' : 'Details'}
                                                        </button>
                                                    </td>
                                                </tr>

                                                {/* Subject Wise Breakdown */}
                                                {expandedStudent === student.id && (
                                                    <tr>
                                                        <td colSpan={8}>
                                                            <div className="p-4 bg-gray-50 rounded-lg">
                                                                <p className="font-semibold text-gray-900 mb-3">
                                                                    Subject-wise Attendance
                                                                </p>
                                                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                                    {student.subject_wise.map((subject, idx) => (
                                                                        <div
                                                                            key={idx}
                                                                            className="p-3 bg-white rounded-lg border border-gray-200"
                                                                        >
                                                                            <p className="font-medium text-gray-900 text-sm">
                                                                                {subject.subject_name}
                                                                            </p>
                                                                            <div className="grid grid-cols-4 gap-2 mt-2 text-xs">
                                                                                <div className="text-center">
                                                                                    <p className="text-gray-500">Total</p>
                                                                                    <p className="font-bold text-gray-900">
                                                                                        {subject.total}
                                                                                    </p>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <p className="text-emerald-600">Present</p>
                                                                                    <p className="font-bold text-emerald-700">
                                                                                        {subject.present}
                                                                                    </p>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <p className="text-red-600">Absent</p>
                                                                                    <p className="font-bold text-red-700">
                                                                                        {subject.absent}
                                                                                    </p>
                                                                                </div>
                                                                                <div className="text-center">
                                                                                    <p className="text-amber-600">Leave</p>
                                                                                    <p className="font-bold text-amber-700">
                                                                                        {subject.leave}
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            <div className="mt-2 pt-2 border-t border-gray-200">
                                                                                <Badge
                                                                                    color={getRateColor(subject.rate)}
                                                                                    className="w-full text-center"
                                                                                >
                                                                                    {subject.rate}%
                                                                                </Badge>
                                                                            </div>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                                {/* Attendance Calendar */}
                                                                <div className="mt-6 pt-6 border-t border-gray-200">
                                                                    <div className="flex items-center justify-between mb-4">
                                                                        <p className="font-semibold text-gray-900">Attendance Calendar - {monthName}</p>
                                                                        <div className="flex gap-2">
                                                                            <button
                                                                                onClick={() => setCalendarMonth(new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() - 1))}
                                                                                className="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded"
                                                                            >
                                                                                ← Prev
                                                                            </button>
                                                                            <button
                                                                                onClick={() => setCalendarMonth(new Date())}
                                                                                className="px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded"
                                                                            >
                                                                                Today
                                                                            </button>
                                                                            <button
                                                                                onClick={() => setCalendarMonth(new Date(calendarMonth.getFullYear(), calendarMonth.getMonth() + 1))}
                                                                                className="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded"
                                                                            >
                                                                                Next →
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                    <div className="grid grid-cols-7 gap-1 mb-4">
                                                                        {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                                                                            <div key={day} className="text-center text-xs font-semibold text-gray-500 py-2">
                                                                                {day}
                                                                            </div>
                                                                        ))}
                                                                        {renderCalendar(student).map((dayData, idx) => (
                                                                            <div
                                                                                key={idx}
                                                                                className={`aspect-square flex items-center justify-center text-xs font-medium rounded border-2 transition cursor-help ${
                                                                                    dayData === null
                                                                                        ? 'bg-white border-white'
                                                                                        : `${getStatusColor(dayData.attendance?.status)} border`
                                                                                }`}
                                                                                title={dayData ? getStatusLabel(dayData.attendance?.status) + (dayData.attendance?.subject_name ? ` - ${dayData.attendance.subject_name}` : '') : ''}
                                                                            >
                                                                                {dayData && dayData.day}
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                    <div className="flex flex-wrap gap-3 text-xs">
                                                                        <div className="flex items-center gap-2">
                                                                            <div className="w-3 h-3 bg-emerald-100 border border-emerald-300 rounded" />
                                                                            <span className="text-gray-600">Present</span>
                                                                        </div>
                                                                        <div className="flex items-center gap-2">
                                                                            <div className="w-3 h-3 bg-red-100 border border-red-300 rounded" />
                                                                            <span className="text-gray-600">Absent</span>
                                                                        </div>
                                                                        <div className="flex items-center gap-2">
                                                                            <div className="w-3 h-3 bg-amber-100 border border-amber-300 rounded" />
                                                                            <span className="text-gray-600">Leave</span>
                                                                        </div>
                                                                        <div className="flex items-center gap-2">
                                                                            <div className="w-3 h-3 bg-gray-50 border border-gray-200 rounded" />
                                                                            <span className="text-gray-600">No Record</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            <Pagination data={students} />
                        </>
                    ) : (
                        <div className="text-center py-8">
                            <p className="text-gray-500 text-lg">No attendance records found</p>
                            <p className="text-gray-400 text-sm mt-1">Try adjusting your filters</p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
