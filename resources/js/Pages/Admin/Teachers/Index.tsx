import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, EyeIcon, TrashIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import SearchInput from '@/Components/SearchInput';
import Pagination from '@/Components/Pagination';
import type { PageProps, TeacherProfile, PaginatedData } from '@/types';

interface Props extends PageProps {
    teachers: PaginatedData<TeacherProfile>;
}

export default function TeachersIndex({ teachers }: Props) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    const handleDelete = (teacherId: number, teacherName: string) => {
        if (confirm(`Are you sure you want to archive ${teacherName}? They can be restored later.`)) {
            setDeletingId(teacherId);
            router.delete(route('admin.teachers.destroy', teacherId), {
                onSuccess: () => {
                    setDeletingId(null);
                },
                onError: () => {
                    setDeletingId(null);
                    alert('Failed to archive teacher');
                },
            });
        }
    };

    return (
        <AppLayout title="Teachers">
            <Head title="Teachers" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Teachers</h1>
                    <p className="page-subtitle">{teachers.total} staff members</p>
                </div>
                <Link href={route('admin.teachers.create')} className="btn-primary w-full sm:w-auto">
                    <PlusIcon className="w-4 h-4" /> Add Teacher
                </Link>
            </div>

            <div className="card">
                <div className="card-header">
                    <SearchInput placeholder="Search by name…" />
                </div>
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Employee ID</th>
                                <th>Qualification</th>
                                <th>Specialisation</th>
                                <th>Class Teacher</th>
                                <th>Date Joined</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {teachers.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="text-center py-10 text-gray-400">
                                        No teachers found
                                    </td>
                                </tr>
                            ) : teachers.data.map(t => (
                                <tr key={t.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                                {t.user?.name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}
                                            </div>
                                            <div>
                                                <p className="font-semibold text-gray-900">{t.user?.name}</p>
                                                <p className="text-xs text-gray-400">{t.user?.email}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="font-mono text-sm">{t.employee_id}</td>
                                    <td>{t.qualification || '—'}</td>
                                    <td>{t.specialisation || '—'}</td>
                                    <td>
                                        {t.class_teacher_classes && t.class_teacher_classes.length > 0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {t.class_teacher_classes.map((cls, idx) => (
                                                    <Badge key={idx} color="blue">{cls}</Badge>
                                                ))}
                                            </div>
                                        ) : (
                                            <span className="text-gray-400">—</span>
                                        )}
                                    </td>
                                    <td>{t.date_joined || '—'}</td>
                                    <td>
                                        <Badge color={t.is_active ? 'green' : 'gray'}>
                                            {t.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td>
                                        <div className="flex gap-2">
                                            <Link href={route('admin.teachers.show', t.id)} className="btn-ghost btn-sm">
                                                <EyeIcon className="w-4 h-4" /> View
                                            </Link>
                                            <button
                                                onClick={() => handleDelete(t.id, t.user?.name || 'Teacher')}
                                                disabled={deletingId === t.id}
                                                className="btn-ghost btn-sm text-red-600 hover:bg-red-50"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                                {deletingId === t.id ? 'Archiving…' : 'Archive'}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">
                        Showing {teachers.from ?? 0}–{teachers.to ?? 0} of {teachers.total}
                    </p>
                    <Pagination data={teachers} />
                </div>
            </div>
        </AppLayout>
    );
}
