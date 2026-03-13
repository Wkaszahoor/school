import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ChevronLeftIcon, PencilIcon, TrashIcon, PlusIcon } from '@heroicons/react/24/outline';

interface Subject {
    id: number;
    subject_name: string;
    subject_code: string;
    is_active: boolean;
}

interface Props {
    subjects: Subject[];
}

export default function SubjectsPage({ subjects: initialSubjects }: Props) {
    const [subjects, setSubjects] = useState<Subject[]>(initialSubjects);
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState<number | null>(null);
    const { data, setData, post, delete: destroy, processing } = useForm({
        id: null as number | null,
        subject_name: '',
        subject_code: '',
        is_active: true,
    });

    const handleEdit = (subject: Subject) => {
        setEditingId(subject.id);
        setData({
            id: subject.id,
            subject_name: subject.subject_name,
            subject_code: subject.subject_code,
            is_active: subject.is_active,
        });
        setShowForm(true);
    };

    const handleDelete = (subject: Subject) => {
        if (confirm(`Delete "${subject.subject_name}"?`)) {
            destroy(route('admin.subject-management.subject.destroy', subject.id));
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.subject-management.subject.store'), {
            onSuccess: () => {
                setShowForm(false);
                setEditingId(null);
                setData({ id: null, subject_name: '', subject_code: '', is_active: true });
            },
        });
    };

    const handleReset = () => {
        setEditingId(null);
        setData({ id: null, subject_name: '', subject_code: '', is_active: true });
    };

    return (
        <AppLayout>
            <Head title="Manage Subjects" />

            <Link href={route('admin.subject-management.index')} className="btn-link mb-4">
                <ChevronLeftIcon className="w-4 h-4" /> Back
            </Link>

            <div className="page-header">
                <div>
                    <h1 className="page-title">Manage Subjects</h1>
                    <p className="page-subtitle">Create, edit, and manage all school subjects</p>
                </div>
                <button
                    onClick={() => {
                        handleReset();
                        setShowForm(!showForm);
                    }}
                    className="btn-primary gap-1"
                >
                    <PlusIcon className="w-4 h-4" /> Add Subject
                </button>
            </div>

            {/* Form */}
            {showForm && (
                <div className="card mb-6 bg-blue-50 border-blue-200">
                    <div className="card-header">
                        <h3 className="font-semibold">{editingId ? 'Edit Subject' : 'Add New Subject'}</h3>
                    </div>
                    <form onSubmit={handleSubmit} className="card-body space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block font-medium mb-1">Subject Name</label>
                                <input
                                    type="text"
                                    value={data.subject_name}
                                    onChange={(e) => setData('subject_name', e.target.value)}
                                    placeholder="e.g., Physics"
                                    className="input w-full"
                                    required
                                />
                            </div>
                            <div>
                                <label className="block font-medium mb-1">Subject Code</label>
                                <input
                                    type="text"
                                    value={data.subject_code}
                                    onChange={(e) => setData('subject_code', e.target.value.toUpperCase())}
                                    placeholder="e.g., PHY"
                                    maxLength={20}
                                    className="input w-full"
                                    required
                                />
                            </div>
                        </div>

                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            <span className="font-medium">Active</span>
                        </label>

                        <div className="flex gap-2">
                            <button type="submit" disabled={processing} className="btn-primary flex-1">
                                {processing ? 'Saving...' : editingId ? 'Update' : 'Create'}
                            </button>
                            <button
                                type="button"
                                onClick={() => {
                                    setShowForm(false);
                                    handleReset();
                                }}
                                className="btn-outline"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            )}

            {/* Subjects List */}
            <div className="card">
                <div className="card-header">
                    <h2 className="font-semibold">All Subjects ({subjects.length})</h2>
                </div>
                <div className="card-body">
                    {subjects.length === 0 ? (
                        <p className="text-gray-500 italic">No subjects found</p>
                    ) : (
                        <div className="space-y-2">
                            {subjects.map((subject) => (
                                <div key={subject.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition">
                                    <div className="flex-1">
                                        <p className="font-semibold text-gray-900">{subject.subject_name}</p>
                                        <p className="text-sm text-gray-500">{subject.subject_code}</p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ${subject.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-700'}`}>
                                            {subject.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        <div className="flex gap-2">
                                            <button onClick={() => handleEdit(subject)} className="btn-outline text-sm gap-1">
                                                <PencilIcon className="w-4 h-4" /> Edit
                                            </button>
                                            <button onClick={() => handleDelete(subject)} className="btn-danger text-sm gap-1">
                                                <TrashIcon className="w-4 h-4" /> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
