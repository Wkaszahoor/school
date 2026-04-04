import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { CalendarDaysIcon, XMarkIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, LeaveRequest } from '@/types';

interface Props extends PageProps {
    leaves: PaginatedData<LeaveRequest>;
    counts: {
        pending: number;
        approved: number;
        rejected: number;
    };
}

const LEAVE_TYPES: Record<string, string> = {
    casual: 'Casual Leave',
    annual: 'Annual Leave',
    emergency: 'Emergency Leave',
    other: 'Other',
};

const STATUS_COLOR: Record<string, 'green' | 'red' | 'yellow'> = {
    'Pending': 'yellow',
    'Approved': 'green',
    'Rejected': 'red',
};

export default function LeaveIndex({ leaves, counts }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        leave_type: 'casual',
        other_leave_type: '',
        from_date: '',
        to_date: '',
        reason: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('teacher.leave.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    const calculateDays = (fromDate: string, toDate: string) => {
        const from = new Date(fromDate);
        const to = new Date(toDate);
        return Math.abs(to.getTime() - from.getTime()) / 86400000 + 1;
    };

    return (
        <AppLayout title="My Leave Requests">
            <Head title="My Leave Requests" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">My Leave Requests</h1>
                    <p className="page-subtitle">Manage your leave requests</p>
                </div>
                <button
                    onClick={() => setShowForm(!showForm)}
                    className="btn-primary"
                >
                    {showForm ? 'Cancel' : 'Apply for Leave'}
                </button>
            </div>

            {/* Apply Leave Form */}
            {showForm && (
                <div className="card mb-6">
                    <div className="card-header">
                        <h2 className="text-lg font-semibold text-gray-900">New Leave Request</h2>
                    </div>
                    <form onSubmit={handleSubmit} className="space-y-4 p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Leave Type */}
                            <div className="form-group">
                                <label className="form-label">Leave Type <span className="text-red-500">*</span></label>
                                <select
                                    className="form-control"
                                    value={data.leave_type}
                                    onChange={e => setData('leave_type', e.target.value)}
                                    required
                                >
                                    <option value="casual">Casual Leave</option>
                                    <option value="annual">Annual Leave</option>
                                    <option value="emergency">Emergency Leave</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.leave_type && <span className="text-red-500 text-sm mt-1 block">{errors.leave_type}</span>}
                            </div>

                            {/* Custom Type */}
                            {data.leave_type === 'other' && (
                                <div className="form-group">
                                    <label className="form-label">Please Specify <span className="text-red-500">*</span></label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={data.other_leave_type}
                                        onChange={e => setData('other_leave_type', e.target.value)}
                                        placeholder="e.g., Maternity Leave"
                                        maxLength={100}
                                        required={data.leave_type === 'other'}
                                    />
                                    {errors.other_leave_type && <span className="text-red-500 text-sm mt-1 block">{errors.other_leave_type}</span>}
                                </div>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* From Date */}
                            <div className="form-group">
                                <label className="form-label">From Date <span className="text-red-500">*</span></label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={data.from_date}
                                    onChange={e => setData('from_date', e.target.value)}
                                    required
                                />
                                {errors.from_date && <span className="text-red-500 text-sm mt-1 block">{errors.from_date}</span>}
                            </div>

                            {/* To Date */}
                            <div className="form-group">
                                <label className="form-label">To Date <span className="text-red-500">*</span></label>
                                <input
                                    type="date"
                                    className="form-control"
                                    value={data.to_date}
                                    onChange={e => setData('to_date', e.target.value)}
                                    required
                                />
                                {errors.to_date && <span className="text-red-500 text-sm mt-1 block">{errors.to_date}</span>}
                            </div>
                        </div>

                        {/* Reason */}
                        <div className="form-group">
                            <label className="form-label">Reason <span className="text-red-500">*</span></label>
                            <textarea
                                className="form-textarea"
                                value={data.reason}
                                onChange={e => setData('reason', e.target.value)}
                                placeholder="Explain the reason for your leave request…"
                                rows={3}
                                maxLength={1000}
                                required
                            />
                            {errors.reason && <span className="text-red-500 text-sm mt-1 block">{errors.reason}</span>}
                            <p className="text-xs text-gray-500 mt-1">{data.reason.length} / 1000 characters</p>
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => setShowForm(false)}
                                className="btn-secondary flex-1"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-primary flex-1"
                            >
                                {processing ? 'Submitting…' : 'Submit Request'}
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {/* Statistics */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="card">
                    <div className="p-6">
                        <p className="text-sm text-gray-600 font-medium">Pending</p>
                        <p className="text-3xl font-bold text-yellow-600 mt-1">{counts.pending}</p>
                    </div>
                </div>
                <div className="card">
                    <div className="p-6">
                        <p className="text-sm text-gray-600 font-medium">Approved</p>
                        <p className="text-3xl font-bold text-green-600 mt-1">{counts.approved}</p>
                    </div>
                </div>
                <div className="card">
                    <div className="p-6">
                        <p className="text-sm text-gray-600 font-medium">Rejected</p>
                        <p className="text-3xl font-bold text-red-600 mt-1">{counts.rejected}</p>
                    </div>
                </div>
            </div>

            {/* Leave Requests Table */}
            <div className="card">
                <div className="card-header">
                    <h2 className="text-lg font-semibold text-gray-900">Leave Requests</h2>
                </div>
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state py-12">
                                            <CalendarDaysIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No leave requests yet</p>
                                            <p className="text-sm text-gray-500 mt-1">Click "Apply for Leave" to submit your first request</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : leaves.data.map(leave => {
                                const days = calculateDays(leave.from_date, leave.to_date);
                                return (
                                    <tr key={leave.id}>
                                        <td className="capitalize font-medium">
                                            {LEAVE_TYPES[leave.leave_type ?? 'casual'] || leave.other_leave_type}
                                        </td>
                                        <td>{new Date(leave.from_date).toLocaleDateString('en-GB')}</td>
                                        <td>{new Date(leave.to_date).toLocaleDateString('en-GB')}</td>
                                        <td>
                                            <span className="badge badge-blue">{days} day{days !== 1 ? 's' : ''}</span>
                                        </td>
                                        <td className="max-w-xs">
                                            <p className="truncate text-gray-600 text-sm">{leave.reason}</p>
                                        </td>
                                        <td>
                                            <Badge color={STATUS_COLOR[leave.status] ?? 'gray'}>
                                                {leave.status}
                                            </Badge>
                                        </td>
                                        <td className="text-sm text-gray-500">
                                            {new Date(leave.created_at).toLocaleDateString('en-GB')}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={leaves} />
                </div>
            </div>
        </AppLayout>
    );
}
