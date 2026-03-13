import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { PlusIcon, EyeIcon, XMarkIcon, CheckIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import type { PageProps, Student, SchoolClass, SubjectGroup, PaginatedData } from '@/types';

interface Props extends PageProps {
    students: PaginatedData<Student>;
    classes: SchoolClass[];
    groups: SubjectGroup[];
    filters: { search?: string; class_id?: string };
}

export default function PrincipalStudentsIndex({ students, classes, groups, filters }: Props) {
    const [filterName, setFilterName] = useState('');
    const [filterAdmission, setFilterAdmission] = useState('');
    const [filterClass, setFilterClass] = useState(filters.class_id ?? '');
    const [filterStream, setFilterStream] = useState(filters.subject_group_id ?? '');
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [bulkGroupId, setBulkGroupId] = useState('');
    const [bulkProcessing, setBulkProcessing] = useState(false);

    const toggleSelectStudent = (studentId: number) => {
        setSelectedStudents(prev =>
            prev.includes(studentId)
                ? prev.filter(id => id !== studentId)
                : [...prev, studentId]
        );
    };

    const toggleSelectAll = () => {
        if (selectedStudents.length === students.data.length) {
            setSelectedStudents([]);
        } else {
            setSelectedStudents(students.data.map(s => s.id));
        }
    };

    const handleBulkAssign = () => {
        if (!bulkGroupId || selectedStudents.length === 0) {
            alert('Please select students and a group');
            return;
        }

        setBulkProcessing(true);
        router.post(route('principal.students.bulk-assign'), {
            student_ids: selectedStudents,
            subject_group_id: bulkGroupId,
        }, {
            onSuccess: () => {
                setSelectedStudents([]);
                setBulkGroupId('');
                setBulkProcessing(false);
            },
            onError: () => {
                setBulkProcessing(false);
            }
        });
    };

    const applyFilters = () => {
        router.get(route('principal.students.index'), {
            search: filterName || filterAdmission || '',
            class_id: filterClass,
            subject_group_id: filterStream,
        }, {
            preserveState: true, replace: true,
        });
    };

    const clearFilters = () => {
        setFilterName('');
        setFilterAdmission('');
        setFilterClass('');
        setFilterStream('');
        router.get(route('principal.students.index'), {}, {
            preserveState: true, replace: true,
        });
    };

    const hasActiveFilters = filterName || filterAdmission || filterClass || filterStream;

    return (
        <AppLayout title="Students">
            <Head title="Students" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Students</h1>
                    <p className="page-subtitle">{students.total} active students</p>
                </div>
                <Link href={route('principal.students.create')} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> Admit Student
                </Link>
            </div>

            <div className="card">
                <div className="card-header">
                    <div className="w-full">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="font-semibold text-gray-900">Filters</h3>
                            {hasActiveFilters && (
                                <button
                                    onClick={clearFilters}
                                    className="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    <XMarkIcon className="w-4 h-4" /> Clear
                                </button>
                            )}
                        </div>
                        <div className="grid grid-cols-1 sm:grid-cols-5 gap-3">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Student Name</label>
                                <input
                                    type="text"
                                    placeholder="Filter by name…"
                                    value={filterName}
                                    onChange={(e) => setFilterName(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Admission No.</label>
                                <input
                                    type="text"
                                    placeholder="Filter by admission no…"
                                    value={filterAdmission}
                                    onChange={(e) => setFilterAdmission(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Class</label>
                                <select
                                    value={filterClass}
                                    onChange={(e) => setFilterClass(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">All Classes</option>
                                    {classes.map(c => (
                                        <option key={c.id} value={c.id}>
                                            {c.class}{c.section ? ` — ${c.section}` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 mb-1">Stream/Group</label>
                                <select
                                    value={filterStream}
                                    onChange={(e) => setFilterStream(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-indigo-500"
                                >
                                    <option value="">All Streams</option>
                                    {groups.map(g => (
                                        <option key={g.id} value={g.id}>
                                            {g.group_name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex items-end">
                                <button
                                    onClick={applyFilters}
                                    className="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
                                >
                                    Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {selectedStudents.length > 0 && (
                    <div className="mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <span className="font-semibold text-indigo-900">{selectedStudents.length} student{selectedStudents.length !== 1 ? 's' : ''} selected</span>
                            <select
                                value={bulkGroupId}
                                onChange={(e) => setBulkGroupId(e.target.value)}
                                className="form-select text-sm"
                            >
                                <option value="">Choose a group...</option>
                                {groups.map(g => (
                                    <option key={g.id} value={g.id}>{g.group_name}</option>
                                ))}
                            </select>
                            <button
                                onClick={handleBulkAssign}
                                disabled={!bulkGroupId || bulkProcessing}
                                className="btn-primary btn-sm"
                            >
                                <CheckIcon className="w-4 h-4" /> {bulkProcessing ? 'Assigning...' : 'Assign Group'}
                            </button>
                            <button
                                onClick={() => setSelectedStudents([])}
                                className="btn-ghost btn-sm"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                )}

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th style={{ width: '40px' }}>
                                    <input
                                        type="checkbox"
                                        checked={selectedStudents.length > 0 && selectedStudents.length === students.data.length}
                                        onChange={toggleSelectAll}
                                        className="w-4 h-4 text-indigo-600 rounded"
                                    />
                                </th>
                                <th>Name</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Stream/Group</th>
                                <th>Status</th>
                                <th></th>
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
                                        <input
                                            type="checkbox"
                                            checked={selectedStudents.includes(s.id)}
                                            onChange={() => toggleSelectStudent(s.id)}
                                            className="w-4 h-4 text-indigo-600 rounded"
                                        />
                                    </td>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                                {s.full_name.charAt(0).toUpperCase()}
                                            </div>
                                            <span className="font-semibold text-gray-900">{s.full_name}</span>
                                        </div>
                                    </td>
                                    <td className="text-sm text-gray-600">{s.admission_no}</td>
                                    <td className="font-medium">{s.class?.class}{s.class?.section ? ` - ${s.class.section}` : ''}</td>
                                    <td className="text-gray-600">{s.subjectGroup?.group_name || '—'}</td>
                                    <td>
                                        <Badge color={s.is_active ? 'green' : 'gray'}>
                                            {s.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td>
                                        <Link href={route('principal.students.show', s.id)} className="btn-ghost btn-sm">
                                            <EyeIcon className="w-4 h-4" /> View
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">
                        Showing {students.from ?? 0}–{students.to ?? 0} of {students.total}
                    </p>
                    <Pagination data={students} />
                </div>
            </div>
        </AppLayout>
    );
}
