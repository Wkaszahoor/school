import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { PlusIcon, HeartIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import SearchInput from '@/Components/SearchInput';
import type { PageProps, PaginatedData, SickRecord, Student } from '@/types';

interface Props extends PageProps {
    records: PaginatedData<SickRecord>;
    students: Student[];
}

export default function DoctorRecords({ records, students }: Props) {
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        student_id:           '',
        symptoms:             '',
        diagnosis:            '',
        treatment:            '',
        referred_to_hospital: false,
        visit_date:           new Date().toISOString().split('T')[0],
        notes:                '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('doctor.records.store'), {
            onSuccess: () => { setOpen(false); reset(); },
        });
    };

    return (
        <AppLayout title="Medical Records">
            <Head title="Medical Records" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Medical Records</h1>
                    <p className="page-subtitle">{records.total} records total</p>
                </div>
                <button onClick={() => setOpen(true)} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> New Visit
                </button>
            </div>

            <div className="card mb-5">
                <div className="card-body !py-4 flex flex-wrap gap-3 items-center">
                    <SearchInput placeholder="Search by student name…" className="w-64" />
                    <input type="date" className="form-input w-44"
                           placeholder="From date"
                           onChange={e => {}} />
                    <input type="date" className="form-input w-44"
                           placeholder="To date"
                           onChange={e => {}} />
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Symptoms</th>
                                <th>Diagnosis</th>
                                <th>Treatment</th>
                                <th>Referred</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {records.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state py-12">
                                            <HeartIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No medical records found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : records.data.map(r => (
                                <tr key={r.id}>
                                    <td className="font-medium text-gray-900">{r.student?.name}</td>
                                    <td>{r.student?.class?.name ?? '—'}</td>
                                    <td><span className="block max-w-xs truncate">{r.symptoms}</span></td>
                                    <td>{r.diagnosis ?? '—'}</td>
                                    <td>{r.treatment ?? '—'}</td>
                                    <td>
                                        <Badge color={r.referred_to_hospital ? 'red' : 'green'}>
                                            {r.referred_to_hospital ? 'Referred' : 'In-school'}
                                        </Badge>
                                    </td>
                                    <td>{new Date(r.visit_date).toLocaleDateString('en-GB')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={records} />
                </div>
            </div>

            {/* New Record Modal */}
            <Modal isOpen={open} onClose={() => setOpen(false)} title="New Medical Visit" size="lg">
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Student <span className="text-red-500">*</span></label>
                            <select className="form-select" value={data.student_id}
                                    onChange={e => setData('student_id', e.target.value)} required>
                                <option value="">Select student…</option>
                                {students.map(s => <option key={s.id} value={s.id}>{s.full_name} ({s.admission_no})</option>)}
                            </select>
                            {errors.student_id && <p className="form-error">{errors.student_id}</p>}
                        </div>
                        <div className="form-group">
                            <label className="form-label">Visit Date <span className="text-red-500">*</span></label>
                            <input type="date" className="form-input" value={data.visit_date}
                                   onChange={e => setData('visit_date', e.target.value)} required />
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Symptoms <span className="text-red-500">*</span></label>
                        <textarea className="form-textarea" rows={2} value={data.symptoms}
                                  onChange={e => setData('symptoms', e.target.value)} required />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Diagnosis</label>
                            <input className="form-input" value={data.diagnosis}
                                   onChange={e => setData('diagnosis', e.target.value)} />
                        </div>
                        <div className="form-group">
                            <label className="form-label">Treatment</label>
                            <input className="form-input" value={data.treatment}
                                   onChange={e => setData('treatment', e.target.value)} />
                        </div>
                    </div>
                    <div className="form-group">
                        <label className="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" checked={data.referred_to_hospital}
                                   onChange={e => setData('referred_to_hospital', e.target.checked)}
                                   className="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500" />
                            <span className="text-sm font-medium text-gray-700">Referred to hospital</span>
                        </label>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Notes</label>
                        <textarea className="form-textarea" rows={2} value={data.notes}
                                  onChange={e => setData('notes', e.target.value)} />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setOpen(false)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={processing} className="btn-primary flex-1">
                            {processing ? 'Saving…' : 'Save Record'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
