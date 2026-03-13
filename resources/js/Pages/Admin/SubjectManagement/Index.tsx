import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { BookOpenIcon } from '@heroicons/react/24/outline';

interface Subject {
    id: number;
    subject_name: string;
    subject_code: string;
    is_active: boolean;
}

interface SubjectGroup {
    id: number;
    group_name: string;
    stream: string;
    subjects: Subject[];
}

interface SchoolClass {
    id: number;
    class: number;
    section?: string;
    subjectGroups: SubjectGroup[];
}

interface Props {
    classes: SchoolClass[];
    subjects: Subject[];
    totalGroups: number;
    totalSubjects: number;
}

export default function Index({ classes, subjects, totalGroups, totalSubjects }: Props) {
    const [activeTab, setActiveTab] = useState<'classes' | 'subjects'>('classes');

    return (
        <AppLayout>
            <Head title="Subject Management" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Subject Management</h1>
                    <p className="page-subtitle">Manage subject groups and assignments for classes 9-12</p>
                </div>
            </div>

            {/* Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="card">
                    <div className="card-body text-center">
                        <div className="text-3xl font-bold text-blue-600">{classes.length}</div>
                        <p className="text-gray-600 text-sm mt-1">Classes (9-12)</p>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body text-center">
                        <div className="text-3xl font-bold text-green-600">{totalGroups}</div>
                        <p className="text-gray-600 text-sm mt-1">Subject Groups</p>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body text-center">
                        <div className="text-3xl font-bold text-purple-600">{totalSubjects}</div>
                        <p className="text-gray-600 text-sm mt-1">Active Subjects</p>
                    </div>
                </div>
            </div>

            {/* Tabs */}
            <div className="card">
                <div className="card-header border-b">
                    <div className="flex gap-4">
                        <button
                            onClick={() => setActiveTab('classes')}
                            className={`px-4 py-2 font-medium transition ${activeTab === 'classes' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'}`}
                        >
                            Classes & Groups
                        </button>
                        <button
                            onClick={() => setActiveTab('subjects')}
                            className={`px-4 py-2 font-medium transition ${activeTab === 'subjects' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'}`}
                        >
                            Subjects
                        </button>
                    </div>
                </div>

                <div className="card-body">
                    {activeTab === 'classes' && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {classes.map((schoolClass) => (
                                <Link
                                    key={schoolClass.id}
                                    href={route('admin.subject-management.class', schoolClass.id)}
                                    className="card hover:shadow-lg transition"
                                >
                                    <div className="card-header">
                                        <div className="flex items-center gap-2">
                                            <BookOpenIcon className="h-5 w-5 text-blue-600" />
                                            <h3 className="font-semibold">
                                                Class {schoolClass.class}
                                                {schoolClass.section && <span className="text-gray-400"> - {schoolClass.section}</span>}
                                            </h3>
                                        </div>
                                        <p className="text-sm text-gray-600">
                                            {schoolClass.subjectGroups.length} group{schoolClass.subjectGroups.length !== 1 ? 's' : ''}
                                        </p>
                                    </div>
                                    <div className="card-body text-sm space-y-1">
                                        {schoolClass.subjectGroups.length > 0 ? (
                                            schoolClass.subjectGroups.map((group) => (
                                                <div key={group.id} className="text-gray-700">
                                                    <span className="font-medium">{group.group_name}</span>
                                                    <span className="text-gray-400 ml-2">({group.subjects.length} subjects)</span>
                                                </div>
                                            ))
                                        ) : (
                                            <p className="text-gray-400 italic">No groups configured</p>
                                        )}
                                    </div>
                                </Link>
                            ))}
                        </div>
                    )}

                    {activeTab === 'subjects' && (
                        <div>
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-semibold">All Subjects ({subjects.length})</h3>
                                <Link href={route('admin.subject-management.subjects.index')} className="btn-primary">
                                    Manage Subjects
                                </Link>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                {subjects.map((subject) => (
                                    <div key={subject.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                                        <div>
                                            <p className="font-medium text-gray-900">{subject.subject_name}</p>
                                            <p className="text-sm text-gray-500">{subject.subject_code}</p>
                                        </div>
                                        <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${subject.is_active ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-700'}`}>
                                            {subject.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
