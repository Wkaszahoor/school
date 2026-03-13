import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Student, SchoolClass, SubjectGroup } from '@/types';

interface Props extends PageProps {
    student: Student;
    classes: SchoolClass[];
    groups: SubjectGroup[];
}

export default function PrincipalEditStudent({ student, classes, groups }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        full_name: student.full_name,
        admission_no: student.admission_no,
        student_cnic: student.student_cnic ?? '',
        father_name: student.father_name ?? '',
        father_cnic: student.father_cnic ?? '',
        mother_name: student.mother_name ?? '',
        mother_cnic: student.mother_cnic ?? '',
        guardian_name: student.guardian_name ?? '',
        guardian_relation: student.guardian_relation ?? '',
        guardian_phone: student.guardian_phone ?? '',
        guardian_cnic: student.guardian_cnic ?? '',
        guardian_address: student.guardian_address ?? '',
        dob: student.dob ?? '',
        gender: student.gender,
        class_id: String(student.class_id ?? ''),
        subject_group_id: String(student.subject_group_id ?? ''),
        group_stream: student.group_stream ?? '',
        semester: student.semester ?? '',
        phone: student.phone ?? '',
        email: student.email ?? '',
        blood_group: student.blood_group ?? '',
        join_date_kort: student.join_date_kort ?? '',
        is_orphan: student.is_orphan ?? false,
        trust_notes: student.trust_notes ?? '',
        previous_school: student.previous_school ?? '',
        favorite_color: student.favorite_color ?? '',
        favorite_food: student.favorite_food ?? '',
        favorite_subject: student.favorite_subject ?? '',
        ambition: student.ambition ?? '',
        reason_left_kort: student.reason_left_kort ?? '',
        leaving_date: student.leaving_date ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('principal.students.update', student.id));
    };

    // Check if selected class is 9-12 (eligible for subject groups)
    const selectedClass = classes.find(c => String(c.id) === data.class_id);
    const classNumber = selectedClass ? parseInt(selectedClass.class) : 0;
    const isEligibleForGroup = classNumber >= 9 && classNumber <= 12;

    return (
        <AppLayout title={`Edit — ${student.full_name}`}>
            <Head title={`Edit — ${student.full_name}`} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('principal.students.show', student.id)} className="btn-ghost btn-icon">
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
                        {/* Basic Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Basic Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Full Name <span className="text-red-500">*</span></label>
                                        <input className="form-input" value={data.full_name}
                                               onChange={e => setData('full_name', e.target.value)} required />
                                        {errors.full_name && <p className="form-error">{errors.full_name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Admission No.</label>
                                        <input className="form-input" value={data.admission_no} disabled />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">CNIC</label>
                                        <input className="form-input" value={data.student_cnic}
                                               onChange={e => setData('student_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Date of Birth</label>
                                        <input type="date" className="form-input" value={data.dob}
                                               onChange={e => setData('dob', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Gender</label>
                                        <select className="form-select" value={data.gender}
                                                onChange={e => setData('gender', e.target.value)}>
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

                        {/* Parent Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Parent Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Father Name</label>
                                        <input className="form-input" value={data.father_name}
                                               onChange={e => setData('father_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Father CNIC</label>
                                        <input className="form-input" value={data.father_cnic}
                                               onChange={e => setData('father_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Mother Name</label>
                                        <input className="form-input" value={data.mother_name}
                                               onChange={e => setData('mother_name', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Mother CNIC</label>
                                        <input className="form-input" value={data.mother_cnic}
                                               onChange={e => setData('mother_cnic', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Guardian Information */}
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
                                        <label className="form-label">Relation</label>
                                        <input className="form-input" placeholder="e.g. Father, Mother, Uncle" value={data.guardian_relation}
                                               onChange={e => setData('guardian_relation', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Phone</label>
                                        <input className="form-input" value={data.guardian_phone}
                                               onChange={e => setData('guardian_phone', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">CNIC</label>
                                        <input className="form-input" value={data.guardian_cnic}
                                               onChange={e => setData('guardian_cnic', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Address</label>
                                        <textarea className="form-textarea" rows={2} value={data.guardian_address}
                                                  onChange={e => setData('guardian_address', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Contact & Academic */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Contact & Academic</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Phone</label>
                                        <input className="form-input" value={data.phone}
                                               onChange={e => setData('phone', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Email</label>
                                        <input type="email" className="form-input" value={data.email}
                                               onChange={e => setData('email', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Class</label>
                                        <select className="form-select" value={data.class_id}
                                                onChange={e => setData('class_id', e.target.value)}>
                                            {classes.map(c => (
                                                <option key={c.id} value={c.id}>
                                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    {isEligibleForGroup && (
                                        <div className="form-group">
                                            <label className="form-label">Stream/Group</label>
                                            <select className="form-select" value={data.subject_group_id}
                                                    onChange={e => setData('subject_group_id', e.target.value)}>
                                                <option value="">None</option>
                                                {groups.map(g => (
                                                    <option key={g.id} value={g.id}>{g.group_name}</option>
                                                ))}
                                            </select>
                                        </div>
                                    )}
                                    <div className="form-group">
                                        <label className="form-label">Semester</label>
                                        <input className="form-input" value={data.semester}
                                               onChange={e => setData('semester', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Previous School</label>
                                        <input className="form-input" value={data.previous_school}
                                               onChange={e => setData('previous_school', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Personal Preferences */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Personal Preferences</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
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
                                    <div className="form-group">
                                        <label className="form-label">Ambition</label>
                                        <input className="form-input" value={data.ambition}
                                               onChange={e => setData('ambition', e.target.value)} />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Attendance & Status */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Attendance & Status</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Joining Date</label>
                                        <input type="date" className="form-input" value={data.join_date_kort}
                                               onChange={e => setData('join_date_kort', e.target.value)} />
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Leaving Date</label>
                                        <input type="date" className="form-input" value={data.leaving_date}
                                               onChange={e => setData('leaving_date', e.target.value)} />
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Reason Left</label>
                                        <textarea className="form-textarea" rows={2} value={data.reason_left_kort}
                                                  onChange={e => setData('reason_left_kort', e.target.value)} />
                                    </div>
                                    <label className="flex items-center gap-2 cursor-pointer col-span-2">
                                        <input type="checkbox" checked={data.is_orphan}
                                               onChange={e => setData('is_orphan', e.target.checked)}
                                               className="w-4 h-4 text-indigo-600 rounded" />
                                        <span className="text-sm font-semibold text-gray-700">Orphan</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {/* Additional Notes */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Additional Notes</p></div>
                            <div className="card-body space-y-4">
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
                                    {processing ? 'Saving…' : 'Save Changes'}
                                </button>
                                <Link href={route('principal.students.show', student.id)} className="btn-secondary w-full text-center">
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
