import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, PencilSquareIcon, CalendarDaysIcon, AcademicCapIcon, ShieldExclamationIcon, DocumentArrowDownIcon, TrashIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import AttendanceReport from '@/Components/AttendanceReport';
import type { PageProps, Student } from '@/types';

interface AttendanceSummary {
    total: number;
    present: number;
    absent: number;
    leave: number;
    rate: number;
}

interface Props extends PageProps {
    student: Student;
    attendanceSummary: AttendanceSummary;
    monthAttendance: Record<string, { status: string; remarks?: string | null }>;
    attendanceReport: any[];
    subjectWiseSummary: any[];
    monthWiseSummary: any[];
}

export default function PrincipalStudentShow({ student, attendanceSummary, monthAttendance, attendanceReport, subjectWiseSummary, monthWiseSummary }: Props) {
    const initials = student.full_name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const [currentMonth, setCurrentMonth] = React.useState(new Date());
    const [viewMode, setViewMode] = React.useState<'month' | 'quarter' | 'year' | 'custom'>('month');
    const [customStartDate, setCustomStartDate] = React.useState('');
    const [customEndDate, setCustomEndDate] = React.useState('');
    const [activeTab, setActiveTab] = React.useState<'overview' | 'report'>('overview');
    const [searchQuery, setSearchQuery] = useState('');

    const handleDelete = () => {
        if (confirm(`Delete "${student.full_name}"? This action cannot be undone.`)) {
            router.delete(route('principal.students.destroy', student.id));
        }
    };

    // Filter data based on search query
    const filteredAttendanceReport = useMemo(() => {
        if (!searchQuery.trim()) return attendanceReport || [];
        const q = searchQuery.toLowerCase();
        return (attendanceReport || []).filter(record =>
            record.date?.toLowerCase().includes(q) ||
            record.status?.toLowerCase().includes(q) ||
            record.remarks?.toLowerCase().includes(q)
        );
    }, [searchQuery, attendanceReport]);

    const filteredSubjectWiseSummary = useMemo(() => {
        if (!searchQuery.trim()) return subjectWiseSummary || [];
        const q = searchQuery.toLowerCase();
        return (subjectWiseSummary || []).filter(record =>
            record.subject?.toLowerCase().includes(q) ||
            record.name?.toLowerCase().includes(q)
        );
    }, [searchQuery, subjectWiseSummary]);

    // Helper functions
    const getDaysInMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
    const getFirstDayOfMonth = (date: Date) => new Date(date.getFullYear(), date.getMonth(), 1).getDay();
    const monthName = currentMonth.toLocaleString('default', { month: 'long', year: 'numeric' });

    // Get date range based on view mode
    const getDateRange = () => {
        const today = new Date();
        if (viewMode === 'custom' && customStartDate && customEndDate) {
            return { start: new Date(customStartDate), end: new Date(customEndDate) };
        } else if (viewMode === 'year') {
            return {
                start: new Date(currentMonth.getFullYear(), 0, 1),
                end: new Date(currentMonth.getFullYear(), 11, 31)
            };
        } else if (viewMode === 'quarter') {
            const quarter = Math.floor(currentMonth.getMonth() / 3);
            return {
                start: new Date(currentMonth.getFullYear(), quarter * 3, 1),
                end: new Date(currentMonth.getFullYear(), (quarter + 1) * 3, 0)
            };
        } else {
            return {
                start: new Date(currentMonth.getFullYear(), currentMonth.getMonth(), 1),
                end: new Date(currentMonth.getFullYear(), currentMonth.getMonth() + 1, 0)
            };
        }
    };

    const dateRange = getDateRange();

    // Calculate stats for selected range
    const getRangeStats = () => {
        let present = 0, absent = 0, leave = 0, total = 0;
        for (const [dateStr, record] of Object.entries(monthAttendance)) {
            const date = new Date(dateStr);
            if (date >= dateRange.start && date <= dateRange.end) {
                total++;
                if (record.status === 'P') present++;
                else if (record.status === 'A') absent++;
                else if (record.status === 'L') leave++;
            }
        }
        const rate = total > 0 ? Math.round((present / total) * 100) : 0;
        return { present, absent, leave, total, rate };
    };

    const rangeStats = getRangeStats();

    const getRangeLabel = () => {
        if (viewMode === 'custom') {
            return `${customStartDate} to ${customEndDate}`;
        } else if (viewMode === 'year') {
            return currentMonth.getFullYear().toString();
        } else if (viewMode === 'quarter') {
            const quarter = Math.floor(currentMonth.getMonth() / 3) + 1;
            return `Q${quarter} ${currentMonth.getFullYear()}`;
        }
        return monthName;
    };

    const days = [];
    const firstDay = getFirstDayOfMonth(currentMonth);
    const daysInMonth = getDaysInMonth(currentMonth);

    // Empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        days.push(null);
    }

    // Days of the month
    for (let i = 1; i <= daysInMonth; i++) {
        const dateStr = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), i)
            .toISOString().split('T')[0];
        const attendance = monthAttendance[dateStr];
        days.push({ day: i, attendance });
    }

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

    return (
        <AppLayout title={student.full_name}>
            <Head title={student.full_name} />

            <div className="max-w-7xl mx-auto">
                <div className="page-header flex-col gap-4 sm:flex-row">
                    <div className="flex items-center gap-3">
                        <Link href={route('principal.students.index')} className="btn-ghost btn-icon">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="page-title">{student.full_name}</h1>
                            <p className="page-subtitle">{student.admission_no} · {student.class?.name}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 flex-1 sm:justify-center">
                        <div className="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                placeholder="Search attendance, records..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="form-input pl-10 pr-9"
                            />
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        {searchQuery && (
                            <button
                                onClick={() => setSearchQuery('')}
                                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <XCircleIcon className="w-4 h-4" />
                            </button>
                        )}
                    </div>
                </div>
                <div className="flex gap-2 sm:ml-auto">
                    <Link href={route('principal.students.edit', student.id)} className="btn-secondary">
                        <PencilSquareIcon className="w-4 h-4" /> Edit
                    </Link>
                    <a href={route('principal.students.pdf', student.id)} className="btn-secondary">
                        <DocumentArrowDownIcon className="w-4 h-4" /> Download PDF
                    </a>
                    <button onClick={handleDelete} className="btn-secondary text-red-600 hover:text-red-700">
                        <TrashIcon className="w-4 h-4" /> Delete
                    </button>
                </div>
            </div>

            {/* Search Results Indicator */}
            {searchQuery && (
                <div className="mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-700">
                    <span className="font-medium">Searching for:</span> "{searchQuery}"
                    {filteredAttendanceReport.length > 0 && ` • Found ${filteredAttendanceReport.length} matching attendance record(s)`}
                    {filteredSubjectWiseSummary.length > 0 && ` • Found ${filteredSubjectWiseSummary.length} matching subject(s)`}
                </div>
            )}

            {/* Tab Navigation */}
            <div className="flex gap-2 border-b border-gray-200 mb-5">
                <button
                    onClick={() => setActiveTab('overview')}
                    className={`px-4 py-3 font-medium border-b-2 transition ${
                        activeTab === 'overview'
                            ? 'text-indigo-600 border-indigo-600'
                            : 'text-gray-600 border-transparent hover:text-gray-900'
                    }`}
                >
                    Overview
                </button>
                <button
                    onClick={() => setActiveTab('report')}
                    className={`px-4 py-3 font-medium border-b-2 transition ${
                        activeTab === 'report'
                            ? 'text-indigo-600 border-indigo-600'
                            : 'text-gray-600 border-transparent hover:text-gray-900'
                    }`}
                >
                    Attendance Report
                </button>
            </div>

            {/* Overview Tab */}
            {activeTab === 'overview' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Profile */}
                <div className="card">
                    <div className="card-body flex flex-col items-center text-center gap-4">
                        <div className="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                            {student.photo ? (
                                <img src={`/storage/${student.photo}`} className="w-full h-full rounded-full object-cover" alt="" />
                            ) : initials}
                        </div>
                        <div>
                            <p className="text-lg font-bold text-gray-900">{student.full_name}</p>
                            <p className="text-sm text-gray-500">{student.admission_no}</p>
                            {student.is_orphan && <Badge color="purple" className="mt-1">Orphan</Badge>}
                        </div>
                        <div className="w-full space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Class</span>
                                <span className="font-medium">{student.class?.name}{student.class?.section ? ` — ${student.class.section}` : ''}</span>
                            </div>
                            {student.subject_group && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Group</span>
                                    <span className="font-medium">{student.subject_group.name}</span>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-500">Gender</span>
                                <span className="font-medium capitalize">{student.gender}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">D.O.B</span>
                                <span className="font-medium">{student.dob}</span>
                            </div>
                            {student.blood_group && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Blood Group</span>
                                    <Badge color="red">{student.blood_group}</Badge>
                                </div>
                            )}
                        </div>

                        {/* Attendance Rate */}
                        <div className="w-full bg-gray-50 rounded-xl p-4">
                            <p className="text-xs font-semibold text-gray-500 uppercase mb-2">Attendance Rate</p>
                            <p className="text-3xl font-extrabold text-indigo-600">{attendanceSummary.rate}%</p>
                            <div className="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                                <div className="bg-indigo-500 h-1.5 rounded-full" style={{ width: `${attendanceSummary.rate}%` }} />
                            </div>
                            <div className="grid grid-cols-3 gap-1 mt-3 text-xs text-center">
                                <div className="text-emerald-600">
                                    <p className="font-bold">{attendanceSummary.present}</p>
                                    <p>Present</p>
                                </div>
                                <div className="text-red-600">
                                    <p className="font-bold">{attendanceSummary.absent}</p>
                                    <p>Absent</p>
                                </div>
                                <div className="text-amber-600">
                                    <p className="font-bold">{attendanceSummary.leave}</p>
                                    <p>Leave</p>
                                </div>
                            </div>
                        </div>

                        {/* Attendance Calendar */}
                        <div className="w-full bg-white rounded-xl p-4 border border-gray-200 space-y-4">
                            {/* View Mode Selector */}
                            <div className="flex flex-wrap gap-2">
                                <button
                                    onClick={() => setViewMode('month')}
                                    className={`px-3 py-1.5 text-xs font-medium rounded transition ${
                                        viewMode === 'month'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    Monthly
                                </button>
                                <button
                                    onClick={() => setViewMode('quarter')}
                                    className={`px-3 py-1.5 text-xs font-medium rounded transition ${
                                        viewMode === 'quarter'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    Quarterly
                                </button>
                                <button
                                    onClick={() => setViewMode('year')}
                                    className={`px-3 py-1.5 text-xs font-medium rounded transition ${
                                        viewMode === 'year'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    Yearly
                                </button>
                                <button
                                    onClick={() => setViewMode('custom')}
                                    className={`px-3 py-1.5 text-xs font-medium rounded transition ${
                                        viewMode === 'custom'
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                    }`}
                                >
                                    Custom Range
                                </button>
                            </div>

                            {/* Navigation & Date Range Display */}
                            <div className="flex items-center justify-between">
                                <h3 className="font-semibold text-gray-900">{getRangeLabel()}</h3>
                                <div className="flex gap-2">
                                    {viewMode !== 'custom' && (
                                        <>
                                            <button
                                                onClick={() => {
                                                    if (viewMode === 'year') {
                                                        setCurrentMonth(new Date(currentMonth.getFullYear() - 1, 0, 1));
                                                    } else {
                                                        setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() - (viewMode === 'quarter' ? 3 : 1)));
                                                    }
                                                }}
                                                className="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded"
                                            >
                                                ← Prev
                                            </button>
                                            <button
                                                onClick={() => setCurrentMonth(new Date())}
                                                className="px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 rounded"
                                            >
                                                Today
                                            </button>
                                            <button
                                                onClick={() => {
                                                    if (viewMode === 'year') {
                                                        setCurrentMonth(new Date(currentMonth.getFullYear() + 1, 0, 1));
                                                    } else {
                                                        setCurrentMonth(new Date(currentMonth.getFullYear(), currentMonth.getMonth() + (viewMode === 'quarter' ? 3 : 1)));
                                                    }
                                                }}
                                                className="px-2 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded"
                                            >
                                                Next →
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* Custom Date Range Inputs */}
                            {viewMode === 'custom' && (
                                <div className="flex gap-2 p-3 bg-gray-50 rounded-lg">
                                    <div className="flex-1">
                                        <label className="text-xs font-medium text-gray-600">Start Date</label>
                                        <input
                                            type="date"
                                            value={customStartDate}
                                            onChange={(e) => setCustomStartDate(e.target.value)}
                                            className="w-full px-2 py-1 text-xs border border-gray-300 rounded"
                                        />
                                    </div>
                                    <div className="flex-1">
                                        <label className="text-xs font-medium text-gray-600">End Date</label>
                                        <input
                                            type="date"
                                            value={customEndDate}
                                            onChange={(e) => setCustomEndDate(e.target.value)}
                                            className="w-full px-2 py-1 text-xs border border-gray-300 rounded"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Statistics for Selected Range */}
                            <div className="grid grid-cols-4 gap-2 p-3 bg-gray-50 rounded-lg">
                                <div className="text-center">
                                    <p className="text-xs text-gray-600">Total Days</p>
                                    <p className="text-sm font-bold text-gray-900">{rangeStats.total}</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-xs text-emerald-600 font-medium">Present</p>
                                    <p className="text-sm font-bold text-emerald-700">{rangeStats.present}</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-xs text-red-600 font-medium">Absent</p>
                                    <p className="text-sm font-bold text-red-700">{rangeStats.absent}</p>
                                </div>
                                <div className="text-center">
                                    <p className="text-xs text-amber-600 font-medium">Leave</p>
                                    <p className="text-sm font-bold text-amber-700">{rangeStats.leave}</p>
                                </div>
                            </div>

                            {/* Attendance Rate for Range */}
                            <div className="p-3 bg-indigo-50 rounded-lg">
                                <p className="text-xs text-indigo-600 font-medium mb-2">Attendance Rate (This Period)</p>
                                <div className="flex items-center gap-3">
                                    <div className="flex-1">
                                        <div className="w-full bg-gray-200 rounded-full h-2">
                                            <div className="bg-indigo-500 h-2 rounded-full" style={{ width: `${rangeStats.rate}%` }} />
                                        </div>
                                    </div>
                                    <p className="text-xl font-bold text-indigo-700 w-12 text-right">{rangeStats.rate}%</p>
                                </div>
                            </div>

                            {/* Monthly Calendar (Show only when in month view) */}
                            {viewMode === 'month' && (
                                <>
                                    <div className="border-t pt-4">
                                        <p className="text-xs font-semibold text-gray-600 mb-3">Day-Wise Attendance</p>
                                        <div className="grid grid-cols-7 gap-1">
                                            {/* Day Headers */}
                                            {['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(day => (
                                                <div key={day} className="text-center text-xs font-semibold text-gray-500 py-2">
                                                    {day}
                                                </div>
                                            ))}
                                            {/* Days */}
                                            {(() => {
                                                const calendarDays = [];
                                                const firstDay = getFirstDayOfMonth(currentMonth);
                                                const daysInMonth = getDaysInMonth(currentMonth);

                                                for (let i = 0; i < firstDay; i++) {
                                                    calendarDays.push(null);
                                                }

                                                for (let i = 1; i <= daysInMonth; i++) {
                                                    const dateStr = new Date(currentMonth.getFullYear(), currentMonth.getMonth(), i)
                                                        .toISOString().split('T')[0];
                                                    const attendance = monthAttendance[dateStr];
                                                    calendarDays.push({ day: i, attendance });
                                                }

                                                return calendarDays.map((dayData, idx) => (
                                                    <div
                                                        key={idx}
                                                        className={`aspect-square flex items-center justify-center text-xs font-medium rounded border-2 transition ${
                                                            dayData === null
                                                                ? 'bg-white border-white'
                                                                : `${getStatusColor(dayData.attendance?.status)} border cursor-help`
                                                        }`}
                                                        title={dayData ? getStatusLabel(dayData.attendance?.status) : ''}
                                                    >
                                                        {dayData && dayData.day}
                                                    </div>
                                                ));
                                            })()}
                                        </div>
                                    </div>
                                </>
                            )}

                            {/* Legend */}
                            <div className="flex flex-wrap gap-3 text-xs border-t pt-4">
                                <div className="flex items-center gap-2">
                                    <div className="w-4 h-4 bg-emerald-100 border border-emerald-300 rounded" />
                                    <span className="text-gray-600">Present</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-4 h-4 bg-red-100 border border-red-300 rounded" />
                                    <span className="text-gray-600">Absent</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-4 h-4 bg-amber-100 border border-amber-300 rounded" />
                                    <span className="text-gray-600">Leave</span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <div className="w-4 h-4 bg-gray-50 border border-gray-200 rounded" />
                                    <span className="text-gray-600">No Record</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-2 space-y-5">
                    {/* Guardian */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Guardian Information</p></div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Name</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_name || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Phone</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_phone || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Email</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_email || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Address</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_address || '—'}</dd>
                                </div>
                            </dl>
                            {student.trust_notes && (
                                <div className="mt-4 p-3 bg-purple-50 rounded-xl text-sm text-purple-700">
                                    <p className="font-semibold mb-1">Trust Notes</p>
                                    <p>{student.trust_notes}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Comprehensive Student Information */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Complete Student Information</p></div>
                        <div className="card-body">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                {/* Personal Details */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Personal Details</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Full Name</dt>
                                            <dd className="font-medium mt-0.5">{student.full_name}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Admission No.</dt>
                                            <dd className="font-medium mt-0.5">{student.admission_no}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Gender</dt>
                                            <dd className="font-medium mt-0.5 capitalize">{student.gender || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Date of Birth</dt>
                                            <dd className="font-medium mt-0.5">{student.dob || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Student CNIC</dt>
                                            <dd className="font-medium mt-0.5">{student.student_cnic || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Blood Group</dt>
                                            <dd className="font-medium mt-0.5">{student.blood_group || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Family Details */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Family Details</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Father Name</dt>
                                            <dd className="font-medium mt-0.5">{student.father_name || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Father CNIC</dt>
                                            <dd className="font-medium mt-0.5">{student.father_cnic || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Mother Name</dt>
                                            <dd className="font-medium mt-0.5">{student.mother_name || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Mother CNIC</dt>
                                            <dd className="font-medium mt-0.5">{student.mother_cnic || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Guardian Details */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Guardian Details</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Guardian Name</dt>
                                            <dd className="font-medium mt-0.5">{student.guardian_name || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Relation</dt>
                                            <dd className="font-medium mt-0.5">{student.guardian_relation || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Guardian Phone</dt>
                                            <dd className="font-medium mt-0.5">{student.guardian_phone || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Guardian CNIC</dt>
                                            <dd className="font-medium mt-0.5">{student.guardian_cnic || '—'}</dd>
                                        </div>
                                        <div className="md:col-span-2">
                                            <dt className="text-gray-500 text-xs">Guardian Address</dt>
                                            <dd className="font-medium mt-0.5">{student.guardian_address || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Contact Information */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Contact Information</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Phone</dt>
                                            <dd className="font-medium mt-0.5">{student.phone || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Email</dt>
                                            <dd className="font-medium mt-0.5">{student.email || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Academic Information */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Academic Information</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Class</dt>
                                            <dd className="font-medium mt-0.5">{student.class?.name || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Group/Stream</dt>
                                            <dd className="font-medium mt-0.5">{student.subject_group?.group_name || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Semester</dt>
                                            <dd className="font-medium mt-0.5">{student.semester || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Previous School</dt>
                                            <dd className="font-medium mt-0.5">{student.previous_school || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Personal Preferences */}
                                <div className="md:col-span-2 pb-4 border-b">
                                    <p className="font-semibold text-gray-900 mb-3">Personal Preferences</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Favorite Color</dt>
                                            <dd className="font-medium mt-0.5">{student.favorite_color || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Favorite Food</dt>
                                            <dd className="font-medium mt-0.5">{student.favorite_food || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Favorite Subject</dt>
                                            <dd className="font-medium mt-0.5">{student.favorite_subject || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Ambition</dt>
                                            <dd className="font-medium mt-0.5">{student.ambition || '—'}</dd>
                                        </div>
                                    </div>
                                </div>

                                {/* Enrollment Information */}
                                <div className="md:col-span-2">
                                    <p className="font-semibold text-gray-900 mb-3">Enrollment Information</p>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <dt className="text-gray-500 text-xs">Joining Date</dt>
                                            <dd className="font-medium mt-0.5">{student.join_date_kort || '—'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500 text-xs">Leaving Date</dt>
                                            <dd className="font-medium mt-0.5">{student.leaving_date || '—'}</dd>
                                        </div>
                                        <div className="md:col-span-2">
                                            <dt className="text-gray-500 text-xs">Reason Left</dt>
                                            <dd className="font-medium mt-0.5">{student.reason_left_kort || '—'}</dd>
                                        </div>
                                        {student.is_orphan && (
                                            <div className="md:col-span-2">
                                                <Badge color="purple">Orphan Status</Badge>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Results */}
                    {student.results && student.results.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title flex items-center gap-2"><AcademicCapIcon className="w-4 h-4" /> Academic Results</p>
                            </div>
                            <div className="table-wrapper">
                                <table className="table">
                                    <thead>
                                        <tr><th>Subject</th><th>Exam</th><th>Marks</th><th>%</th><th>Grade</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        {student.results.map(r => (
                                            <tr key={r.id}>
                                                <td className="font-medium">{r.subject?.name}</td>
                                                <td>{r.exam_type}</td>
                                                <td>{r.obtained_marks}/{r.total_marks}</td>
                                                <td>{r.percentage}%</td>
                                                <td><Badge color={r.grade === 'A' ? 'green' : r.grade === 'F' ? 'red' : 'blue'}>{r.grade}</Badge></td>
                                                <td><Badge color={r.approval_status === 'approved' ? 'green' : r.approval_status === 'rejected' ? 'red' : 'yellow'}>{r.approval_status === 'approved' ? 'Approved' : r.approval_status === 'rejected' ? 'Rejected' : 'Pending'}</Badge></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Discipline */}
                    {student.discipline_records && student.discipline_records.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title flex items-center gap-2"><ShieldExclamationIcon className="w-4 h-4" /> Discipline Records</p>
                            </div>
                            <div className="table-wrapper">
                                <table className="table">
                                    <thead>
                                        <tr><th>Date</th><th>Type</th><th>Description</th><th>Severity</th></tr>
                                    </thead>
                                    <tbody>
                                        {student.discipline_records.map(d => (
                                            <tr key={d.id}>
                                                <td>{d.incident_date}</td>
                                                <td className="font-medium">{d.title}</td>
                                                <td className="capitalize">{d.category}</td>
                                                <td>
                                                    <Badge color={d.severity === 'high' ? 'red' : d.severity === 'medium' ? 'yellow' : 'gray'}>
                                                        {d.severity}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Documents */}
                    {student.documents && student.documents.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title flex items-center gap-2"><DocumentArrowDownIcon className="w-4 h-4" /> Documents</p>
                            </div>
                            <div className="card-body">
                                <div className="space-y-2">
                                    {student.documents.map(doc => (
                                        <div key={doc.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                            <div className="flex-1">
                                                <p className="font-medium text-gray-900">{doc.document_name}</p>
                                                <p className="text-xs text-gray-500">{doc.document_type}</p>
                                            </div>
                                            {doc.file_path && (
                                                <a
                                                    href={`/storage/${doc.file_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="text-indigo-600 hover:text-indigo-700 font-medium text-sm"
                                                >
                                                    Download
                                                </a>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
            )}

                {/* Attendance Report Tab */}
                {activeTab === 'report' && (
                    <AttendanceReport
                        attendanceSummary={attendanceSummary}
                        attendanceReport={attendanceReport}
                        subjectWiseSummary={subjectWiseSummary}
                        monthWiseSummary={monthWiseSummary}
                    />
                )}
            </div>
        </AppLayout>
    );
}
