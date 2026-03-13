import React, { useState } from 'react';
import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { ChevronLeftIcon, PencilIcon, TrashIcon, PlusIcon } from '@heroicons/react/24/outline';

interface Subject {
    id: number;
    subject_name: string;
    subject_code: string;
    subject_type?: 'compulsory' | 'major' | 'optional';
}

interface SubjectGroup {
    id: number;
    group_name: string;
    stream: string;
    description?: string;
    min_select: number;
    max_select: number;
    is_optional_group: boolean;
    subjects: Subject[];
}

interface SchoolClass {
    id: number;
    class: number;
    section?: string;
    subjectGroups: SubjectGroup[];
}

interface Props {
    class: SchoolClass;
    allSubjects: Subject[];
}

export default function ClassDetail({ class: schoolClass, allSubjects }: Props) {
    const [showCreateForm, setShowCreateForm] = useState(false);
    const [editingGroupId, setEditingGroupId] = useState<number | null>(null);
    const { delete: destroy } = useForm();

    const handleDeleteGroup = (group: SubjectGroup) => {
        if (confirm(`Delete group "${group.group_name}"?`)) {
            destroy(route('admin.subject-management.group.destroy', group.id));
        }
    };

    return (
        <AppLayout>
            <Head title={`Class ${schoolClass.class} - Subject Management`} />

            <div>
                <Link href={route('admin.subject-management.index')} className="btn-link mb-4">
                    <ChevronLeftIcon className="w-4 h-4" /> Back
                </Link>

                <div className="page-header">
                    <h1 className="page-title">Class {schoolClass.class}{schoolClass.section && ` - ${schoolClass.section}`}</h1>
                    <p className="page-subtitle">Manage subject groups and subject assignments</p>
                </div>

                {/* Subject Groups */}
                <div className="card">
                    <div className="card-header flex justify-between items-center">
                        <h2 className="font-semibold">Subject Groups ({schoolClass.subjectGroups.length})</h2>
                        <button onClick={() => setShowCreateForm(!showCreateForm)} className="btn-primary text-sm gap-1">
                            <PlusIcon className="w-4 h-4" /> Add Group
                        </button>
                    </div>

                    <div className="card-body space-y-4">
                        {showCreateForm && (
                            <div className="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-4">
                                <h3 className="font-semibold mb-3">Create New Subject Group</h3>
                                <CreateGroupForm
                                    classId={schoolClass.id}
                                    allSubjects={allSubjects}
                                    onSuccess={() => setShowCreateForm(false)}
                                />
                            </div>
                        )}

                        {schoolClass.subjectGroups.length === 0 ? (
                            <p className="text-gray-500 italic">No subject groups configured for this class</p>
                        ) : (
                            <div className="space-y-4">
                                {schoolClass.subjectGroups.map((group) => (
                                    <div key={group.id} className="border rounded-lg p-4 space-y-3">
                                        <div className="flex justify-between items-start">
                                            <div>
                                                <h3 className="font-semibold text-lg">{group.group_name}</h3>
                                                {group.description && <p className="text-gray-600 text-sm">{group.description}</p>}
                                            </div>
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => setEditingGroupId(editingGroupId === group.id ? null : group.id)}
                                                    className="btn-outline text-sm gap-1"
                                                >
                                                    <PencilIcon className="w-4 h-4" /> Edit
                                                </button>
                                                <button
                                                    onClick={() => handleDeleteGroup(group)}
                                                    className="btn-danger text-sm gap-1"
                                                >
                                                    <TrashIcon className="w-4 h-4" /> Delete
                                                </button>
                                            </div>
                                        </div>

                                        {/* Group Info */}
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm bg-gray-50 p-3 rounded">
                                            <div>
                                                <p className="text-gray-600">Stream</p>
                                                <p className="font-semibold">{group.stream}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Min Select</p>
                                                <p className="font-semibold">{group.min_select}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Max Select</p>
                                                <p className="font-semibold">{group.max_select}</p>
                                            </div>
                                            <div>
                                                <p className="text-gray-600">Type</p>
                                                <p className="font-semibold">{group.is_optional_group ? 'Optional' : 'Compulsory'}</p>
                                            </div>
                                        </div>

                                        {/* Subjects */}
                                        <div>
                                            <p className="font-semibold mb-2">Subjects ({group.subjects.length})</p>
                                            <div className="space-y-1">
                                                {group.subjects.map((subject) => (
                                                    <div key={subject.id} className="flex items-center justify-between p-2 bg-gray-50 rounded text-sm">
                                                        <div>
                                                            <p className="font-medium">{subject.subject_name}</p>
                                                            <p className="text-xs text-gray-500">{subject.subject_code}</p>
                                                        </div>
                                                        <span
                                                            className={`text-xs font-medium px-2 py-1 rounded ${
                                                                subject.subject_type === 'compulsory'
                                                                    ? 'bg-red-100 text-red-700'
                                                                    : subject.subject_type === 'major'
                                                                      ? 'bg-blue-100 text-blue-700'
                                                                      : 'bg-green-100 text-green-700'
                                                            }`}
                                                        >
                                                            {subject.subject_type}
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Edit Form */}
                                        {editingGroupId === group.id && (
                                            <div className="bg-blue-50 p-3 rounded border border-blue-200 mt-3">
                                                <h4 className="font-semibold mb-2 text-sm">Edit Group</h4>
                                                <EditGroupForm
                                                    group={group}
                                                    allSubjects={allSubjects}
                                                    onSuccess={() => setEditingGroupId(null)}
                                                />
                                            </div>
                                        )}
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function CreateGroupForm({ classId, allSubjects, onSuccess }: any) {
    const { data, setData, post, processing, errors } = useForm({
        class_id: classId,
        stream_key: '',
        group_name: '',
        description: '',
        min_select: 0,
        max_select: 1,
        subjects: [] as any[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.subject-management.group.store'), { onSuccess });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3 text-sm">
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="block font-medium mb-1">Stream Key</label>
                    <input
                        type="text"
                        value={data.stream_key}
                        onChange={(e) => setData('stream_key', e.target.value)}
                        placeholder="e.g., science"
                        className="input w-full"
                    />
                </div>
                <div>
                    <label className="block font-medium mb-1">Group Name</label>
                    <input
                        type="text"
                        value={data.group_name}
                        onChange={(e) => setData('group_name', e.target.value)}
                        placeholder="e.g., Science Group"
                        className="input w-full"
                    />
                </div>
            </div>

            <div>
                <label className="block font-medium mb-1">Description</label>
                <input
                    type="text"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    className="input w-full"
                />
            </div>

            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="block font-medium mb-1">Min Select</label>
                    <input type="number" min="0" value={data.min_select} onChange={(e) => setData('min_select', parseInt(e.target.value))} className="input w-full" />
                </div>
                <div>
                    <label className="block font-medium mb-1">Max Select</label>
                    <input type="number" min="1" value={data.max_select} onChange={(e) => setData('max_select', parseInt(e.target.value))} className="input w-full" />
                </div>
            </div>

            <div>
                <label className="block font-medium mb-2">Subjects</label>
                <div className="border rounded-lg max-h-40 overflow-y-auto p-2 space-y-1 bg-white">
                    {allSubjects.map((subject) => (
                        <label key={subject.id} className="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.subjects.some((s) => s.id === subject.id)}
                                onChange={(e) => {
                                    if (e.target.checked) {
                                        setData('subjects', [...data.subjects, { id: subject.id, subject_type: 'compulsory' }]);
                                    } else {
                                        setData('subjects', data.subjects.filter((s) => s.id !== subject.id));
                                    }
                                }}
                            />
                            <span>{subject.subject_name}</span>
                        </label>
                    ))}
                </div>
            </div>

            <button type="submit" disabled={processing} className="btn-primary w-full">
                {processing ? 'Creating...' : 'Create Group'}
            </button>
        </form>
    );
}

function EditGroupForm({ group, allSubjects, onSuccess }: any) {
    const { data, setData, put, processing } = useForm({
        subjects: group.subjects.map((s: any) => ({ id: s.id, subject_type: s.subject_type })),
        min_select: group.min_select,
        max_select: group.max_select,
        is_optional_group: group.is_optional_group,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.subject-management.group.update-subjects', group.id), { onSuccess });
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-3 text-sm">
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="block font-medium mb-1">Min Select</label>
                    <input type="number" min="0" value={data.min_select} onChange={(e) => setData('min_select', parseInt(e.target.value))} className="input w-full" />
                </div>
                <div>
                    <label className="block font-medium mb-1">Max Select</label>
                    <input type="number" min="1" value={data.max_select} onChange={(e) => setData('max_select', parseInt(e.target.value))} className="input w-full" />
                </div>
            </div>

            <div>
                <label className="block font-medium mb-2">Subjects</label>
                <div className="border rounded-lg max-h-40 overflow-y-auto p-2 space-y-1 bg-white">
                    {allSubjects.map((subject) => (
                        <label key={subject.id} className="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                            <input
                                type="checkbox"
                                checked={data.subjects.some((s) => s.id === subject.id)}
                                onChange={(e) => {
                                    if (e.target.checked) {
                                        setData('subjects', [...data.subjects, { id: subject.id, subject_type: 'compulsory' }]);
                                    } else {
                                        setData('subjects', data.subjects.filter((s) => s.id !== subject.id));
                                    }
                                }}
                            />
                            <span>{subject.subject_name}</span>
                        </label>
                    ))}
                </div>
            </div>

            <button type="submit" disabled={processing} className="btn-primary w-full">
                {processing ? 'Updating...' : 'Update Group'}
            </button>
        </form>
    );
}
