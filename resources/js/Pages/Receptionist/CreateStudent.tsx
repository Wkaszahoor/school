import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, SchoolClass } from '@/types';

interface Props extends PageProps {
    classes: SchoolClass[];
    nextNo: string;
}

export default function ReceptionistCreateStudent({ classes, nextNo }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        admission_no: nextNo,
        full_name: '',
        student_cnic: '',
        dob: '',
        gender: '',
        class_id: '',
        group_stream: 'general',
        semester: '',
        phone: '',
        email: '',
        father_name: '',
        father_cnic: '',
        mother_name: '',
        mother_cnic: '',
        guardian_name: '',
        guardian_cnic: '',
        guardian_phone: '',
        guardian_address: '',
        blood_group: '',
        favorite_color: '',
        favorite_food: '',
        favorite_subject: '',
        ambition: '',
        join_date_kort: '',
        is_orphan: false,
        trust_notes: '',
        previous_school: '',
        photo: null,
        is_active: true,
        reason_left_kort: '',
        leaving_date: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('receptionist.students.store'));
    };

    return (
        <AppLayout title="Register Student">
            <Head title="Register Student" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('receptionist.students')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Register New Student</h1>
                        <p className="page-subtitle">Add a student to the system</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        {/* Student Identification */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Student Identification</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Admission No. <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.admission_no}
                                               onChange={e => setData('admission_no', e.target.value)} required />
                                        {errors.admission_no && <p className="form-error">{errors.admission_no}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Full Name <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.full_name}
                                               onChange={e => setData('full_name', e.target.value)}
                                               placeholder="Student full name" required />
                                        {errors.full_name && <p className="form-error">{errors.full_name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Student CNIC</label>
                                        <input className="form-input" value={data.student_cnic}
                                               onChange={e => setData('student_cnic', e.target.value)}
                                               placeholder="XXXXX-XXXXXXX-X" />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Date of Birth <span className="text-red-500">*</span></label>
                                        <input type="date" className="form-input" value={data.dob}
                                               onChange={e => setData('dob', e.target.value)} required />
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
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Contact Number</label>
                                        <input className="form-input" value={data.phone}
                                               onChange={e => setData('phone', e.target.value)}
                                               placeholder="+44 7700 000000" />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Email</label>
                                        <input type="email" className="form-input" value={data.email}
                                               onChange={e => setData('email', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Academic Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Academic Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
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
                                        <label className="form-label">Course</label>
                                        <select className="form-select" value={data.group_stream}
                                                onChange={e => setData('group_stream', e.target.value)}>
                                            <option value="general">General</option>
                                            <option value="pre_medical">Pre-Medical</option>
                                            <option value="pre_engineering">Pre-Engineering</option>
                                            <option value="computer_science">Computer Science</option>
                                            <option value="arts">Arts</option>
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Semester</label>
                                        <input className="form-input" value={data.semester}
                                               onChange={e => setData('semester', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Joining Date</label>
                                        <input type="date" className="form-input" value={data.join_date_kort}
                                               onChange={e => setData('join_date_kort', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Parents Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Parents / Guardian Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Father's Name</label>
                                        <input className="form-input" value={data.father_name}
                                               onChange={e => setData('father_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Father's CNIC</label>
                                        <input className="form-input" value={data.father_cnic}
                                               onChange={e => setData('father_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Mother's Name</label>
                                        <input className="form-input" value={data.mother_name}
                                               onChange={e => setData('mother_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Mother's CNIC</label>
                                        <input className="form-input" value={data.mother_cnic}
                                               onChange={e => setData('mother_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Guardian Name</label>
                                        <input className="form-input" value={data.guardian_name}
                                               onChange={e => setData('guardian_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Guardian CNIC</label>
                                        <input className="form-input" value={data.guardian_cnic}
                                               onChange={e => setData('guardian_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Guardian Phone</label>
                                        <input className="form-input" value={data.guardian_phone}
                                               onChange={e => setData('guardian_phone', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Guardian Address</label>
                                        <textarea className="form-textarea" rows={2} value={data.guardian_address}
                                                  onChange={e => setData('guardian_address', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Medical & Personal Preferences */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Medical & Personal Preferences</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
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
                                        <label className="form-label">Favorite Color</label>
                                        <input className="form-input" value={data.favorite_color}
                                               onChange={e => setData('favorite_color', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Favorite Food</label>
                                        <input className="form-input" value={data.favorite_food}
                                               onChange={e => setData('favorite_food', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Favorite Subject</label>
                                        <input className="form-input" value={data.favorite_subject}
                                               onChange={e => setData('favorite_subject', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Ambition / Career Goals</label>
                                        <textarea className="form-textarea" rows={2} value={data.ambition}
                                                  onChange={e => setData('ambition', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Additional Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Additional Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Previous School</label>
                                        <input className="form-input" value={data.previous_school}
                                               onChange={e => setData('previous_school', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Is Orphan?</label>
                                        <select className="form-select" value={data.is_orphan ? '1' : '0'}
                                                onChange={e => setData('is_orphan', e.target.value === '1')}>
                                            <option value="0">No</option>
                                            <option value="1">Yes</option>
                                        </select>
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Trust Notes</label>
                                        <textarea className="form-textarea" rows={2} value={data.trust_notes}
                                                  onChange={e => setData('trust_notes', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Right Sidebar */}
                    <div className="space-y-5">
                        <div className="card">
                            <div className="card-header"><p className="card-title">Register</p></div>
                            <div className="card-body space-y-3">
                                <div className="bg-indigo-50 rounded-xl p-3 text-sm text-indigo-700">
                                    <p className="font-semibold">Admission No.</p>
                                    <p className="font-mono text-lg mt-1">{data.admission_no}</p>
                                </div>
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                    ) : 'Register Student'}
                                </button>
                                <Link href={route('receptionist.students')} className="btn-secondary w-full text-center">
                                    Cancel
                                </Link>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-header"><p className="card-title">Status</p></div>
                            <div className="card-body space-y-3">
                                <div className="form-group">
                                    <label className="form-label">Active</label>
                                    <select className="form-select" value={data.is_active ? '1' : '0'}
                                            onChange={e => setData('is_active', e.target.value === '1')}>
                                        <option value="1">Active</option>
                                        <option value="0">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-header"><p className="card-title">Leaving Information</p></div>
                            <div className="card-body space-y-3">
                                <div className="form-group">
                                    <label className="form-label">Reason Left KORT</label>
                                    <input className="form-input" value={data.reason_left_kort}
                                           onChange={e => setData('reason_left_kort', e.target.value)} />
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Leaving Date</label>
                                    <input type="date" className="form-input" value={data.leaving_date}
                                           onChange={e => setData('leaving_date', e.target.value)} />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
