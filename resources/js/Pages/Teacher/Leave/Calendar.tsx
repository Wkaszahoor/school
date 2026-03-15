import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';

dayjs.extend(utc);
dayjs.extend(timezone);

interface LeaveRequest {
    id: number;
    from_date: string;
    to_date: string;
    leave_type: string;
    status: 'Pending' | 'Approved' | 'Rejected';
    is_paid?: boolean;
    approved_days?: number;
    reason: string;
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
    balance: Record<string, { max_days: number; used: number; remaining: number; is_paid: boolean }>;
    settings: LeaveSetting[];
    academicYear: string;
}

export default function Calendar({ leaves, balance, settings, academicYear }: Props) {
    const [currentDate, setCurrentDate] = useState(dayjs());
    const [viewType, setViewType] = useState<'month' | 'year'>('month');

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
                return 'bg-green-100 border-green-300';
            case 'Pending':
                return 'bg-yellow-100 border-yellow-300';
            case 'Rejected':
                return 'bg-red-100 border-red-300';
            default:
                return 'bg-gray-100 border-gray-300';
        }
    };

    const getStatusTextColor = (status: string) => {
        switch (status) {
            case 'Approved':
                return 'text-green-700';
            case 'Pending':
                return 'text-yellow-700';
            case 'Rejected':
                return 'text-red-700';
            default:
                return 'text-gray-700';
        }
    };

    const isLeaveOnDate = (date: dayjs.Dayjs) => {
        return leaves.filter(
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

                            return (
                                <div
                                    key={date.format('YYYY-MM-DD')}
                                    className={`min-h-32 p-2 bg-white ${
                                        isCurrentMonth ? '' : 'bg-gray-50'
                                    }`}
                                >
                                    <div className="text-sm font-semibold text-gray-900 mb-1">
                                        {date.format('D')}
                                    </div>
                                    {dayLeaves.length > 0 && (
                                        <div className="space-y-1">
                                            {dayLeaves.map((leave) => (
                                                <div
                                                    key={leave.id}
                                                    className={`text-xs px-2 py-1 rounded border truncate cursor-pointer ${getStatusColor(
                                                        leave.status
                                                    )} hover:opacity-80`}
                                                    title={`${leave.leave_type}: ${leave.status}`}
                                                >
                                                    <span className={getStatusTextColor(leave.status)}>
                                                        {leave.leave_type}
                                                    </span>
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
                                                className={`text-xs p-1 rounded text-center ${
                                                    dayLeaves.length > 0
                                                        ? 'bg-blue-100 text-blue-700 font-semibold'
                                                        : isCurrentMonth
                                                          ? 'text-gray-900'
                                                          : 'text-gray-400'
                                                }`}
                                            >
                                                {date.format('D')}
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
                            <h1 className="text-3xl font-bold text-gray-900">Leave Calendar</h1>
                            <Link
                                href={route('teacher.leave.index')}
                                className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                            >
                                Apply for Leave
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

                        {/* Sidebar - Leave Balance */}
                        <div className="space-y-6">
                            <div className="bg-white rounded-lg shadow-sm p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">Leave Balance</h3>
                                <div className="space-y-4">
                                    {settings.length > 0 ? (
                                        settings.map((setting) => {
                                            const balanceInfo = balance[setting.leave_type] || {
                                                max_days: 0,
                                                used: 0,
                                                remaining: 0,
                                                is_paid: false,
                                            };

                                            return (
                                                <div
                                                    key={setting.leave_type}
                                                    className="border-l-4 px-4 py-3 bg-gray-50 rounded"
                                                    style={{
                                                        borderColor: getLeaveTypeColor(
                                                            setting.leave_type
                                                        ),
                                                    }}
                                                >
                                                    <div className="flex items-center justify-between mb-2">
                                                        <span className="font-semibold text-gray-900 capitalize">
                                                            {setting.leave_type}
                                                        </span>
                                                        <span className="text-xs bg-gray-200 px-2 py-1 rounded">
                                                            {balanceInfo.is_paid ? '💰 Paid' : '🆓 Unpaid'}
                                                        </span>
                                                    </div>
                                                    <div className="text-sm text-gray-600 mb-2">
                                                        {balanceInfo.used}/{balanceInfo.max_days} days used
                                                    </div>
                                                    <div className="w-full bg-gray-300 rounded-full h-2">
                                                        <div
                                                            className="h-2 rounded-full"
                                                            style={{
                                                                width: `${
                                                                    (balanceInfo.used /
                                                                        balanceInfo.max_days) *
                                                                    100
                                                                }%`,
                                                                backgroundColor: getLeaveTypeColor(
                                                                    setting.leave_type
                                                                ),
                                                            }}
                                                        />
                                                    </div>
                                                    <div className="text-xs text-gray-500 mt-2">
                                                        {balanceInfo.remaining} days remaining
                                                    </div>
                                                </div>
                                            );
                                        })
                                    ) : (
                                        <div className="text-center py-4 text-gray-500">
                                            No leave settings configured
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Legend */}
                            <div className="bg-white rounded-lg shadow-sm p-6">
                                <h3 className="text-lg font-bold text-gray-900 mb-4">Legend</h3>
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <div className="w-4 h-4 bg-green-100 border border-green-300 rounded"></div>
                                        <span className="text-sm text-gray-700">Approved</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-4 h-4 bg-yellow-100 border border-yellow-300 rounded"></div>
                                        <span className="text-sm text-gray-700">Pending</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <div className="w-4 h-4 bg-red-100 border border-red-300 rounded"></div>
                                        <span className="text-sm text-gray-700">Rejected</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
