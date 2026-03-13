import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon, ArchiveBoxIcon, SparklesIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import { Timetable, PaginatedData } from '@/types/timetable';
import { PageProps } from '@/types';

interface Props extends PageProps {
    timetables: PaginatedData<Timetable>;
    statuses: string[];
    terms: string[];
    academicYears: string[];
}

const statusBadgeStyles: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    generating: 'bg-blue-100 text-blue-800',
    generated: 'bg-green-100 text-green-800',
    published: 'bg-indigo-100 text-indigo-800',
    archived: 'bg-slate-100 text-slate-800',
};

const termLabels: Record<string, string> = {
    spring: 'Spring',
    summer: 'Summer',
    autumn: 'Autumn',
};

export default function Index({ timetables, statuses, terms, academicYears }: Props) {
    const [selectedStatus, setSelectedStatus] = useState('');
    const [selectedTerm, setSelectedTerm] = useState('');
    const [selectedYear, setSelectedYear] = useState('');
    const [searchQuery, setSearchQuery] = useState('');

    const handleFilter = (key: string, value: string) => {
        const params: Record<string, string> = {
            search: searchQuery,
        };

        if (key === 'status') {
            setSelectedStatus(value);
            if (value) params.status = value;
        } else if (key === 'term') {
            setSelectedTerm(value);
            if (value) params.term = value;
        } else if (key === 'academic_year') {
            setSelectedYear(value);
            if (value) params.academic_year = value;
        } else if (key === 'search') {
            setSearchQuery(value);
            params.search = value;
        }

        router.get(route('principal.timetables.index'), params, { preserveState: true });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this timetable?')) {
            router.delete(route('principal.timetables.destroy', id), { preserveScroll: true });
        }
    };

    const handleArchive = (id: number) => {
        if (confirm('Archive this timetable? You can still access it later.')) {
            router.post(route('principal.timetables.archive', id), {}, { preserveScroll: true });
        }
    };

    return (
        <AppLayout>
            <Head title="Timetables" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Timetables</h1>
                            <p className="mt-2 text-gray-600">Manage school schedules and generate timetables</p>
                        </div>
                        <Link
                            href={route('principal.timetables.create')}
                            className="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            <PlusIcon className="w-5 h-5" />
                            New Timetable
                        </Link>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input
                                    type="text"
                                    value={searchQuery}
                                    onChange={(e) => handleFilter('search', e.target.value)}
                                    placeholder="Search timetables..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                                <select
                                    value={selectedYear}
                                    onChange={(e) => handleFilter('academic_year', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">All Years</option>
                                    {academicYears.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Term</label>
                                <select
                                    value={selectedTerm}
                                    onChange={(e) => handleFilter('term', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">All Terms</option>
                                    {terms.map((term) => (
                                        <option key={term} value={term}>
                                            {termLabels[term as keyof typeof termLabels] || term}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    value={selectedStatus}
                                    onChange={(e) => handleFilter('status', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">All Statuses</option>
                                    {statuses.map((status) => (
                                        <option key={status} value={status}>
                                            {status.charAt(0).toUpperCase() + status.slice(1)}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Timetables List */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        {timetables.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Academic Year</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conflicts</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {timetables.data.map((timetable) => (
                                            <tr key={timetable.id} className="hover:bg-gray-50 transition">
                                                <td className="px-6 py-4">
                                                    <Link
                                                        href={route('principal.timetables.show', timetable.id)}
                                                        className="font-medium text-indigo-600 hover:text-indigo-700"
                                                    >
                                                        {timetable.name}
                                                    </Link>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-600">{timetable.academic_year}</td>
                                                <td className="px-6 py-4 text-sm text-gray-600">
                                                    {termLabels[timetable.term as keyof typeof termLabels]}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span
                                                        className={`px-3 py-1 text-xs font-semibold rounded-full ${
                                                            statusBadgeStyles[timetable.status] || 'bg-gray-100 text-gray-800'
                                                        }`}
                                                    >
                                                        {timetable.status.charAt(0).toUpperCase() + timetable.status.slice(1)}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-sm">
                                                    {timetable.conflict_count > 0 ? (
                                                        <span className="px-2 py-1 bg-red-100 text-red-800 text-xs rounded">
                                                            {timetable.conflict_count}
                                                        </span>
                                                    ) : (
                                                        <span className="text-gray-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-600">{timetable.creator?.name}</td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <Link
                                                            href={route('principal.timetables.show', timetable.id)}
                                                            className="text-indigo-600 hover:text-indigo-700 text-sm"
                                                        >
                                                            View
                                                        </Link>
                                                        {timetable.status === 'draft' && (
                                                            <button
                                                                onClick={() => handleDelete(timetable.id)}
                                                                className="text-red-600 hover:text-red-700 text-sm"
                                                                title="Delete"
                                                            >
                                                                <TrashIcon className="w-4 h-4" />
                                                            </button>
                                                        )}
                                                        {timetable.status !== 'archived' && timetable.status !== 'draft' && (
                                                            <button
                                                                onClick={() => handleArchive(timetable.id)}
                                                                className="text-amber-600 hover:text-amber-700 text-sm"
                                                                title="Archive"
                                                            >
                                                                <ArchiveBoxIcon className="w-4 h-4" />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="p-12 text-center">
                                <SparklesIcon className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                <p className="text-gray-600 mb-4">No timetables found</p>
                                <Link
                                    href={route('principal.timetables.create')}
                                    className="text-indigo-600 hover:text-indigo-700 font-medium"
                                >
                                    Create your first timetable
                                </Link>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {timetables.data.length > 0 && <Pagination data={timetables} />}
                </div>
            </div>
        </AppLayout>
    );
}
