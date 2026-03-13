import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, SchoolClass, Student } from '@/types';

interface Props extends PageProps {
    class: SchoolClass;
    students: Student[];
}

export default function ShowClass({ class: schoolClass, students }: Props) {
    return (
        <AppLayout title={schoolClass.name || schoolClass.class}>
            <Head title={schoolClass.name || schoolClass.class} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.classes.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">{schoolClass.name || schoolClass.class}</h1>
                        <p className="page-subtitle">{students.length} students · {schoolClass.academic_year}</p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 gap-5">
                {/* Class Info */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Class Information</p>
                    </div>
                    <div className="card-body">
                        <dl className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt className="text-gray-500">Class Name</dt>
                                <dd className="font-medium mt-0.5">{schoolClass.name || schoolClass.class}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Section</dt>
                                <dd className="font-medium mt-0.5">{schoolClass.section || '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Academic Year</dt>
                                <dd className="font-medium mt-0.5">{schoolClass.academic_year}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Total Students</dt>
                                <dd className="font-medium mt-0.5 flex items-center gap-2">
                                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                        {students.length} students
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Status</dt>
                                <dd className="font-medium mt-0.5">
                                    <Badge color={schoolClass.is_active ? 'green' : 'gray'}>
                                        {schoolClass.is_active ? 'Active' : 'Inactive'}
                                    </Badge>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {/* Students List */}
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Students ({students.length})</p>
                    </div>
                    {students.length === 0 ? (
                        <div className="card-body text-center py-10 text-gray-400">
                            No students in this class
                        </div>
                    ) : (
                        <div className="table-wrapper">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Admission No.</th>
                                        <th>Gender</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map(student => (
                                        <tr key={student.id}>
                                            <td className="font-semibold text-gray-900">{student.full_name}</td>
                                            <td className="text-sm text-gray-600">{student.admission_no}</td>
                                            <td className="capitalize text-sm">{student.gender}</td>
                                            <td className="text-sm text-gray-600">{student.phone || '—'}</td>
                                            <td>
                                                <Badge color={student.is_active ? 'green' : 'gray'}>
                                                    {student.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </td>
                                            <td>
                                                <Link href={route('admin.students.show', student.id)} className="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                                    View Profile
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
