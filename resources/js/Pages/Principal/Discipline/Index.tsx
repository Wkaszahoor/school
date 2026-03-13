import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { PlusIcon, ExclamationTriangleIcon, FunnelIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import SearchInput from '@/Components/SearchInput';
import type { PageProps, PaginatedData, DisciplineRecord, SchoolClass, Student } from '@/types';

interface Props extends PageProps {
    records: PaginatedData<DisciplineRecord>;
    classes: SchoolClass[];
}

const TYPE_COLOR: Record<string, 'green' | 'yellow' | 'red' | 'gray'> = {
    achievement: 'green',
    warning:     'yellow',
    suspension:  'red',
    note:        'gray',
};

export default function DisciplineIndex({ records, classes }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        student_id:    '',
        type:          'warning',
        description:   '',
        action_taken:  '',
        incident_date: new Date().toISOString().split('T')[0],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.discipline.store'), {
            onSuccess: () => { setOpen(false); reset(); },
        });
    };

    return (
        <AppLayout title="Discipline">
            <Head title="Discipline" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Discipline Records</h1>
                    <p className="page-subtitle">{records.total} records total</p>
                </div>
                <button onClick={() => setOpen(true)} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> Add Record
                </button>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="form-label">Search Student</label>
                            <SearchInput />
                        </div>
                        <div>
                            <label className="form-label">Type</label>
                            <select className="form-select" onChange={e => {}}>
                                <option value="">All Types</option>
                                <option value="warning">Warning</option>
                                <option value="achievement">Achievement</option>
                                <option value="suspension">Suspension</option>
                                <option value="note">Note</option>
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Class</label>
                            <select className="form-select" onChange={e => {}}>
                                <option value="">All Classes</option>
                                {classes.map(c => <option key={c.id} value={c.id}>{c.class}{c.section ? ` — ${c.section}` : ''}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Recorded By</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {records.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state py-12">
                                            <ExclamationTriangleIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No discipline records</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : records.data.map(r => (
                                <tr key={r.id}>
                                    <td>
                                        <div className="flex items-center gap-2">
                                            <div className="avatar-sm bg-gray-400">{r.student?.name?.charAt(0)}</div>
                                            <span className="font-medium text-gray-900">{r.student?.name}</span>
                                        </div>
                                    </td>
                                    <td>{r.student?.class?.name ?? '—'}</td>
                                    <td>
                                        <Badge color={TYPE_COLOR[r.type] ?? 'gray'}>{r.type}</Badge>
                                    </td>
                                    <td>
                                        <span className="block max-w-xs truncate text-gray-600">{r.description}</span>
                                    </td>
                                    <td className="text-gray-500">{r.recordedBy?.name ?? '—'}</td>
                                    <td>{new Date(r.incident_date).toLocaleDateString('en-GB')}</td>
                                    <td>
                                        <Badge color={r.resolved ? 'green' : 'yellow'}>
                                            {r.resolved ? 'Resolved' : 'Open'}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={records} />
                </div>
            </div>

            <Modal isOpen={open} onClose={() => setOpen(false)} title="Add Discipline Record" size="lg">
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Student ID <span className="text-red-500">*</span></label>
                            <input type="number" className="form-input" value={data.student_id}
                                   onChange={e => setData('student_id', e.target.value)} required
                                   placeholder="Enter student ID" />
                            {errors.student_id && <p className="form-error">{errors.student_id}</p>}
                        </div>
                        <div className="form-group">
                            <label className="form-label">Type <span className="text-red-500">*</span></label>
                            <select className="form-select" value={data.type}
                                    onChange={e => setData('type', e.target.value)}>
                                <option value="warning">Warning</option>
                                <option value="achievement">Achievement</option>
                                <option value="suspension">Suspension</option>
                                <option value="note">Note</option>
                            </select>
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Description <span className="text-red-500">*</span></label>
                        <textarea className="form-textarea" rows={3} value={data.description}
                                  onChange={e => setData('description', e.target.value)} required />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Action Taken</label>
                            <input className="form-input" value={data.action_taken}
                                   onChange={e => setData('action_taken', e.target.value)} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Incident Date <span className="text-red-500">*</span></label>
                            <input type="date" className="form-input" value={data.incident_date}
                                   onChange={e => setData('incident_date', e.target.value)} required />
                        </div>
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setOpen(false)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary flex-1">
                            {processing ? 'Saving…' : 'Add Record'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
