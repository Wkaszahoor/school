import React from 'react';
import Badge from '@/Components/Badge';

interface AttendanceRecord {
    id: number;
    attendance_date: string;
    status: string;
    remarks?: string;
    subject_name: string;
    subject_id?: number;
    month: string;
    year: string;
}

interface SubjectSummary {
    subject_id?: number;
    subject_name: string;
    total: number;
    present: number;
    absent: number;
    leave: number;
    rate: number;
}

interface MonthSummary {
    month: string;
    month_label: string;
    total: number;
    present: number;
    absent: number;
    leave: number;
    rate: number;
}

interface Props {
    attendanceSummary: {
        total: number;
        present: number;
        absent: number;
        leave: number;
        rate: number;
    };
    attendanceReport: AttendanceRecord[];
    subjectWiseSummary: SubjectSummary[];
    monthWiseSummary: MonthSummary[];
}

export default function AttendanceReport({
    attendanceSummary,
    attendanceReport,
    subjectWiseSummary,
    monthWiseSummary,
}: Props) {
    const [reportFilter, setReportFilter] = React.useState<'all' | 'month' | 'subject'>('all');
    const [selectedMonth, setSelectedMonth] = React.useState('');
    const [selectedSubject, setSelectedSubject] = React.useState('');

    const getFilteredReport = () => {
        let filtered = [...attendanceReport];

        if (reportFilter === 'month' && selectedMonth) {
            filtered = filtered.filter(r => r.month === selectedMonth);
        } else if (reportFilter === 'subject' && selectedSubject) {
            filtered = filtered.filter(r => r.subject_id?.toString() === selectedSubject || (!selectedSubject && !r.subject_id));
        }

        return filtered;
    };

    const filteredReport = getFilteredReport();

    const getStatusLabel = (status?: string) => {
        switch (status) {
            case 'P': return 'Present';
            case 'A': return 'Absent';
            case 'L': return 'Leave';
            default: return 'No record';
        }
    };

    return (
        <div className="space-y-5">
            {/* Summary Cards */}
            <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div className="card p-4 text-center">
                    <p className="text-xs text-gray-600">Total Days</p>
                    <p className="text-2xl font-bold text-gray-900">{attendanceSummary.total}</p>
                </div>
                <div className="card p-4 text-center bg-emerald-50">
                    <p className="text-xs text-emerald-600 font-medium">Present</p>
                    <p className="text-2xl font-bold text-emerald-700">{attendanceSummary.present}</p>
                </div>
                <div className="card p-4 text-center bg-red-50">
                    <p className="text-xs text-red-600 font-medium">Absent</p>
                    <p className="text-2xl font-bold text-red-700">{attendanceSummary.absent}</p>
                </div>
                <div className="card p-4 text-center bg-amber-50">
                    <p className="text-xs text-amber-600 font-medium">Leave</p>
                    <p className="text-2xl font-bold text-amber-700">{attendanceSummary.leave}</p>
                </div>
                <div className="card p-4 text-center bg-indigo-50">
                    <p className="text-xs text-indigo-600 font-medium">Attendance Rate</p>
                    <p className="text-2xl font-bold text-indigo-700">{attendanceSummary.rate}%</p>
                </div>
            </div>

            {/* Subject-wise Breakdown */}
            {subjectWiseSummary.length > 0 && (
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Subject-wise Attendance</p>
                    </div>
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr><th>Subject</th><th>Total</th><th>Present</th><th>Absent</th><th>Leave</th><th>Rate</th></tr>
                            </thead>
                            <tbody>
                                {subjectWiseSummary.map((subject, idx) => (
                                    <tr key={idx}>
                                        <td className="font-medium">{subject.subject_name}</td>
                                        <td>{subject.total}</td>
                                        <td className="text-emerald-600 font-medium">{subject.present}</td>
                                        <td className="text-red-600 font-medium">{subject.absent}</td>
                                        <td className="text-amber-600 font-medium">{subject.leave}</td>
                                        <td><Badge color={subject.rate >= 75 ? 'green' : subject.rate >= 50 ? 'yellow' : 'red'}>{subject.rate}%</Badge></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Month-wise Breakdown */}
            {monthWiseSummary.length > 0 && (
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Month-wise Attendance</p>
                    </div>
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr><th>Month</th><th>Total</th><th>Present</th><th>Absent</th><th>Leave</th><th>Rate</th></tr>
                            </thead>
                            <tbody>
                                {monthWiseSummary.map((month, idx) => (
                                    <tr key={idx}>
                                        <td className="font-medium">{month.month_label}</td>
                                        <td>{month.total}</td>
                                        <td className="text-emerald-600 font-medium">{month.present}</td>
                                        <td className="text-red-600 font-medium">{month.absent}</td>
                                        <td className="text-amber-600 font-medium">{month.leave}</td>
                                        <td><Badge color={month.rate >= 75 ? 'green' : month.rate >= 50 ? 'yellow' : 'red'}>{month.rate}%</Badge></td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* Filter Controls */}
            <div className="card">
                <div className="card-header">
                    <p className="card-title">Day-wise Attendance Details</p>
                </div>
                <div className="card-body space-y-4">
                    <div className="flex gap-3 flex-wrap">
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                checked={reportFilter === 'all'}
                                onChange={() => setReportFilter('all')}
                                className="w-4 h-4"
                            />
                            <span className="text-sm">All Records</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                checked={reportFilter === 'month'}
                                onChange={() => setReportFilter('month')}
                                className="w-4 h-4"
                            />
                            <span className="text-sm">Filter by Month</span>
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                checked={reportFilter === 'subject'}
                                onChange={() => setReportFilter('subject')}
                                className="w-4 h-4"
                            />
                            <span className="text-sm">Filter by Subject</span>
                        </label>
                    </div>

                    {reportFilter === 'month' && (
                        <div>
                            <label className="form-label">Select Month</label>
                            <select
                                value={selectedMonth}
                                onChange={(e) => setSelectedMonth(e.target.value)}
                                className="form-select"
                            >
                                <option value="">All Months</option>
                                {monthWiseSummary.map((m) => (
                                    <option key={m.month} value={m.month}>{m.month_label}</option>
                                ))}
                            </select>
                        </div>
                    )}

                    {reportFilter === 'subject' && (
                        <div>
                            <label className="form-label">Select Subject</label>
                            <select
                                value={selectedSubject}
                                onChange={(e) => setSelectedSubject(e.target.value)}
                                className="form-select"
                            >
                                <option value="">All Subjects</option>
                                {subjectWiseSummary.map((s, idx) => (
                                    <option key={idx} value={s.subject_id?.toString() || 'general'}>{s.subject_name}</option>
                                ))}
                            </select>
                        </div>
                    )}
                </div>
            </div>

            {/* Detailed Report Table */}
            <div className="card">
                <div className="card-body">
                    <p className="text-sm font-semibold text-gray-900 mb-4">
                        Showing {filteredReport.length} of {attendanceReport.length} records
                    </p>
                    <div className="table-wrapper overflow-x-auto">
                        <table className="table">
                            <thead>
                                <tr><th>Date</th><th>Day</th><th>Subject</th><th>Status</th><th>Remarks</th></tr>
                            </thead>
                            <tbody>
                                {filteredReport.length > 0 ? (
                                    filteredReport.slice(0, 100).map((record, idx) => {
                                        const date = new Date(record.attendance_date);
                                        const dayName = date.toLocaleDateString('en-US', { weekday: 'short' });
                                        return (
                                            <tr key={idx}>
                                                <td className="font-medium">{record.attendance_date}</td>
                                                <td>{dayName}</td>
                                                <td>{record.subject_name}</td>
                                                <td>
                                                    <Badge color={record.status === 'P' ? 'green' : record.status === 'A' ? 'red' : 'yellow'}>
                                                        {getStatusLabel(record.status)}
                                                    </Badge>
                                                </td>
                                                <td className="text-gray-600 text-sm">{record.remarks || '—'}</td>
                                            </tr>
                                        );
                                    })
                                ) : (
                                    <tr><td colSpan={5} className="text-center text-gray-500 py-4">No attendance records found</td></tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                    {filteredReport.length > 100 && (
                        <p className="text-xs text-gray-500 mt-3">Showing first 100 records of {filteredReport.length}</p>
                    )}
                </div>
            </div>
        </div>
    );
}
