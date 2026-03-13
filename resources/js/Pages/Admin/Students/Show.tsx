import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, PencilSquareIcon, AcademicCapIcon, CalendarDaysIcon, UserIcon, DocumentArrowDownIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, Student } from '@/types';

interface Props extends PageProps {
    student: Student;
}

export default function ShowStudent({ student }: Props) {
    const initials = student.full_name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();

    return (
        <AppLayout title={student.full_name}>
            <Head title={student.full_name} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.students.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">{student.full_name}</h1>
                        <p className="page-subtitle">{student.admission_no} · {student.class?.name}</p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <Link href={route('admin.students.edit', student.id)} className="btn-secondary">
                        <PencilSquareIcon className="w-4 h-4" /> Edit Student
                    </Link>
                    <a href={route('admin.students.pdf', student.id)} className="btn-secondary">
                        <DocumentArrowDownIcon className="w-4 h-4" /> Download PDF
                    </a>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Profile Card */}
                <div className="card">
                    <div className="card-body flex flex-col items-center text-center gap-4">
                        <div className="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                            {student.photo ? (
                                <img src={`/storage/${student.photo}`} className="w-full h-full rounded-full object-cover" alt="" />
                            ) : initials}
                        </div>
                        <div>
                            <p className="text-lg font-bold text-gray-900">{student.full_name}</p>
                            <p className="text-sm text-gray-500">{student.admission_no}</p>
                        </div>
                        <div className="w-full space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Class</span>
                                <span className="font-medium">{student.class?.name}{student.class?.section ? ` — ${student.class.section}` : ''}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Gender</span>
                                <span className="font-medium capitalize">{student.gender}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">D.O.B</span>
                                <span className="font-medium">{student.dob}</span>
                            </div>
                            {student.blood_group && (
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Blood Group</span>
                                    <Badge color="red">{student.blood_group}</Badge>
                                </div>
                            )}
                            <div className="flex justify-between">
                                <span className="text-gray-500">Status</span>
                                <Badge color={student.is_active ? 'green' : 'gray'}>{student.is_active ? 'Active' : 'Inactive'}</Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-2 space-y-5">
                    {/* Personal Information */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Personal Information</p>
                        </div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Student CNIC</dt>
                                    <dd className="font-medium mt-0.5">{student.student_cnic || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Phone</dt>
                                    <dd className="font-medium mt-0.5">{student.phone || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Favorite Color</dt>
                                    <dd className="font-medium mt-0.5">{student.favorite_color || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Favorite Food</dt>
                                    <dd className="font-medium mt-0.5">{student.favorite_food || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Favorite Subject</dt>
                                    <dd className="font-medium mt-0.5">{student.favorite_subject || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Ambition</dt>
                                    <dd className="font-medium mt-0.5">{student.ambition || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Semester</dt>
                                    <dd className="font-medium mt-0.5">{student.semester || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Stream/Course</dt>
                                    <dd className="font-medium mt-0.5 capitalize">{student.group_stream || '—'}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Family Information */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Family Information</p>
                        </div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Father's Name</dt>
                                    <dd className="font-medium mt-0.5">{student.father_name || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Father's CNIC</dt>
                                    <dd className="font-medium mt-0.5">{student.father_cnic || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Mother's Name</dt>
                                    <dd className="font-medium mt-0.5">{student.mother_name || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Mother's CNIC</dt>
                                    <dd className="font-medium mt-0.5">{student.mother_cnic || '—'}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Guardian */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title flex items-center gap-2"><UserIcon className="w-4 h-4" /> Guardian Information</p>
                        </div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Name</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_name || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Phone</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_phone || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">CNIC</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_cnic || '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-gray-500">Email</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_email || '—'}</dd>
                                </div>
                                <div className="col-span-2">
                                    <dt className="text-gray-500">Address</dt>
                                    <dd className="font-medium mt-0.5">{student.guardian_address || '—'}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {/* Enrollment Information */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Enrollment Information</p>
                        </div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt className="text-gray-500">Joining Date</dt>
                                    <dd className="font-medium mt-0.5">{student.join_date_kort || '—'}</dd>
                                </div>
                                {student.reason_left_kort && (
                                    <>
                                        <div>
                                            <dt className="text-gray-500">Reason Left</dt>
                                            <dd className="font-medium mt-0.5">{student.reason_left_kort}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-gray-500">Leaving Date</dt>
                                            <dd className="font-medium mt-0.5">{student.leaving_date || '—'}</dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </div>
                    </div>

                    {/* Trust Notes */}
                    {(student.is_orphan || student.trust_notes) && (
                        <div className="card border-l-4 border-l-amber-400 bg-amber-50">
                            <div className="card-header">
                                <p className="card-title">Trust Notes</p>
                            </div>
                            <div className="card-body">
                                <div className="space-y-3 text-sm">
                                    {student.is_orphan && (
                                        <div className="flex items-center gap-2">
                                            <Badge color="red">Orphan</Badge>
                                            <span className="text-gray-600">This student is marked as orphan</span>
                                        </div>
                                    )}
                                    {student.trust_notes && (
                                        <div>
                                            <p className="text-gray-500 mb-1">Notes:</p>
                                            <p className="text-gray-700 bg-white rounded p-2">{student.trust_notes}</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Attendance */}
                    {student.attendance && student.attendance.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title flex items-center gap-2"><CalendarDaysIcon className="w-4 h-4" /> Recent Attendance</p>
                            </div>
                            <div className="card-body">
                                <div className="flex gap-4 mb-4">
                                    {['P', 'A', 'L'].map(s => {
                                        const count = student.attendance!.filter(a => a.status === s).length;
                                        const labels: Record<string, string> = { P: 'Present', A: 'Absent', L: 'Leave' };
                                        const colors: Record<string, string> = { P: 'text-emerald-600 bg-emerald-50', A: 'text-red-600 bg-red-50', L: 'text-amber-600 bg-amber-50' };
                                        return (
                                            <div key={s} className={`flex-1 rounded-xl p-3 text-center ${colors[s]}`}>
                                                <p className="text-xl font-bold">{count}</p>
                                                <p className="text-xs font-medium">{labels[s]}</p>
                                            </div>
                                        );
                                    })}
                                </div>
                                <div className="flex flex-wrap gap-1">
                                    {student.attendance.slice(0, 30).map(a => (
                                        <span key={a.id} title={a.date}
                                              className={`w-7 h-7 rounded flex items-center justify-center text-xs font-bold
                                                ${a.status === 'P' ? 'bg-emerald-100 text-emerald-700' :
                                                  a.status === 'A' ? 'bg-red-100 text-red-700' :
                                                  'bg-amber-100 text-amber-700'}`}>
                                            {a.status}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Results */}
                    {student.results && student.results.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title flex items-center gap-2"><AcademicCapIcon className="w-4 h-4" /> Results</p>
                            </div>
                            <div className="table-wrapper">
                                <table className="table">
                                    <thead>
                                        <tr>
                                            <th>Subject</th>
                                            <th>Exam</th>
                                            <th>Marks</th>
                                            <th>%</th>
                                            <th>Grade</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {student.results.map(r => (
                                            <tr key={r.id}>
                                                <td className="font-medium">{r.subject?.name}</td>
                                                <td>{r.exam_type}</td>
                                                <td>{r.obtained_marks}/{r.total_marks}</td>
                                                <td>{r.percentage}%</td>
                                                <td><Badge color={r.grade === 'A' ? 'green' : r.grade === 'F' ? 'red' : 'blue'}>{r.grade}</Badge></td>
                                                <td><Badge color={r.approval_status === 'approved' ? 'green' : r.approval_status === 'rejected' ? 'red' : 'yellow'}>{r.approval_status === 'approved' ? 'Approved' : r.approval_status === 'rejected' ? 'Rejected' : 'Pending'}</Badge></td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
