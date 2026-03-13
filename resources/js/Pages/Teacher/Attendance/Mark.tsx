import React, { useState, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { CheckCircleIcon, XCircleIcon, MinusCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, SchoolClass, Student } from '@/types';

interface Props extends PageProps {
    allClasses: SchoolClass[];
    selectedClass: SchoolClass | null;
    students: Student[];
    existing: Record<number, string>;
    date: string;
    canMark: boolean;
    canMarkAttendance: number[];
    canViewOnly: number[];
}

type Status = 'P' | 'A' | 'L';

const STATUS_CONFIG: Record<Status, { label: string; cls: string; icon: React.ComponentType<{ className?: string }> }> = {
    P: { label: 'Present', cls: 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-500/30', icon: CheckCircleIcon },
    A: { label: 'Absent',  cls: 'bg-red-100 text-red-700 ring-1 ring-red-500/30',         icon: XCircleIcon },
    L: { label: 'Leave',   cls: 'bg-amber-100 text-amber-700 ring-1 ring-amber-500/30',    icon: MinusCircleIcon },
};

export default function MarkAttendance({ allClasses, selectedClass, students, existing, date, canMark, canMarkAttendance, canViewOnly }: Props) {
    const [attendance, setAttendance] = useState<Record<number, Status>>(() => {
        const init: Record<number, Status> = {};
        students.forEach(s => { init[s.id] = (existing[s.id] as Status) ?? 'P'; });
        return init;
    });
    const [submitting, setSubmitting] = useState(false);
    const [selectedDate, setSelectedDate] = useState(date);

    useEffect(() => {
        const init: Record<number, Status> = {};
        students.forEach(s => { init[s.id] = (existing[s.id] as Status) ?? 'P'; });
        setAttendance(init);
    }, [students, existing]);

    const handleClassChange = (classId: string) => {
        router.get(route('teacher.attendance.index'), { class_id: classId, date: selectedDate }, { preserveState: false });
    };

    const handleDateChange = (d: string) => {
        setSelectedDate(d);
        if (selectedClass) {
            router.get(route('teacher.attendance.index'), { class_id: selectedClass.id, date: d }, { preserveState: false });
        }
    };

    const markAll = (status: Status) => {
        const updated: Record<number, Status> = {};
        students.forEach(s => { updated[s.id] = status; });
        setAttendance(updated);
    };

    const presentCount = Object.values(attendance).filter(s => s === 'P').length;
    const absentCount  = Object.values(attendance).filter(s => s === 'A').length;
    const leaveCount   = Object.values(attendance).filter(s => s === 'L').length;

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedClass) return;
        setSubmitting(true);
        router.post(route('teacher.attendance.store'), {
            class_id:   selectedClass.id,
            date:       selectedDate,
            attendance,
        }, {
            onFinish: () => setSubmitting(false),
        });
    };

    const handleReportAbsence = () => {
        if (!selectedClass) return;
        const absentStudentIds = Object.entries(attendance)
            .filter(([_, status]) => status === 'A')
            .map(([studentId, _]) => parseInt(studentId));

        if (absentStudentIds.length === 0) return;

        setSubmitting(true);
        router.post(route('teacher.attendance.report-absence'), {
            class_id: selectedClass.id,
            date: selectedDate,
            student_ids: absentStudentIds,
            reason: '',
        }, {
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AppLayout title="Mark Attendance">
            <Head title="Mark Attendance" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Attendance Management</h1>
                    <p className="page-subtitle">
                        {canMark
                            ? 'Mark and manage student attendance for your homeroom class'
                            : 'View attendance and report absences to class teacher and principal'}
                    </p>
                </div>
            </div>

            {/* Role Indicator */}
            <div className={`card mb-5 ${canMark ? 'bg-emerald-50 border-l-4 border-emerald-500' : 'bg-blue-50 border-l-4 border-blue-500'}`}>
                <div className="card-body">
                    <p className={canMark ? 'text-emerald-700 font-semibold' : 'text-blue-700 font-semibold'}>
                        {canMark
                            ? '✓ You are a CLASS TEACHER - You can mark attendance'
                            : 'ℹ️ You are a SUBJECT TEACHER - You can view attendance and report absences'}
                    </p>
                </div>
            </div>

            {/* Selectors */}
            <div className="card mb-5">
                <div className="card-body !py-4 flex flex-wrap gap-4 items-center">
                    <div className="form-group">
                        <label className="form-label">Class</label>
                        <select
                            className="form-select w-48"
                            value={selectedClass?.id ?? ''}
                            onChange={e => handleClassChange(e.target.value)}
                        >
                            <option value="">Select class…</option>
                            {allClasses.map(c => (
                                <option key={c.id} value={c.id}>
                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Date</label>
                        <input
                            type="date"
                            className="form-input w-48"
                            value={selectedDate}
                            onChange={e => handleDateChange(e.target.value)}
                            max={new Date().toISOString().split('T')[0]}
                        />
                    </div>
                    {selectedClass && students.length > 0 && canMark && (
                        <div className="ml-auto flex gap-2 pt-5">
                            <button type="button" onClick={() => markAll('P')} className="btn-success btn-sm">
                                All Present
                            </button>
                            <button type="button" onClick={() => markAll('A')} className="btn-danger btn-sm">
                                All Absent
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {selectedClass ? (
                <form onSubmit={handleSubmit}>
                    {/* Summary */}
                    <div className="grid grid-cols-3 gap-4 mb-5">
                        <div className="stat-card text-center">
                            <p className="text-3xl font-extrabold text-emerald-600">{presentCount}</p>
                            <p className="stat-card-label">Present</p>
                        </div>
                        <div className="stat-card text-center">
                            <p className="text-3xl font-extrabold text-red-600">{absentCount}</p>
                            <p className="stat-card-label">Absent</p>
                        </div>
                        <div className="stat-card text-center">
                            <p className="text-3xl font-extrabold text-amber-600">{leaveCount}</p>
                            <p className="stat-card-label">On Leave</p>
                        </div>
                    </div>

                    {students.length === 0 ? (
                        <div className="card">
                            <div className="card-body empty-state">
                                <p className="empty-state-text">No students in this class</p>
                            </div>
                        </div>
                    ) : (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title">{selectedClass.name}{selectedClass.section ? ` — ${selectedClass.section}` : ''}</p>
                                <span className="badge-blue badge">{students.length} students</span>
                            </div>
                            <div className="divide-y divide-gray-50">
                                {students.map(student => {
                                    const status = attendance[student.id] ?? 'P';
                                    return (
                                        <div key={student.id} className="flex items-center gap-4 px-5 py-3.5">
                                            <div className={`avatar-sm ${status === 'P' ? 'bg-emerald-500' : status === 'A' ? 'bg-red-500' : 'bg-amber-500'}`}>
                                                {student.full_name.charAt(0)}
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-medium text-gray-900 text-sm">{student.full_name}</p>
                                                <p className="text-xs text-gray-400">{student.admission_no}</p>
                                            </div>
                                            <div className="flex gap-1.5">
                                                {(['P', 'A', 'L'] as Status[]).map(s => {
                                                    const cfg = STATUS_CONFIG[s];
                                                    const active = status === s;
                                                    return (
                                                        <button
                                                            key={s}
                                                            type="button"
                                                            disabled={!canMark && s !== 'A'}
                                                            onClick={() => setAttendance(prev => ({ ...prev, [student.id]: s }))}
                                                            title={!canMark && s !== 'A' ? 'Only subject teachers can mark as Absent to report' : ''}
                                                            className={`flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all ${
                                                                !canMark && s !== 'A' ? 'opacity-50 cursor-not-allowed bg-gray-50 text-gray-300' :
                                                                active ? cfg.cls : 'bg-gray-50 text-gray-400 hover:bg-gray-100'
                                                            }`}
                                                        >
                                                            <cfg.icon className="w-3.5 h-3.5" />
                                                            {cfg.label}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                            <div className="card-footer">
                                <p className="text-xs text-gray-400">{students.length} students</p>
                                {canMark ? (
                                    <button type="submit" disabled={submitting} className="btn-primary">
                                        {submitting ? (
                                            <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                        ) : (
                                            <>Save Attendance</>
                                        )}
                                    </button>
                                ) : (
                                    <div className="text-sm text-gray-500">
                                        Only class teachers can mark attendance. You can view attendance as a subject teacher.
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Class Teacher Absence Report */}
                    {canMark && absentCount > 0 && (
                        <div className="mt-5 card bg-red-50 border-l-4 border-red-500">
                            <div className="card-body">
                                <div className="flex items-start gap-3">
                                    <XCircleIcon className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="font-semibold text-red-900">Absence Report</p>
                                        <p className="text-sm text-red-700 mt-2">
                                            The following {absentCount} student(s) will be reported as absent to principal:
                                        </p>
                                        <ul className="list-inside list-disc mt-2 space-y-1">
                                            {students
                                                .filter(s => attendance[s.id] === 'A')
                                                .map(s => (
                                                    <li key={s.id} className="text-sm text-red-800">
                                                        <span className="font-medium">{s.full_name}</span> ({s.admission_no})
                                                    </li>
                                                ))}
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Subject Teacher Absence Report */}
                    {!canMark && absentCount > 0 && (
                        <div className="mt-5 card bg-blue-50 border-l-4 border-blue-500">
                            <div className="card-body">
                                <div className="flex items-start gap-3">
                                    <XCircleIcon className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="font-semibold text-blue-900">Report Absences</p>
                                        <p className="text-sm text-blue-700 mt-2">
                                            The following {absentCount} student(s) are absent and can be reported to the class teacher and principal:
                                        </p>
                                        <ul className="list-inside list-disc mt-2 space-y-1">
                                            {students
                                                .filter(s => attendance[s.id] === 'A')
                                                .map(s => (
                                                    <li key={s.id} className="text-sm text-blue-800">
                                                        <span className="font-medium">{s.full_name}</span> ({s.admission_no})
                                                    </li>
                                                ))}
                                        </ul>
                                        <button type="button" onClick={handleReportAbsence} disabled={submitting} className="btn-primary mt-3">
                                            {submitting ? (
                                                <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Reporting…</>
                                            ) : (
                                                <>Report to Class Teacher & Principal</>
                                            )}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </form>
            ) : (
                <div className="card">
                    <div className="card-body empty-state">
                        <p className="empty-state-text">Select a class and date to start marking attendance</p>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
