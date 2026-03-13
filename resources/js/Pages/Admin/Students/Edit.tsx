import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Student, SchoolClass } from '@/types';

interface Props extends PageProps {
    student: Student;
    classes: SchoolClass[];
}

export default function EditStudent({ student, classes }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: student.full_name,
        date_of_birth: student.date_of_birth,
        gender: student.gender,
        class_id: String(student.class_id ?? ''),
        guardian_name: student.guardian_name ?? '',
        guardian_phone: student.guardian_phone ?? '',
        guardian_email: student.guardian_email ?? '',
        address: student.address ?? '',
        blood_group: student.blood_group ?? '',
        is_orphan: student.is_orphan ?? false,
        trust_notes: student.trust_notes ?? '',
        is_active: student.is_active ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.students.update', student.id));
    };

    return (
        <AppLayout title={`Edit — ${student.full_name}`}>
            <Head title={`Edit — ${student.full_name}`} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.students.show', student.id)} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Edit Student</h1>
                        <p className="page-subtitle">{student.full_name} · {student.admission_no}</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        <div className="card">
                            <div className="card-header"><p className="card-title">Personal Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Full Name <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.name}
                                               onChange={e => setData('name', e.target.value)} required />
                                        {errors.name && <p className="form-error">{errors.name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Class <span className="text-red-500">*</span></label>
                                        <select className="form-select" value={data.class_id}
                                                onChange={e => setData('class_id', e.target.value)} required>
                                            <option value="">Select class…</option>
                                            {classes.map(c => (
                                                <option key={c.id} value={c.id}>
                                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.class_id && <p className="form-error">{errors.class_id}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Date of Birth <span className="text-red-500">*</span></label>
                                        <input type="date" className="form-input" value={data.date_of_birth}
                                               onChange={e => setData('date_of_birth', e.target.value)} required />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Gender <span className="text-red-500">*</span></label>
                                        <select className="form-select" value={data.gender}
                                                onChange={e => setData('gender', e.target.value)} required>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Blood Group</label>
                                        <select className="form-select" value={data.blood_group}
                                                onChange={e => setData('blood_group', e.target.value)}>
                                            <option value="">Unknown</option>
                                            {['A+','A-','B+','B-','AB+','AB-','O+','O-'].map(bg => (
                                                <option key={bg} value={bg}>{bg}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-header"><p className="card-title">Guardian Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Guardian Name</label>
                                        <input className="form-input" value={data.guardian_name}
                                               onChange={e => setData('guardian_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Phone</label>
                                        <input className="form-input" value={data.guardian_phone}
                                               onChange={e => setData('guardian_phone', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Email</label>
                                        <input type="email" className="form-input" value={data.guardian_email}
                                               onChange={e => setData('guardian_email', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Address</label>
                                        <textarea className="form-textarea" rows={2} value={data.address}
                                                  onChange={e => setData('address', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-header"><p className="card-title">Notes &amp; Status</p></div>
                            <div className="card-body space-y-4">
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" checked={data.is_orphan}
                                               onChange={e => setData('is_orphan', e.target.checked)}
                                               className="w-4 h-4 text-indigo-600 rounded" />
                                        <span className="text-sm font-semibold text-gray-700">Orphan</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" checked={data.is_active}
                                               onChange={e => setData('is_active', e.target.checked)}
                                               className="w-4 h-4 text-indigo-600 rounded" />
                                        <span className="text-sm font-semibold text-gray-700">Active</span>
                                    </label>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Trust Notes</label>
                                    <textarea className="form-textarea" rows={3} value={data.trust_notes}
                                              onChange={e => setData('trust_notes', e.target.value)} />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="card">
                            <div className="card-header"><p className="card-title">Save Changes</p></div>
                            <div className="card-body space-y-3">
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                    ) : 'Save Changes'}
                                </button>
                                <Link href={route('admin.students.show', student.id)} className="btn-secondary w-full text-center">
                                    Cancel
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
