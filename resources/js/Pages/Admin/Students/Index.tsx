import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    PlusIcon, FunnelIcon, EyeIcon, PencilSquareIcon,
    UserGroupIcon, ArrowDownTrayIcon, ArrowUpTrayIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import SearchInput from '@/Components/SearchInput';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, Student, SchoolClass } from '@/types';

interface Props extends PageProps {
    students: PaginatedData<Student>;
    classes: SchoolClass[];
}

export default function StudentsIndex({ students, classes }: Props) {
    const [classFilter, setClassFilter] = useState('');

    const handleClassChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
        setClassFilter(e.target.value);
        router.get(route('admin.students.index'), { class_id: e.target.value }, {
            preserveState: true, replace: true,
        });
    };

    return (
        <AppLayout title="Students">
            <Head title="Students" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Students</h1>
                    <p className="page-subtitle">{students.total} students registered</p>
                </div>
                <div className="flex gap-2">
                    <Link href={route('admin.import-students.index')} className="btn-secondary">
                        <ArrowUpTrayIcon className="w-4 h-4" /> Import Excel
                    </Link>
                    <Link href={route('admin.students.create')} className="btn-primary">
                        <PlusIcon className="w-4 h-4" />
                        Add Student
                    </Link>
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body !py-4 flex flex-col sm:flex-row flex-wrap gap-3 items-stretch sm:items-center">
                    <SearchInput placeholder="Search by name or admission no…" className="w-full sm:w-64" />
                    <select
                        value={classFilter}
                        onChange={handleClassChange}
                        className="form-select w-full sm:w-48"
                    >
                        <option value="">All Classes</option>
                        {classes.map(c => (
                            <option key={c.id} value={c.id}>
                                Class {c.class}{c.section ? ` — ${c.section}` : ''}
                            </option>
                        ))}
                    </select>
                    <div className="sm:ml-auto flex gap-2 w-full sm:w-auto">
                        <button className="btn-secondary btn-sm flex-1 sm:flex-none">
                            <ArrowDownTrayIcon className="w-4 h-4" />
                            <span className="hidden sm:inline">Export</span>
                        </button>
                    </div>
                </div>
            </div>

            {/* Table */}
            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Gender</th>
                                <th>Guardian</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {students.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8}>
                                        <div className="empty-state py-12">
                                            <UserGroupIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No students found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : students.data.map(student => (
                                <tr key={student.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="avatar-sm bg-indigo-600">
                                                {student.full_name?.charAt(0) ?? 'S'}
                                            </div>
                                            <span className="font-medium text-gray-900">{student.full_name}</span>
                                        </div>
                                    </td>
                                    <td className="font-mono text-xs text-gray-500">{student.admission_no}</td>
                                    <td>
                                        {student.class
                                            ? `Class ${student.class.class}${student.class.section ? ` — ${student.class.section}` : ''}`
                                            : <span className="text-gray-400">—</span>
                                        }
                                    </td>
                                    <td className="capitalize">{student.gender}</td>
                                    <td>{student.guardian_name ?? <span className="text-gray-400">—</span>}</td>
                                    <td>
                                        <Badge color={student.is_active ? 'green' : 'red'}>
                                            {student.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td className="text-gray-500">
                                        {student.created_at
                                            ? new Date(student.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })
                                            : '—'
                                        }
                                    </td>
                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            <Link href={route('admin.students.show', student.id)}
                                                  className="btn-ghost btn-icon btn-sm" title="View">
                                                <EyeIcon className="w-4 h-4" />
                                            </Link>
                                            <Link href={route('admin.students.edit', student.id)}
                                                  className="btn-ghost btn-icon btn-sm" title="Edit">
                                                <PencilSquareIcon className="w-4 h-4" />
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={students} />
                </div>
            </div>
        </AppLayout>
    );
}
