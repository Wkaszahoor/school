import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { CheckIcon, XMarkIcon, CalendarDaysIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import type { PageProps, PaginatedData, LeaveRequest } from '@/types';

interface Props extends PageProps {
    leaves: PaginatedData<LeaveRequest>;
}

const STATUS_COLOR: Record<string, 'green' | 'red' | 'yellow'> = {
    'Approved': 'green',
    'Rejected': 'red',
    'Pending':  'yellow',
};

const LEAVE_TYPES: Record<string, string> = {
    casual: 'Casual Leave',
    annual: 'Annual Leave',
    emergency: 'Emergency Leave',
    other: 'Other',
};

export default function LeaveIndex({ leaves }: Props) {
    const [rejectLeave, setRejectLeave] = useState<LeaveRequest | null>(null);
    const [approveRemarks, setApproveRemarks] = useState('');
    const { data, setData, post, processing, reset } = useForm({ remarks: '' });

    const handleApprove = (leave: LeaveRequest) => {
        router.post(route('principal.leave.approve', leave.id), { remarks: approveRemarks });
    };

    const handleRejectSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!rejectLeave) return;
        post(route('principal.leave.reject', rejectLeave.id), {
            onSuccess: () => { setRejectLeave(null); reset(); },
        });
    };

    return (
        <AppLayout title="Leave Requests">
            <Head title="Leave Requests" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Leave Requests</h1>
                    <p className="page-subtitle">{leaves.total} requests total</p>
                </div>
                <div className="flex gap-2">
                    {['all', 'pending', 'approved', 'rejected'].map(s => (
                        <button
                            key={s}
                            onClick={() => router.get(route('principal.leave.index'), { status: s === 'all' ? '' : s }, { preserveState: true, replace: true })}
                            className="btn-secondary btn-sm capitalize"
                        >
                            {s}
                        </button>
                    ))}
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Leave Type</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Duration</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {leaves.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8}>
                                        <div className="empty-state py-12">
                                            <CalendarDaysIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No leave requests</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : leaves.data.map(leave => {
                                const days = Math.abs(new Date(leave.to_date).getTime() - new Date(leave.from_date).getTime()) / 86400000 + 1;
                                return (
                                    <tr key={leave.id}>
                                        <td>
                                            <div className="flex items-center gap-2">
                                                <div className="avatar-sm bg-purple-600">
                                                    {leave.teacher?.name?.charAt(0) ?? 'T'}
                                                </div>
                                                <span className="font-medium text-gray-900">
                                                    {leave.teacher?.name ?? '—'}
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            {leave.leave_type ? LEAVE_TYPES[leave.leave_type] : (leave.other_leave_type || '—')}
                                        </td>
                                        <td>{new Date(leave.from_date).toLocaleDateString('en-GB')}</td>
                                        <td>{new Date(leave.to_date).toLocaleDateString('en-GB')}</td>
                                        <td>
                                            <span className="badge-blue badge">{days} day{days !== 1 ? 's' : ''}</span>
                                        </td>
                                        <td className="max-w-xs">
                                            <p className="truncate text-gray-600">{leave.reason}</p>
                                        </td>
                                        <td>
                                            <Badge color={STATUS_COLOR[leave.status] ?? 'gray'}>
                                                {leave.status}
                                            </Badge>
                                        </td>
                                        <td>
                                            {leave.status === 'Pending' && (
                                                <div className="flex items-center justify-end gap-1">
                                                    <button
                                                        onClick={() => handleApprove(leave)}
                                                        className="btn-success btn-sm btn-icon"
                                                        title="Approve"
                                                    >
                                                        <CheckIcon className="w-4 h-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => setRejectLeave(leave)}
                                                        className="btn-danger btn-sm btn-icon"
                                                        title="Reject"
                                                    >
                                                        <XMarkIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            )}
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

            {/* Reject Modal */}
            <Modal isOpen={!!rejectLeave} onClose={() => setRejectLeave(null)} title="Reject Leave Request">
                <form onSubmit={handleRejectSubmit} className="space-y-4">
                    <p className="text-sm text-gray-600">
                        You are rejecting the leave request for{' '}
                        <strong>{rejectLeave?.teacher?.name}</strong>.
                    </p>
                    <div className="form-group">
                        <label className="form-label">Reason for Rejection <span className="text-red-500">*</span></label>
                        <textarea
                            className="form-textarea"
                            value={data.remarks}
                            onChange={e => setData('remarks', e.target.value)}
                            placeholder="Explain why the leave is being rejected…"
                            rows={3}
                            required
                        />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setRejectLeave(null)} className="btn-secondary flex-1">
                            Cancel
                        </button>
                        <button type="submit" disabled={processing} className="btn-danger flex-1">
                            {processing ? 'Rejecting…' : 'Reject Request'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
