import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { MagnifyingGlassIcon, EyeIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, PaginatedData } from '@/types';

interface Selection {
    id: number;
    student_id: number;
    subject_id: number;
    subject_group_id: number;
    subject_type: 'compulsory' | 'optional';
    selected_at: string;
    student: {
        id: number;
        full_name: string;
        class: {
            class: string;
            section: string | null;
        };
    };
    subject: {
        id: number;
        subject_name: string;
    };
    subjectGroup: {
        id: number;
        group_name: string;
    };
}

interface Props extends PageProps {
    selections: PaginatedData<Selection>;
    classes: Array<{ id: number; class: string; section: string | null }>;
    subjectGroups: Array<{ id: number; group_name: string; stream: string | null }>;
}

export default function StudentSelectionsIndex({ selections, classes, subjectGroups }: Props) {
    const [filters, setFilters] = useState({
        class_id: '',
        stream: '',
        group_id: '',
    });

    const handleFilterChange = (field: string, value: string) => {
        setFilters(prev => ({ ...prev, [field]: value }));
    };

    const applyFilters = () => {
        const params = new URLSearchParams();
        if (filters.class_id) params.append('class_id', filters.class_id);
        if (filters.stream) params.append('stream', filters.stream);
        if (filters.group_id) params.append('group_id', filters.group_id);

        window.location.href = `${route('principal.student-selections.index')}?${params.toString()}`;
    };

    return (
        <AppLayout title="Student Selections">
            <Head title="Student Selections" />

            <div className="max-w-7xl mx-auto">
                <div className="page-header mb-6">
                    <div>
                        <h1 className="page-title">Student Subject Selections</h1>
                        <p className="page-subtitle">Track all student subject selections across classes and streams</p>
                    </div>
                    <Link href={route('principal.student-selections.reports')} className="btn-primary">
                        View Reports
                    </Link>
                </div>

                {/* Filters */}
                <div className="card mb-6">
                    <div className="card-body">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div className="form-group">
                                <label className="form-label">Class</label>
                                <select
                                    value={filters.class_id}
                                    onChange={(e) => handleFilterChange('class_id', e.target.value)}
                                    className="form-input"
                                >
                                    <option value="">All Classes</option>
                                    {classes.map(c => (
                                        <option key={c.id} value={c.id}>
                                            {c.class}{c.section ? `-${c.section}` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="form-group">
                                <label className="form-label">Stream</label>
                                <select
                                    value={filters.stream}
                                    onChange={(e) => handleFilterChange('stream', e.target.value)}
                                    className="form-input"
                                >
                                    <option value="">All Streams</option>
                                    <option value="ICS">ICS</option>
                                    <option value="Pre-Medical">Pre-Medical</option>
                                    <option value="General">General</option>
                                </select>
                            </div>

                            <div className="form-group">
                                <label className="form-label">Subject Group</label>
                                <select
                                    value={filters.group_id}
                                    onChange={(e) => handleFilterChange('group_id', e.target.value)}
                                    className="form-input"
                                >
                                    <option value="">All Groups</option>
                                    {subjectGroups.map(g => (
                                        <option key={g.id} value={g.id}>
                                            {g.group_name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>

                        <button onClick={applyFilters} className="btn-primary mt-4">
                            Apply Filters
                        </button>
                    </div>
                </div>

                {/* Selections Table */}
                <div className="card">
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Group</th>
                                    <th>Type</th>
                                    <th>Selected At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {selections.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="text-center py-8 text-gray-500">
                                            <p className="text-sm">No student selections found</p>
                                            <p className="text-xs mt-1">Students haven't selected their subjects yet or no filters matched</p>
                                        </td>
                                    </tr>
                                ) : (
                                    selections.data.map((selection) => (
                                        <tr key={selection.id}>
                                            <td className="font-medium">{selection.student.full_name}</td>
                                            <td>
                                                {selection.student.class.class}
                                                {selection.student.class.section && `-${selection.student.class.section}`}
                                            </td>
                                            <td>{selection.subject.subject_name}</td>
                                            <td>{selection.subjectGroup.group_name}</td>
                                            <td>
                                                <span className={`badge ${selection.subject_type === 'compulsory' ? 'badge-blue' : 'badge-yellow'}`}>
                                                    {selection.subject_type}
                                                </span>
                                            </td>
                                            <td className="text-sm text-gray-600">
                                                {new Date(selection.selected_at).toLocaleDateString()}
                                            </td>
                                            <td>
                                                <Link
                                                    href={route('principal.student-selections.show', selection.student_id)}
                                                    className="btn-sm btn-ghost"
                                                    title="View all selections"
                                                >
                                                    <EyeIcon className="w-4 h-4" />
                                                </Link>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                {/* Pagination */}
                {selections.last_page > 1 && (
                    <div className="mt-6 flex items-center justify-between">
                        <p className="text-sm text-gray-600">
                            Showing {selections.from} to {selections.to} of {selections.total} selections
                        </p>
                        <div className="flex gap-2">
                            {selections.links.map((link, idx) => (
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
