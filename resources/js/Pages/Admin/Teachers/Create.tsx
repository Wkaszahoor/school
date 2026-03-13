import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, SchoolClass, Subject } from '@/types';

interface Props extends PageProps {
    classes: SchoolClass[];
    subjects: Subject[];
}

export default function CreateTeacher({ classes, subjects }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        employee_id: '',
        phone: '',
        qualification: '',
        specialisation: '',
        date_joined: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.teachers.store'));
    };

    return (
        <AppLayout title="Add Teacher">
            <Head title="Add Teacher" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.teachers.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Add New Teacher</h1>
                        <p className="page-subtitle">Create teacher account and profile</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        {/* Account */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Login Account</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Full Name <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.name}
                                               onChange={e => setData('name', e.target.value)}
                                               placeholder="Teacher full name" required />
                                        {errors.name && <p className="form-error">{errors.name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Email <span className="text-red-500">*</span></label>
                                        <input type="email" className="form-input" value={data.email}
                                               onChange={e => setData('email', e.target.value)}
                                               placeholder="teacher@school.uk" required />
                                        {errors.email && <p className="form-error">{errors.email}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Password <span className="text-red-500">*</span></label>
                                        <input type="password" className="form-input" value={data.password}
                                               onChange={e => setData('password', e.target.value)}
                                               placeholder="Min. 8 characters" required />
                                        {errors.password && <p className="form-error">{errors.password}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Profile */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Professional Profile</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Employee ID <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.employee_id}
                                               onChange={e => setData('employee_id', e.target.value)}
                                               placeholder="e.g. EMP001" required />
                                        {errors.employee_id && <p className="form-error">{errors.employee_id}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Phone</label>
                                        <input className="form-input" value={data.phone}
                                               onChange={e => setData('phone', e.target.value)}
                                               placeholder="+44 7700 000000" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Qualification</label>
                                        <input className="form-input" value={data.qualification}
                                               onChange={e => setData('qualification', e.target.value)}
                                               placeholder="e.g. BSc Mathematics" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Specialisation</label>
                                        <input className="form-input" value={data.specialisation}
                                               onChange={e => setData('specialisation', e.target.value)}
                                               placeholder="e.g. Pure Mathematics" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Date Joined</label>
                                        <input type="date" className="form-input" value={data.date_joined}
                                               onChange={e => setData('date_joined', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="card">
                            <div className="card-header"><p className="card-title">Create Account</p></div>
                            <div className="card-body space-y-3">
                                <div className="bg-blue-50 rounded-xl p-3 text-sm text-blue-700">
                                    <p className="font-semibold mb-1">Note</p>
                                    <p className="text-blue-600">The teacher will be able to log in with the email and password you set.</p>
                                </div>
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Creating…</>
                                    ) : 'Create Teacher'}
                                </button>
                                <Link href={route('admin.teachers.index')} className="btn-secondary w-full text-center">
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
