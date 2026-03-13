import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { BuildingLibraryIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import type { PageProps, Student, SchoolClass, PaginatedData } from '@/types';

interface Props extends PageProps {
    universityClass: SchoolClass;
    students: PaginatedData<Student>;
}

export default function UniversityStudentsIndex({ universityClass, students }: Props) {
    const [searchTerm, setSearchTerm] = useState('');

    const filteredStudents = students.data.filter(student =>
        student.full_name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
        student.admission_no?.toLowerCase().includes(searchTerm.toLowerCase())
    );

    return (
        <AppLayout title="University Students">
            <Head title="University Students" />

            <div className="page-header">
                <div>
                    <div className="flex items-center gap-3">
                        <BuildingLibraryIcon className="w-8 h-8 text-indigo-600" />
                        <div>
                            <h1 className="page-title">University Students</h1>
                            <p className="page-subtitle">{students.total} students pursuing higher education</p>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card">
                {/* Class Info Card */}
                <div className="card-header border-b">
                    <p className="card-title">Program Information</p>
                </div>
                <div className="card-body">
                    <dl className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <dt className="text-gray-500">Program</dt>
                            <dd className="font-medium mt-0.5">{universityClass.class}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Academic Year</dt>
                            <dd className="font-medium mt-0.5">{universityClass.academic_year}</dd>
                        </div>
                        <div>
                            <dt className="text-gray-500">Total Students</dt>
                            <dd className="font-medium mt-0.5 flex items-center gap-2">
                                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                    {students.total} students
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {/* Students List */}
            <div className="card mt-5">
                <div className="card-header">
                    <div className="flex items-center justify-between">
                        <p className="card-title">University Students ({students.total})</p>
                        <input
                            type="text"
                            placeholder="Search by name or admission no..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="form-input w-64"
                        />
                    </div>
                </div>
                {filteredStudents.length === 0 ? (
                    <div className="card-body text-center py-10 text-gray-400">
                        No university students found
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
                                {filteredStudents.map(student => (
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
                                            <Link href={route('admin.university-students.show', student.id)} className="text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                                View Profile
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
                <div className="card-footer">
                    <p className="text-sm text-gray-500">Showing {students.from ?? 0}–{students.to ?? 0} of {students.total}</p>
                    <Pagination data={students} />
                </div>
            </div>
        </AppLayout>
    );
}
