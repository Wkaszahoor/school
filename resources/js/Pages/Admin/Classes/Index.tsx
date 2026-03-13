import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { PlusIcon, PencilSquareIcon, TrashIcon, EyeIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import type { PageProps, SchoolClass, PaginatedData } from '@/types';

interface Props extends PageProps {
    classes: PaginatedData<SchoolClass & { students_count: number }>;
}

declare const route: (name: string, params?: any) => string;

export default function ClassesIndex({ classes }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<(SchoolClass & { students_count: number }) | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<(SchoolClass & { students_count: number }) | null>(null);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedYear, setSelectedYear] = useState('');

    const createForm = useForm({ name: '', section: '', academic_year: '' });
    const editForm   = useForm({ name: '', section: '', academic_year: '', is_active: true });
    const deleteForm = useForm({});

    // Get unique academic years for filter
    const academicYears = Array.from(new Set(classes.data.map(c => c.academic_year))).sort().reverse();

    // Filter classes based on search and year
    const filteredClasses = classes.data.filter(cls => {
        const matchesSearch = !searchTerm ||
            cls.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            cls.section?.toLowerCase().includes(searchTerm.toLowerCase());
        const matchesYear = !selectedYear || cls.academic_year === selectedYear;
        return matchesSearch && matchesYear;
    });

    const openEdit = (cls: SchoolClass & { students_count: number }) => {
        editForm.setData({
            name: cls.name || cls.class,
            section: cls.section ?? '',
            academic_year: cls.academic_year,
            is_active: cls.is_active ?? true,
        });
        setEditTarget(cls);
    };

    const submitCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('admin.classes.store'), {
            onSuccess: () => { setCreateOpen(false); createForm.reset(); },
        });
    };

    const submitEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editTarget) return;
        editForm.put(route('admin.classes.update', editTarget.id), {
            onSuccess: () => setEditTarget(null),
        });
    };

    const submitDelete = () => {
        if (!deleteTarget) return;
        deleteForm.delete(route('admin.classes.destroy', deleteTarget.id), {
            onSuccess: () => setDeleteTarget(null),
        });
    };

    return (
        <AppLayout title="Classes">
            <Head title="Classes" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Classes</h1>
                    <p className="page-subtitle">{classes.total} classes</p>
                </div>
                <button onClick={() => setCreateOpen(true)} className="btn-primary w-full sm:w-auto">
                    <PlusIcon className="w-4 h-4" /> Add Class
                </button>
            </div>

            <div className="card">
                {/* Search and Filter Section */}
                <div className="card-header border-b">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label className="form-label text-xs">Search by Class Name or Section</label>
                            <input
                                type="text"
                                placeholder="Search..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="form-input"
                            />
                        </div>
                        <div>
                            <label className="form-label text-xs">Filter by Academic Year</label>
                            <select
                                value={selectedYear}
                                onChange={(e) => setSelectedYear(e.target.value)}
                                className="form-input"
                            >
                                <option value="">All Years</option>
                                {academicYears.map(year => (
                                    <option key={year} value={year}>{year}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Section</th>
                                <th>Academic Year</th>
                                <th>Students</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {filteredClasses.length === 0 ? (
                                <tr><td colSpan={6} className="text-center py-10 text-gray-400">No classes found</td></tr>
                            ) : filteredClasses.map(cls => (
                                <tr key={cls.id}>
                                    <td className="font-semibold text-gray-900">{cls.name}</td>
                                    <td>{cls.section || '—'}</td>
                                    <td>{cls.academic_year}</td>
                                    <td>
                                        <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700">
                                            {cls.students_count} students
                                        </span>
                                    </td>
                                    <td>
                                        <Badge color={cls.is_active ? 'green' : 'gray'}>
                                            {cls.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td className="space-x-1">
                                        <Link href={route('admin.classes.show', cls.id)} className="btn-ghost btn-sm">
                                            <EyeIcon className="w-4 h-4" /> View
                                        </Link>
                                        <button onClick={() => openEdit(cls)} className="btn-ghost btn-sm">
                                            <PencilSquareIcon className="w-4 h-4" /> Edit
                                        </button>
                                        {cls.students_count === 0 && (
                                            <button onClick={() => setDeleteTarget(cls)} className="btn-ghost btn-sm text-red-600 hover:text-red-700">
                                                <TrashIcon className="w-4 h-4" /> Delete
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">
                        Showing {filteredClasses.length} of {classes.total} classes
                        {(searchTerm || selectedYear) && (
                            <span className="ml-2 text-gray-400">
                                (filtered from {classes.total} total)
                            </span>
                        )}
                    </p>
                    {!searchTerm && !selectedYear && <Pagination data={classes} />}
                </div>
            </div>

            {/* Create Modal */}
            <Modal isOpen={createOpen} onClose={() => setCreateOpen(false)} title="Add New Class" size="sm">
                <form onSubmit={submitCreate} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Class Name <span className="text-red-500">*</span></label>
                        <input className="form-input" value={createForm.data.name}
                               onChange={e => createForm.setData('name', e.target.value)}
                               placeholder="e.g. Year 7, Class 9A" required />
                        {createForm.errors.name && <p className="form-error">{createForm.errors.name}</p>}
                    </div>
                    <div className="form-group">
                        <label className="form-label">Section</label>
                        <input className="form-input" value={createForm.data.section}
                               onChange={e => createForm.setData('section', e.target.value)}
                               placeholder="e.g. A, B, Science, Arts" />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Academic Year <span className="text-red-500">*</span></label>
                        <input className="form-input" value={createForm.data.academic_year}
                               onChange={e => createForm.setData('academic_year', e.target.value)}
                               placeholder="e.g. 2025-2026" required />
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                        <button type="button" onClick={() => setCreateOpen(false)} className="btn-secondary">Cancel</button>
                        <button type="submit" disabled={createForm.processing} className="btn-primary">
                            {createForm.processing ? 'Creating…' : 'Create Class'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={!!editTarget} onClose={() => setEditTarget(null)} title="Edit Class" size="sm">
                <form onSubmit={submitEdit} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Class Name <span className="text-red-500">*</span></label>
                        <input className="form-input" value={editForm.data.name}
                               onChange={e => editForm.setData('name', e.target.value)} required />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Section</label>
                        <input className="form-input" value={editForm.data.section}
                               onChange={e => editForm.setData('section', e.target.value)} />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Academic Year <span className="text-red-500">*</span></label>
                        <input className="form-input" value={editForm.data.academic_year}
                               onChange={e => editForm.setData('academic_year', e.target.value)} required />
                    </div>
                    <div className="form-group">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={editForm.data.is_active}
                                   onChange={e => editForm.setData('is_active', e.target.checked)}
                                   className="w-4 h-4 text-indigo-600 rounded" />
                            <span className="text-sm font-semibold text-gray-700">Active</span>
                        </label>
                    </div>
                    <div className="flex gap-2 justify-end pt-2">
                        <button type="button" onClick={() => setEditTarget(null)} className="btn-secondary">Cancel</button>
                        <button type="submit" disabled={editForm.processing} className="btn-primary">
                            {editForm.processing ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Delete Confirmation Modal */}
            <Modal isOpen={!!deleteTarget} onClose={() => setDeleteTarget(null)} title="Delete Class" size="sm">
                <div className="space-y-4">
                    <p className="text-gray-600">
                        Are you sure you want to permanently delete <span className="font-semibold text-gray-900">{deleteTarget?.name}</span>? This action cannot be undone.
                    </p>
                    <div className="flex gap-2 justify-end pt-2">
                        <button onClick={() => setDeleteTarget(null)} className="btn-secondary">
                            Cancel
                        </button>
                        <button onClick={submitDelete} disabled={deleteForm.processing} className="btn-danger">
                            {deleteForm.processing ? 'Deleting…' : 'Delete Class'}
                        </button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
