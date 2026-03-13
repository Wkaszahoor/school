import React from 'react';
import { Head, router } from '@inertiajs/react';
import { FunnelIcon, DocumentArrowDownIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import type { PageProps, Attendance, SchoolClass, PaginatedData } from '@/types';

interface Props extends PageProps {
    attendance: PaginatedData<Attendance>;
    classes: SchoolClass[];
    filters: { class_id?: string; date_from?: string; date_to?: string };
}

export default function AttendanceReport({ attendance, classes, filters }: Props) {
    const handleFilter = (key: string, value: string) => {
        router.get(route('teacher.attendance.report'), { ...filters, [key]: value }, {
            preserveState: true, replace: true,
        });
    };

    const presentCount = attendance.data.filter(a => a.status === 'P').length;
    const absentCount  = attendance.data.filter(a => a.status === 'A').length;
    const leaveCount   = attendance.data.filter(a => a.status === 'L').length;

    return (
        <AppLayout title="Attendance Report">
            <Head title="Attendance Report" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Attendance Report</h1>
                    <p className="page-subtitle">Historical attendance for your classes</p>
                </div>
                <a href={route('teacher.attendance.report.pdf', { class_id: filters.class_id, date_from: filters.date_from, date_to: filters.date_to })} className="btn-secondary">
                    <DocumentArrowDownIcon className="w-4 h-4" /> Download PDF
                </a>
            </div>

            {/* Summary */}
            <div className="grid grid-cols-3 gap-4 mb-5">
                <div className="stat-card">
                    <p className="stat-card-label">Present</p>
                    <p className="stat-card-value text-emerald-600">{presentCount}</p>
                </div>
                <div className="stat-card">
                    <p className="stat-card-label">Absent</p>
                    <p className="stat-card-value text-red-600">{absentCount}</p>
                </div>
                <div className="stat-card">
                    <p className="stat-card-label">Leave</p>
                    <p className="stat-card-value text-amber-600">{leaveCount}</p>
                </div>
            </div>

            <div className="card">
                {/* Filters */}
                <div className="card-header gap-3 flex-wrap">
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="w-4 h-4 text-gray-400" />
                        <select className="form-select !py-2 !text-xs w-40" value={filters.class_id ?? ''}
                                onChange={e => handleFilter('class_id', e.target.value)}>
                            <option value="">All Classes</option>
                            {classes.map(c => (
                                <option key={c.id} value={c.id}>
                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2 ml-auto">
                        <label className="text-xs text-gray-500">From</label>
                        <input type="date" className="form-input !py-1.5 !text-xs w-36"
                               value={filters.date_from ?? ''}
                               onChange={e => handleFilter('date_from', e.target.value)} />
                        <label className="text-xs text-gray-500">To</label>
                        <input type="date" className="form-input !py-1.5 !text-xs w-36"
                               value={filters.date_to ?? ''}
                               onChange={e => handleFilter('date_to', e.target.value)} />
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {attendance.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="text-center py-12 text-gray-400">
                                        No attendance records found
                                    </td>
                                </tr>
                            ) : attendance.data.map(a => (
                                <tr key={a.id}>
                                    <td className="font-medium">{a.attendance_date}</td>
                                    <td className="font-semibold text-gray-900">{a.student?.full_name}</td>
                                    <td className="font-mono text-sm text-gray-500">{a.student?.admission_no}</td>
                                    <td>{a.class?.class}{a.class?.section ? ` — ${a.class.section}` : ''}</td>
                                    <td>
                                        <Badge color={
                                            a.status === 'P' ? 'green' :
                                            a.status === 'A' ? 'red' : 'yellow'
                                        }>
                                            {a.status === 'P' ? 'Present' : a.status === 'A' ? 'Absent' : 'Leave'}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={attendance} />
                </div>
            </div>
        </AppLayout>
    );
}
