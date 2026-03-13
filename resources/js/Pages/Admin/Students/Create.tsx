import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, SchoolClass } from '@/types';

interface Props extends PageProps {
    classes: SchoolClass[];
}

export default function CreateStudent({ classes }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        admission_no: '',
        date_of_birth: '',
        gender: '',
        class_id: '',
        guardian_name: '',
        guardian_phone: '',
        guardian_email: '',
        address: '',
        blood_group: '',
        is_orphan: false,
        trust_notes: '',
        join_date_kort: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.students.store'));
    };

    return (
        <AppLayout title="Add Student">
            <Head title="Add Student" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.students.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Add New Student</h1>
                        <p className="page-subtitle">Register a new student in the system</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        {/* Basic Info */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Personal Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Full Name <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.name}
                                               onChange={e => setData('name', e.target.value)}
                                               placeholder="Student full name" required />
                                        {errors.name && <p className="form-error">{errors.name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Admission No. <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.admission_no}
                                               onChange={e => setData('admission_no', e.target.value)}
                                               placeholder="e.g. SCH0001" required />
                                        {errors.admission_no && <p className="form-error">{errors.admission_no}</p>}
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
                                        {errors.date_of_birth && <p className="form-error">{errors.date_of_birth}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Gender <span className="text-red-500">*</span></label>
                                        <select className="form-select" value={data.gender}
                                                onChange={e => setData('gender', e.target.value)} required>
                                            <option value="">Select…</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                        {errors.gender && <p className="form-error">{errors.gender}</p>}
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
                                    <div className="form-group">
                                        <label className="form-label">Join Date (KORT)</label>
                                        <input type="date" className="form-input" value={data.join_date_kort}
                                               onChange={e => setData('join_date_kort', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Guardian Info */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Guardian Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Guardian Name</label>
                                        <input className="form-input" value={data.guardian_name}
                                               onChange={e => setData('guardian_name', e.target.value)}
                                               placeholder="Parent / guardian full name" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Guardian Phone</label>
                                        <input className="form-input" value={data.guardian_phone}
                                               onChange={e => setData('guardian_phone', e.target.value)}
                                               placeholder="+44 7700 000000" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Guardian Email</label>
                                        <input type="email" className="form-input" value={data.guardian_email}
                                               onChange={e => setData('guardian_email', e.target.value)}
                                               placeholder="guardian@email.com" />
                                        {errors.guardian_email && <p className="form-error">{errors.guardian_email}</p>}
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Home Address</label>
                                        <textarea className="form-textarea" rows={2} value={data.address}
                                                  onChange={e => setData('address', e.target.value)}
                                                  placeholder="Full home address" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* KORT Notes */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Trust / Special Notes</p></div>
                            <div className="card-body space-y-4">
                                <div className="form-group">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" checked={data.is_orphan}
                                               onChange={e => setData('is_orphan', e.target.checked)}
                                               className="w-4 h-4 text-indigo-600 rounded" />
                                        <span className="text-sm font-semibold text-gray-700">This student is an orphan</span>
                                    </label>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Trust Notes</label>
                                    <textarea className="form-textarea" rows={3} value={data.trust_notes}
                                              onChange={e => setData('trust_notes', e.target.value)}
                                              placeholder="Any notes related to trust / scholarship support…" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="card">
                            <div className="card-header"><p className="card-title">Save Student</p></div>
                            <div className="card-body space-y-3">
                                <p className="text-sm text-gray-500">Fill in the required fields and click save to register the student.</p>
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                    ) : 'Save Student'}
                                </button>
                                <Link href={route('admin.students.index')} className="btn-secondary w-full text-center">
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
