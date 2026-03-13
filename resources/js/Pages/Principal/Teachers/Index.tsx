import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { MagnifyingGlassIcon, EllipsisVerticalIcon, EyeIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, TeacherProfile, PaginatedData } from '@/types';

interface Props extends PageProps {
    teachers: PaginatedData<TeacherProfile>;
}

export default function TeachersIndex({ teachers }: Props) {
    const [openMenu, setOpenMenu] = useState<number | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    const handleSearchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setSearchQuery(value);
        // The search is handled by the backend via query string
        const url = new URL(window.location.href);
        if (value) {
            url.searchParams.set('search', value);
        } else {
            url.searchParams.delete('search');
        }
        window.history.replaceState({}, '', url.toString());
    };

    return (
        <AppLayout title="Teachers">
            <Head title="Teachers" />

            <div className="max-w-7xl mx-auto">
                <div className="page-header">
                    <div>
                        <h1 className="page-title">Teacher Management</h1>
                        <p className="page-subtitle">View and manage teacher profiles, assignments, and devices</p>
                    </div>
                </div>

                {/* Search Bar */}
                <div className="mb-6">
                    <div className="relative">
                        <input
                            type="text"
                            placeholder="Search teachers by name..."
                            value={searchQuery}
                            onChange={handleSearchChange}
                            className="form-input pl-10"
                        />
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                    </div>
                </div>

                {/* Teachers Table */}
                <div className="card">
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Class Assignments</th>
                                    <th>Active Devices</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {teachers.data.map((teacher) => (
                                    <tr key={teacher.id}>
                                        <td className="font-medium">{teacher.user?.name}</td>
                                        <td className="text-sm text-gray-600">{teacher.user?.email}</td>
                                        <td>
                                            {teacher.class_teacher_classes && teacher.class_teacher_classes.length > 0 ? (
                                                <div className="flex flex-wrap gap-1">
                                                    {teacher.class_teacher_classes.slice(0, 2).map((cls, idx) => (
                                                        <span key={idx} className="badge badge-blue text-xs">
                                                            {cls}
                                                        </span>
                                                    ))}
                                                    {teacher.class_teacher_classes.length > 2 && (
                                                        <span className="text-xs text-gray-500">
                                                            +{teacher.class_teacher_classes.length - 2} more
                                                        </span>
                                                    )}
                                                </div>
                                            ) : (
                                                <span className="text-gray-400">—</span>
                                            )}
                                        </td>
                                        <td>
                                            <span className="badge badge-blue">
                                                {teacher.user && 'active_devices_count' in teacher.user ? (teacher.user as any).active_devices_count : 0}
                                            </span>
                                        </td>
                                        <td>
                                            <span className={`badge ${teacher.is_active ? 'badge-green' : 'badge-gray'}`}>
                                                {teacher.is_active ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td>
                                            <div className="relative">
                                                <button
                                                    onClick={() => setOpenMenu(openMenu === teacher.id ? null : teacher.id)}
                                                    className="btn-icon btn-ghost"
                                                >
                                                    <EllipsisVerticalIcon className="w-5 h-5" />
                                                </button>

                                                {openMenu === teacher.id && (
                                                    <div className="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                        <Link
                                                            href={route('principal.teachers.show', teacher.id)}
                                                            className="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 border-b border-gray-100 text-sm"
                                                        >
                                                            <EyeIcon className="w-4 h-4" />
                                                            View Profile
                                                        </Link>
                                                        <button
                                                            onClick={() => {
                                                                // Could add edit functionality here
                                                                setOpenMenu(null);
                                                            }}
                                                            className="w-full text-left px-4 py-2 hover:bg-gray-50 text-sm text-gray-700"
                                                        >
                                                            Assign Device
                                                        </button>
                                                    </div>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                {teachers.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-gray-600">
                            Showing {teachers.from} to {teachers.to} of {teachers.total} teachers
                        </p>
                        <div className="flex gap-2">
                            {teachers.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    className={`px-3 py-2 text-sm rounded border ${
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
