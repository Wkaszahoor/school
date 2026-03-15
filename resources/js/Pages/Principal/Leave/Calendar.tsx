import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

interface User {
    id: number;
    name: string;
    email: string;
}

interface LeaveRequest {
    id: number;
    from_date: string;
    to_date: string;
    leave_type: string;
    status: 'Pending' | 'Approved' | 'Rejected';
    is_paid?: boolean;
    approved_days?: number;
    reason: string;
    teacher: User;
}

interface LeaveSetting {
    id: number;
    academic_year: string;
    leave_type: string;
    max_days: number;
    is_paid: boolean;
}

interface Props {
    leaves: LeaveRequest[];
    settings: LeaveSetting[];
    academicYear: string;
    academicYears: string[];
}

export default function Calendar({ leaves, settings, academicYear, academicYears }: Props) {
    const [currentDate, setCurrentDate] = useState(dayjs());
    const [viewType, setViewType] = useState<'month' | 'year'>('month');
    const [filterTeacher, setFilterTeacher] = useState<number | null>(null);
    const [filterLeaveType, setFilterLeaveType] = useState<string | null>(null);
    const [filterStatus, setFilterStatus] = useState<string | null>(null);

    const getLeaveTypeColor = (leaveType: string) => {
        const colors: Record<string, string> = {
            casual: '#3b82f6',
            annual: '#10b981',
            emergency: '#ef4444',
            other: '#8b5cf6',
        };
        return colors[leaveType] || '#6b7280';
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Approved':
                return 'bg-green-100 text-green-700';
            case 'Pending':
                return 'bg-yellow-100 text-yellow-700';
            case 'Rejected':
                return 'bg-red-100 text-red-700';
            default:
                return 'bg-gray-100 text-gray-700';
        }
    };

    // Filter leaves based on current filters
    const filteredLeaves = useMemo(() => {
        return leaves.filter((leave) => {
            if (filterTeacher && leave.teacher.id !== filterTeacher) return false;
            if (filterLeaveType && leave.leave_type !== filterLeaveType) return false;
            if (filterStatus && leave.status !== filterStatus) return false;
            return true;
        });
    }, [leaves, filterTeacher, filterLeaveType, filterStatus]);

    // Get unique teachers
    const teachers = Array.from(
        new Map(leaves.map((leave) => [leave.teacher.id, leave.teacher])).values()
    ).sort((a, b) => a.name.localeCompare(b.name));

    // Get unique leave types
    const leaveTypes = Array.from(new Set(leaves.map((leave) => leave.leave_type)));

    const isLeaveOnDate = (date: dayjs.Dayjs) => {
        return filteredLeaves.filter(
            (leave) =>
                dayjs(leave.from_date).isSameOrBefore(date, 'day') &&
                dayjs(leave.to_date).isSameOrAfter(date, 'day')
        );
    };

    const renderMonth = () => {
        const startOfMonth = currentDate.startOf('month');
        const endOfMonth = currentDate.endOf('month');
        const startDate = startOfMonth.startOf('week');
        const endDate = endOfMonth.endOf('week');

        const days = [];
        let date = startDate;

        while (date.isBefore(endDate, 'day') || date.isSame(endDate, 'day')) {
            days.push(date);
            date = date.add(1, 'day');
        }

        const weeks = [];
        for (let i = 0; i < days.length; i += 7) {
            weeks.push(days.slice(i, i + 7));
        }

        return (
            <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                {/* Month header */}
                <div className="px-6 py-4 border-b border-gray-200">
                    <div className="flex items-center justify-between mb-4">
                        <h2 className="text-xl font-bold text-gray-900">
                            {currentDate.format('MMMM YYYY')}
                        </h2>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setCurrentDate(currentDate.subtract(1, 'month'))}
                                className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                            >
                                ← Prev
                            </button>
                            <button
                                onClick={() => setCurrentDate(dayjs())}
                                className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                            >
                                Today
                            </button>
                            <button
                                onClick={() => setCurrentDate(currentDate.add(1, 'month'))}
                                className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                            >
                                Next →
                            </button>
                        </div>
                    </div>
                </div>

                {/* Weekday headers */}
                <div className="grid grid-cols-7 gap-px bg-gray-200">
                    {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((day) => (
                        <div
                            key={day}
                            className="bg-gray-50 px-4 py-2 text-center font-semibold text-gray-700"
                        >
                            {day}
                        </div>
                    ))}
                </div>

                {/* Calendar grid */}
                {weeks.map((week, weekIndex) => (
                    <div key={weekIndex} className="grid grid-cols-7 gap-px bg-gray-200">
                        {week.map((date) => {
                            const dayLeaves = isLeaveOnDate(date);
                            const isCurrentMonth = date.isSame(currentDate, 'month');

                            // Group leaves by teacher
                            const leavesByTeacher = dayLeaves.reduce(
                                (acc, leave) => {
                                    if (!acc[leave.teacher.name]) {
                                        acc[leave.teacher.name] = [];
                                    }
                                    acc[leave.teacher.name].push(leave);
                                    return acc;
                                },
                                {} as Record<string, LeaveRequest[]>
                            );

                            return (
                                <div
                                    key={date.format('YYYY-MM-DD')}
                                    className={`min-h-32 p-2 bg-white ${
                                        isCurrentMonth ? '' : 'bg-gray-50'
                                    }`}
                                >
                                    <div className="text-sm font-semibold text-gray-900 mb-2">
                                        {date.format('D')}
                                    </div>
                                    {dayLeaves.length > 0 && (
                                        <div className="space-y-1">
                                            {Object.entries(leavesByTeacher).map(([teacherName, teacherLeaves]) => (
                                                <div
                                                    key={teacherName}
                                                    className="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-1 rounded truncate"
                                                    title={teacherName}
                                                >
                                                    {teacherName.split(' ')[0]}
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                ))}
            </div>
        );
    };

    const renderYear = () => {
        const months = [];
        for (let i = 0; i < 12; i++) {
            months.push(currentDate.month(i));
        }

        return (
            <div>
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-xl font-bold text-gray-900">{currentDate.format('YYYY')}</h2>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setCurrentDate(currentDate.subtract(1, 'year'))}
                            className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                        >
                            ← Prev Year
                        </button>
                        <button
                            onClick={() => setCurrentDate(dayjs())}
                            className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                        >
                            Today
                        </button>
                        <button
                            onClick={() => setCurrentDate(currentDate.add(1, 'year'))}
                            className="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                        >
                            Next Year →
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-3 gap-4">
                    {months.map((month) => {
                        const startOfMonth = month.startOf('month');
                        const endOfMonth = month.endOf('month');
                        const startDate = startOfMonth.startOf('week');
                        const endDate = endOfMonth.endOf('week');

                        const days = [];
                        let date = startDate;
                        while (date.isBefore(endDate, 'day') || date.isSame(endDate, 'day')) {
                            days.push(date);
                            date = date.add(1, 'day');
                        }

                        return (
                            <div key={month.format('YYYY-MM')} className="bg-white rounded-lg shadow-sm p-4">
                                <h3 className="text-sm font-bold text-gray-900 mb-2">
                                    {month.format('MMMM')}
                                </h3>
                                <div className="grid grid-cols-7 gap-1">
                                    {['S', 'M', 'T', 'W', 'T', 'F', 'S'].map((day) => (
                                        <div key={day} className="text-xs text-center font-semibold text-gray-600">
                                            {day}
                                        </div>
                                    ))}
                                    {days.map((date) => {
                                        const dayLeaves = isLeaveOnDate(date);
                                        const isCurrentMonth = date.isSame(month, 'month');

                                        return (
                                            <div
                                                key={date.format('YYYY-MM-DD')}
                                                className={`text-xs p-1 rounded text-center relative ${
                                                    dayLeaves.length > 0
                                                        ? 'bg-indigo-100 text-indigo-700 font-semibold'
                                                        : isCurrentMonth
                                                          ? 'text-gray-900'
                                                          : 'text-gray-400'
                                                }`}
                                            >
                                                {date.format('D')}
                                                {dayLeaves.length > 0 && (
                                                    <div className="absolute bottom-0 left-1/2 -translate-x-1/2 flex gap-0.5">
                                                        {dayLeaves.slice(0, 3).map((leave, idx) => (
                                                            <div
                                                                key={idx}
                                                                className="w-1 h-1 rounded-full"
                                                                style={{
                                                                    backgroundColor: getLeaveTypeColor(
                                                                        leave.leave_type
                                                                    ),
                                                                }}
                                                            />
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>
        );
    };

    return (
        <AppLayout>
            <Head title="Leave Calendar" />

            <div className="py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    {/* Header */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-6">
                            <h1 className="text-3xl font-bold text-gray-900">Teacher Leave Calendar</h1>
                            <Link
                                href={route('principal.leave.settings')}
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Leave Settings
                            </Link>
                        </div>

                        {/* View type toggle */}
                        <div className="flex gap-2">
                            <button
                                onClick={() => setViewType('month')}
                                className={`px-4 py-2 rounded font-medium ${
                                    viewType === 'month'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-200 text-gray-900 hover:bg-gray-300'
                                }`}
                            >
                                Month View
                            </button>
                            <button
                                onClick={() => setViewType('year')}
                                className={`px-4 py-2 rounded font-medium ${
                                    viewType === 'year'
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-gray-200 text-gray-900 hover:bg-gray-300'
                                }`}
                            >
                                Year View
                            </button>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-8">
                        {/* Calendar */}
                        <div className="lg:col-span-3">{viewType === 'month' ? renderMonth() : renderYear()}</div>

                        {/* Sidebar - Filters */}
                        <div className="space-y-6">
                            <div className="bg-white rounded-lg shadow-sm p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">Filters</h3>

                                {/* Clear filters */}
                                <button
                                    onClick={() => {
                                        setFilterTeacher(null);
                                        setFilterLeaveType(null);
                                        setFilterStatus(null);
                                    }}
                                    className="w-full px-3 py-2 mb-4 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200"
                                >
                                    Clear Filters
                                </button>

                                {/* Teacher filter */}
                                <div className="mb-4">
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                                        Teacher
                                    </label>
                                    <select
                                        value={filterTeacher || ''}
                                        onChange={(e) =>
                                            setFilterTeacher(e.target.value ? parseInt(e.target.value) : null)
                                        }
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="">All Teachers</option>
                                        {teachers.map((teacher) => (
                                            <option key={teacher.id} value={teacher.id}>
                                                {teacher.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Leave type filter */}
                                <div className="mb-4">
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                                        Leave Type
                                    </label>
                                    <select
                                        value={filterLeaveType || ''}
                                        onChange={(e) => setFilterLeaveType(e.target.value || null)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="">All Types</option>
                                        {leaveTypes.map((type) => (
                                            <option key={type} value={type}>
                                                {type.charAt(0).toUpperCase() + type.slice(1)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {/* Status filter */}
                                <div>
                                    <label className="block text-sm font-semibold text-gray-700 mb-2">
                                        Status
                                    </label>
                                    <select
                                        value={filterStatus || ''}
                                        onChange={(e) => setFilterStatus(e.target.value || null)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    >
                                        <option value="">All Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Approved">Approved</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                </div>
                            </div>

                            {/* Legend */}
                            <div className="bg-white rounded-lg shadow-sm p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">Leave Types</h3>
                                <div className="space-y-2">
                                    {leaveTypes.map((type) => (
                                        <div key={type} className="flex items-center gap-2">
                                            <div
                                                className="w-3 h-3 rounded"
                                                style={{
                                                    backgroundColor: getLeaveTypeColor(type),
                                                }}
                                            ></div>
                                            <span className="text-sm text-gray-700 capitalize">{type}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
