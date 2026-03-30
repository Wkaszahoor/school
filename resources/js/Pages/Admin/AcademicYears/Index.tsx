import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, PencilSquareIcon, TrashIcon, ArrowPathIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData } from '@/types';

interface AcademicYear {
    id: number;
    year: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    description: string | null;
    deleted_at: string | null;
}

interface Props extends PageProps {
    academicYears: PaginatedData<AcademicYear>;
}

export default function AdminAcademicYearsIndex({ academicYears }: Props) {
    const [searchQuery, setSearchQuery] = useState('');

    const handleDelete = (id: number, year: string) => {
        if (confirm(`Archive academic year "${year}"? This action can be undone.`)) {
            router.delete(route('admin.academic-years.destroy', id));
        }
    };

    const handleRestore = (id: number, year: string) => {
        if (confirm(`Restore academic year "${year}"?`)) {
            router.post(route('admin.academic-years.restore', id));
        }
    };

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('admin.academic-years.index'), { search: searchQuery });
    };

    const clearSearch = () => {
        setSearchQuery('');
        router.get(route('admin.academic-years.index'));
    };

    return (
        <AppLayout title="Academic Years">
            <Head title="Academic Years" />

            <div className="page-header">
                <div className="flex items-center justify-between flex-1">
                    <div>
                        <h1 className="page-title">Academic Years</h1>
                        <p className="page-subtitle">Manage academic years for the school</p>
                    </div>
                    <Link href={route('admin.academic-years.create')} className="btn-primary">
                        <PlusIcon className="w-4 h-4" /> Add Academic Year
                    </Link>
                </div>
            </div>

            <form onSubmit={handleSearch} className="mb-5">
                <div className="flex items-center gap-2">
                    <div className="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            placeholder="Search academic years..."
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            className="form-input pl-10 pr-9"
                        />
                        <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                        {searchQuery && (
                            <button
                                type="button"
                                onClick={clearSearch}
                                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                            >
                                <XCircleIcon className="w-4 h-4" />
                            </button>
                        )}
                    </div>
                </div>
            </form>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {academicYears.data.length > 0 ? (
                                academicYears.data.map((year) => (
                                    <tr key={year.id} className={year.deleted_at ? 'opacity-60' : ''}>
                                        <td className="font-semibold">{year.year}</td>
                                        <td className="text-sm text-gray-600">
                                            {new Date(year.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })} —{' '}
                                            {new Date(year.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                        </td>
                                        <td>
                                            {year.deleted_at ? (
                                                <Badge color="gray">Archived</Badge>
                                            ) : (
                                                <Badge color={year.is_active ? 'green' : 'yellow'}>{year.is_active ? 'Active' : 'Inactive'}</Badge>
                                            )}
                                        </td>
                                        <td className="text-sm text-gray-600 truncate max-w-xs">{year.description || '—'}</td>
                                        <td className="text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {year.deleted_at ? (
                                                    <button
                                                        onClick={() => handleRestore(year.id, year.year)}
                                                        className="btn-ghost btn-sm text-indigo-600 hover:text-indigo-700"
                                                        title="Restore"
                                                    >
                                                        <ArrowPathIcon className="w-4 h-4" />
                                                    </button>
                                                ) : (
                                                    <>
                                                        <Link
                                                            href={route('admin.academic-years.edit', year.id)}
                                                            className="btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                                                            title="Edit"
                                                        >
                                                            <PencilSquareIcon className="w-4 h-4" />
                                                        </Link>
                                                        <button
                                                            onClick={() => handleDelete(year.id, year.year)}
                                                            className="btn-ghost btn-sm text-red-600 hover:text-red-700"
                                                            title="Delete"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={5} className="text-center text-gray-500 py-6">
                                        No academic years found
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* Pagination */}
            {academicYears.last_page > 1 && (
                <div className="flex items-center justify-center gap-2 mt-6">
                    {academicYears.links.map((link, idx) => (
                        <Link
                            key={idx}
                            href={link.url || '#'}
                            className={`px-3 py-1 rounded border ${
                                link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : link.url
                                      ? 'border-gray-300 hover:bg-gray-50'
                                      : 'border-gray-200 text-gray-400 cursor-not-allowed'
                            }`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
