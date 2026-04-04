import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, PencilSquareIcon, CalendarDaysIcon, AcademicCapIcon, ShieldExclamationIcon, DocumentArrowDownIcon, TrashIcon, MagnifyingGlassIcon, XCircleIcon, XMarkIcon } from '@heroicons/react/24/outline';
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
    resultsSummary: any[];
    resultsBySubject: any[];
    resultsByAcademicYear: any[];
    disciplineSummary: any[];
    disciplineStats: { total: number; warnings: number; achievements: number; suspensions: number; others: number };
    timeline: any[];
}

export default function PrincipalStudentShow({ student, attendanceSummary, monthAttendance, attendanceReport, subjectWiseSummary, monthWiseSummary, resultsSummary, resultsBySubject, resultsByAcademicYear, disciplineSummary, disciplineStats, timeline }: Props) {
    const initials = student.full_name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const [currentMonth, setCurrentMonth] = React.useState(new Date());
    const [viewMode, setViewMode] = React.useState<'month' | 'quarter' | 'year' | 'custom'>('month');
    const [customStartDate, setCustomStartDate] = React.useState('');
    const [customEndDate, setCustomEndDate] = React.useState('');
    const [activeTab, setActiveTab] = React.useState<'overview' | 'report' | 'results' | 'discipline' | 'timeline'>('overview');
    const [searchQuery, setSearchQuery] = useState('');
    const [showImageModal, setShowImageModal] = useState(false);

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
                    <div className="flex items-center gap-3 flex-1">
                        <Link href={route('principal.students.index')} className="btn-ghost btn-icon">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="page-title">{student.full_name}</h1>
                            <p className="page-subtitle">
                                {student.admission_no} · {student.class?.name}
                                {student.subject_group && (
                                    <span className="ml-2 inline-block px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-semibold">
                                        {student.subject_group.group_name}
                                    </span>
                                )}
                            </p>
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
            <div className="flex gap-2 border-b border-gray-200 mb-5 overflow-x-auto">
                {['overview', 'report', 'results', 'discipline', 'timeline'].map((tab) => (
                    <button
                        key={tab}
                        onClick={() => setActiveTab(tab as any)}
                        className={`px-4 py-3 font-medium border-b-2 transition whitespace-nowrap ${
                            activeTab === tab
                                ? 'text-indigo-600 border-indigo-600'
                                : 'text-gray-600 border-transparent hover:text-gray-900'
                        }`}
                    >
                        {tab === 'overview' && 'Overview'}
                        {tab === 'report' && 'Attendance Report'}
                        {tab === 'results' && 'Academic Results'}
                        {tab === 'discipline' && 'Discipline'}
                        {tab === 'timeline' && 'History'}
                    </button>
                ))}
            </div>

            {/* Overview Tab */}
            {activeTab === 'overview' && (
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Profile */}
                <div className="card">
                    <div className="card-body flex flex-col items-center text-center gap-4">
                        <div
                            className="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600 cursor-pointer hover:ring-4 hover:ring-indigo-300 transition"
                            onClick={() => student.photo && setShowImageModal(true)}
                        >
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
                            {student.subject_group ? (
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500">Stream/Group</span>
                                    <span className="font-bold px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs">{student.subject_group.group_name}</span>
                                </div>
                            ) : null}
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

                        {/* Academic Year & Session */}
                        <div className="w-full bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-4 border-2 border-indigo-300">
                            <p className="text-xs font-semibold text-indigo-600 uppercase mb-3">📚 Academic Information</p>
                            <div className="space-y-3">
                                <div>
                                    <p className="text-xs text-indigo-600 font-medium">Current Session</p>
                                    <p className="text-lg font-bold text-indigo-700">{student.class?.academic_year || 'N/A'}</p>
                                </div>
                                <div>
                                    <p className="text-xs text-indigo-600 font-medium">Class</p>
                                    <p className="text-sm font-semibold text-gray-900">{student.class?.class || 'N/A'}{student.class?.section ? ` - ${student.class.section}` : ''}</p>
                                </div>
                                {student.subject_group ? (
                                    <div className="pt-2 border-t border-indigo-200">
                                        <p className="text-xs text-indigo-600 font-medium mb-1">🎯 Group/Stream</p>
                                        <div className="inline-block px-4 py-2 bg-indigo-600 text-white rounded-lg font-bold text-sm">
                                            {student.subject_group.group_name}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="pt-2 border-t border-indigo-200">
                                        <p className="text-xs text-gray-500">No group/stream assigned</p>
                                    </div>
                                )}
                                <div>
                                    <p className="text-xs text-indigo-600 font-medium">Joined KORT</p>
                                    <p className="text-sm font-semibold text-gray-900">{student.join_date_kort ? new Date(student.join_date_kort).toLocaleDateString() : 'N/A'}</p>
                                </div>
                            </div>
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

                {/* Academic Results Tab */}
                {activeTab === 'results' && (
                    <div className="space-y-6">
                        {/* Results by Academic Year */}
                        {resultsByAcademicYear.length > 0 ? (
                            resultsByAcademicYear.map((yearData: any) => (
                                <div key={yearData.academic_year}>
                                    {/* Year Header */}
                                    <div className="mb-4 pb-3 border-b-2 border-indigo-600">
                                        <h3 className="text-2xl font-bold text-indigo-600">Session: {yearData.academic_year}</h3>
                                    </div>

                                    {/* Results Table for Year */}
                                    <div className="card mb-6">
                                        <div className="card-body">
                                            {yearData.results.length > 0 ? (
                                                <div className="overflow-x-auto">
                                                    <table className="w-full text-sm">
                                                        <thead className="bg-gray-50 border-b">
                                                            <tr>
                                                                <th className="px-4 py-2 text-left font-semibold text-gray-700">Subject</th>
                                                                <th className="px-4 py-2 text-left font-semibold text-gray-700">Exam Type</th>
                                                                <th className="px-4 py-2 text-left font-semibold text-gray-700">Term</th>
                                                                <th className="px-4 py-2 text-center font-semibold text-gray-700">Marks</th>
                                                                <th className="px-4 py-2 text-center font-semibold text-gray-700">%</th>
                                                                <th className="px-4 py-2 text-center font-semibold text-gray-700">Grade</th>
                                                                <th className="px-4 py-2 text-center font-semibold text-gray-700">GPA</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody className="divide-y">
                                                            {yearData.results.map((result: any) => (
                                                                <tr key={result.id} className="hover:bg-gray-50">
                                                                    <td className="px-4 py-3 font-medium text-gray-900">{result.subject_name}</td>
                                                                    <td className="px-4 py-3 text-gray-600">{result.exam_type}</td>
                                                                    <td className="px-4 py-3 text-gray-600">{result.term}</td>
                                                                    <td className="px-4 py-3 text-center text-gray-700">{result.obtained_marks}/{result.total_marks}</td>
                                                                    <td className="px-4 py-3 text-center">
                                                                        <span className="font-semibold text-indigo-600">{result.percentage}%</span>
                                                                    </td>
                                                                    <td className="px-4 py-3 text-center">
                                                                        <span className="inline-block px-3 py-1 rounded-full font-bold text-white" style={{
                                                                            backgroundColor: result.grade === 'A' ? '#10b981' : result.grade === 'B' ? '#3b82f6' : result.grade === 'C' ? '#f59e0b' : result.grade === 'D' ? '#f97316' : '#ef4444'
                                                                        }}>
                                                                            {result.grade}
                                                                        </span>
                                                                    </td>
                                                                    <td className="px-4 py-3 text-center font-medium text-gray-900">{result.gpa_point ? parseFloat(result.gpa_point).toFixed(2) : '—'}</td>
                                                                </tr>
                                                            ))}
                                                        </tbody>
                                                    </table>
                                                </div>
                                            ) : (
                                                <p className="text-gray-500 text-center py-4">No results for this session</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))
                        ) : (
                            <div className="card">
                                <div className="card-body text-center py-8">
                                    <p className="text-gray-500">No academic results available</p>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Discipline Tab */}
                {activeTab === 'discipline' && (
                    <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        {/* Statistics */}
                        <div className="grid grid-cols-2 lg:grid-cols-1 gap-4">
                            <div className="card bg-blue-50">
                                <div className="card-body text-center">
                                    <p className="text-3xl font-bold text-blue-600">{disciplineStats.total}</p>
                                    <p className="text-sm text-blue-700 font-medium">Total Records</p>
                                </div>
                            </div>
                            <div className="card bg-green-50">
                                <div className="card-body text-center">
                                    <p className="text-3xl font-bold text-green-600">{disciplineStats.achievements}</p>
                                    <p className="text-sm text-green-700 font-medium">Achievements</p>
                                </div>
                            </div>
                            <div className="card bg-yellow-50">
                                <div className="card-body text-center">
                                    <p className="text-3xl font-bold text-yellow-600">{disciplineStats.warnings}</p>
                                    <p className="text-sm text-yellow-700 font-medium">Warnings</p>
                                </div>
                            </div>
                            <div className="card bg-red-50">
                                <div className="card-body text-center">
                                    <p className="text-3xl font-bold text-red-600">{disciplineStats.suspensions}</p>
                                    <p className="text-sm text-red-700 font-medium">Suspensions</p>
                                </div>
                            </div>
                        </div>

                        {/* Records List */}
                        <div className="lg:col-span-3">
                            <div className="card">
                                <div className="card-header">
                                    <p className="card-title">Discipline Records</p>
                                </div>
                                <div className="card-body">
                                    {disciplineSummary.length > 0 ? (
                                        <div className="space-y-3">
                                            {disciplineSummary.map((record: any) => (
                                                <div key={record.id} className="p-4 border-l-4 bg-gray-50 rounded"
                                                    style={{ borderColor: record.severity === 'high' ? '#ef4444' : record.severity === 'medium' ? '#f59e0b' : '#3b82f6' }}>
                                                    <div className="flex items-start justify-between mb-2">
                                                        <h4 className="font-semibold text-gray-900">{record.title}</h4>
                                                        <span className={`px-2 py-1 text-xs font-medium rounded capitalize ${
                                                            record.category === 'achievement' ? 'bg-green-100 text-green-700' :
                                                            record.category === 'warning' ? 'bg-yellow-100 text-yellow-700' :
                                                            record.category === 'suspension' ? 'bg-red-100 text-red-700' :
                                                            'bg-gray-100 text-gray-700'
                                                        }`}>
                                                            {record.category}
                                                        </span>
                                                    </div>
                                                    {record.description && <p className="text-sm text-gray-600 mb-2">{record.description}</p>}
                                                    <div className="flex gap-4 text-xs text-gray-500">
                                                        <span>Date: {new Date(record.incident_date).toLocaleDateString()}</span>
                                                        <span className={`font-medium capitalize ${
                                                            record.severity === 'high' ? 'text-red-600' :
                                                            record.severity === 'medium' ? 'text-yellow-600' : 'text-blue-600'
                                                        }`}>{record.severity} severity</span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    ) : (
                                        <p className="text-gray-500 text-center py-8">No discipline records</p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}

                {/* History Timeline Tab */}
                {activeTab === 'timeline' && (
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Student Timeline</p>
                        </div>
                        <div className="card-body">
                            {timeline.length > 0 ? (
                                <div className="relative">
                                    {/* Timeline line */}
                                    <div className="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-600 via-indigo-400 to-gray-200" />

                                    {/* Timeline items */}
                                    <div className="space-y-6">
                                        {timeline.map((event: any, idx: number) => (
                                            <div key={idx} className="pl-20 relative">
                                                {/* Dot */}
                                                <div className="absolute left-0 w-14 h-14 flex items-center justify-center text-2xl">
                                                    <div className="w-14 h-14 rounded-full bg-white border-4 border-indigo-600 flex items-center justify-center">
                                                        {event.icon}
                                                    </div>
                                                </div>

                                                {/* Content */}
                                                <div className="p-4 bg-gradient-to-r from-indigo-50 to-white rounded-lg border border-indigo-100">
                                                    <div className="flex items-start justify-between mb-1">
                                                        <h4 className="font-semibold text-gray-900">{event.title}</h4>
                                                        <span className={`px-2.5 py-0.5 text-xs font-semibold rounded-full text-white ${event.color}`}>
                                                            {event.badge}
                                                        </span>
                                                    </div>
                                                    {event.subtitle && <p className="text-sm text-gray-600 mb-2">{event.subtitle}</p>}
                                                    <p className="text-xs text-gray-500">{new Date(event.date).toLocaleDateString()} • {new Date(event.date).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <p className="text-gray-500 text-center py-8">No timeline events</p>
                            )}
                        </div>
                    </div>
                )}

                {/* Image Popup Modal */}
                {showImageModal && student.photo && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4" onClick={() => setShowImageModal(false)}>
                        <div className="relative max-w-2xl max-h-[90vh]" onClick={(e) => e.stopPropagation()}>
                            {/* Close Button */}
                            <button
                                onClick={() => setShowImageModal(false)}
                                className="absolute -top-10 right-0 text-white hover:text-gray-300 transition"
                            >
                                <XMarkIcon className="w-8 h-8" />
                            </button>

                            {/* Image */}
                            <img
                                src={`/storage/${student.photo}`}
                                alt={student.full_name}
                                className="w-full h-auto rounded-lg shadow-2xl max-h-[90vh] object-contain"
                            />

                            {/* Student Info Below Image */}
                            <div className="mt-4 bg-white rounded-lg p-4 text-center">
                                <p className="text-lg font-bold text-gray-900">{student.full_name}</p>
                                <p className="text-sm text-gray-600">{student.admission_no}</p>
                                <p className="text-sm text-gray-500 mt-1">{student.class?.name}{student.class?.section ? ` — ${student.class.section}` : ''}</p>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
