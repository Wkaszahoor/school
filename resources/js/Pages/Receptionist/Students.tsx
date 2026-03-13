import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, EyeIcon, FunnelIcon, ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import SearchInput from '@/Components/SearchInput';
import Pagination from '@/Components/Pagination';
import type { PageProps, Student, SchoolClass, PaginatedData } from '@/types';

interface Props extends PageProps {
    students: PaginatedData<Student>;
    classes: SchoolClass[];
    filters: { search?: string; class_id?: string };
}

export default function ReceptionistStudents({ students, classes, filters }: Props) {
    const handleClassFilter = (classId: string) => {
        router.get(route('receptionist.students'), { ...filters, class_id: classId }, {
            preserveState: true, replace: true,
        });
    };

    return (
        <AppLayout title="Students">
            <Head title="Students" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Students</h1>
                    <p className="page-subtitle">{students.total} registered students</p>
                </div>
                <div className="flex gap-2">
                    <Link href={route('receptionist.import-students.index')} className="btn-secondary">
                        <ArrowUpTrayIcon className="w-4 h-4" /> Import Excel
                    </Link>
                    <Link href={route('receptionist.students.create')} className="btn-primary">
                        <PlusIcon className="w-4 h-4" /> Register Student
                    </Link>
                </div>
            </div>

            <div className="card">
                <div className="card-header gap-3 flex-wrap">
                    <SearchInput value={filters.search} placeholder="Search name or admission no…" />
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="w-4 h-4 text-gray-400" />
                        <select className="form-select !py-2 !text-xs w-44" value={filters.class_id ?? ''}
                                onChange={e => handleClassFilter(e.target.value)}>
                            <option value="">All Classes</option>
                            {classes.map(c => (
                                <option key={c.id} value={c.id}>
                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Gender</th>
                                <th>Guardian</th>
                                <th>Phone</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            {students.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="text-center py-12 text-gray-400">No students found</td>
                                </tr>
                            ) : students.data.map(s => (
                                <tr key={s.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                                {s.full_name.charAt(0).toUpperCase()}
                                            </div>
                                            <span className="font-semibold text-gray-900">{s.full_name}</span>
                                        </div>
                                    </td>
                                    <td className="font-mono text-sm">{s.admission_no}</td>
                                    <td>{s.class?.name}{s.class?.section ? ` — ${s.class.section}` : ''}</td>
                                    <td className="capitalize">{s.gender}</td>
                                    <td>{s.guardian_name || '—'}</td>
                                    <td>{s.guardian_phone || '—'}</td>
                                    <td className="text-gray-400 text-xs">{s.created_at ? new Date(s.created_at).toLocaleDateString() : '—'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">Showing {students.from ?? 0}–{students.to ?? 0} of {students.total}</p>
                    <Pagination data={students.links} />
                </div>
            </div>
        </AppLayout>
    );
}
