import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, Student, SchoolClass } from '@/types';

interface Props extends PageProps {
    student: Student;
    universityClass: SchoolClass;
}

export default function UniversityStudentShow({ student, universityClass }: Props) {
    return (
        <AppLayout title={student.full_name}>
            <Head title={student.full_name} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.university-students.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">{student.full_name}</h1>
                        <p className="page-subtitle">{universityClass.class} • {student.admission_no}</p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-5">
                {/* Personal Information */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Personal Information</p>
                    </div>
                    <div className="card-body">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt className="text-gray-500">Full Name</dt>
                                <dd className="font-medium mt-0.5">{student.full_name}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Admission No.</dt>
                                <dd className="font-medium mt-0.5">{student.admission_no}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Date of Birth</dt>
                                <dd className="font-medium mt-0.5">{student.dob ? new Date(student.dob).toLocaleDateString() : '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Gender</dt>
                                <dd className="font-medium mt-0.5 capitalize">{student.gender}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Phone</dt>
                                <dd className="font-medium mt-0.5">{student.phone || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Student CNIC</dt>
                                <dd className="font-medium mt-0.5">{student.student_cnic || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Status</dt>
                                <dd className="font-medium mt-0.5">
                                    <Badge color={student.is_active ? 'green' : 'gray'}>
                                        {student.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Join Date</dt>
                                <dd className="font-medium mt-0.5">{student.join_date_kort ? new Date(student.join_date_kort).toLocaleDateString() : '—'}</dd>
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

                {/* Guardian Information */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Guardian Information</p>
                    </div>
                    <div className="card-body">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt className="text-gray-500">Guardian Name</dt>
                                <dd className="font-medium mt-0.5">{student.guardian_name || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Guardian CNIC</dt>
                                <dd className="font-medium mt-0.5">{student.guardian_cnic || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Guardian Address</dt>
                                <dd className="font-medium mt-0.5">{student.guardian_address || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Relation</dt>
                                <dd className="font-medium mt-0.5">{student.guardian_relation || '—'}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Personal Preferences */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Personal Preferences</p>
                    </div>
                    <div className="card-body">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
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
                        </dl>
                    </div>
                </div>

                {/* Academic Information */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Academic Information</p>
                    </div>
                    <div className="card-body">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt className="text-gray-500">Program</dt>
                                <dd className="font-medium mt-0.5">{universityClass.class}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Semester</dt>
                                <dd className="font-medium mt-0.5">{student.semester || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Group/Stream</dt>
                                <dd className="font-medium mt-0.5">{student.group_stream || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Academic Year</dt>
                                <dd className="font-medium mt-0.5">{universityClass.academic_year}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Enrollment Status */}
                {(student.reason_left_kort || student.leaving_date) && (
                    <div className="card border-l-4 border-orange-500">
                        <div className="card-header">
                            <p className="card-title">Enrollment Status</p>
                        </div>
                        <div className="card-body">
                            <dl className="grid grid-cols-2 gap-4 text-sm">
                                {student.reason_left_kort && (
                                    <div>
                                        <dt className="text-gray-500">Reason for Leaving</dt>
                                        <dd className="font-medium mt-0.5">{student.reason_left_kort}</dd>
                                    </div>
                                )}
                                {student.leaving_date && (
                                    <div>
                                        <dt className="text-gray-500">Leaving Date</dt>
                                        <dd className="font-medium mt-0.5">{new Date(student.leaving_date).toLocaleDateString()}</dd>
                                    </div>
                                )}
                            </dl>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
