import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, CheckCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, Student } from '@/types';

interface StudentSelection {
    id: number;
    subject_id: number;
    subject: { id: number; subject_name: string };
    subjectGroup: { id: number; group_name: string };
    subject_type: 'compulsory' | 'optional';
    selected_at: string;
}

interface Props extends PageProps {
    student: Student;
    selections: Record<string, StudentSelection[]>;
}

export default function ShowSelections({ student, selections }: Props) {
    const totalSelections = Object.values(selections).flat().length;
    const compulsoryCount = Object.values(selections)
        .flat()
        .filter(s => s.subject_type === 'compulsory').length;
    const optionalCount = totalSelections - compulsoryCount;

    return (
        <AppLayout title={`${student.full_name} - Subject Selections`}>
            <Head title={`${student.full_name} - Subject Selections`} />

            <div className="max-w-4xl mx-auto">
                <div className="page-header flex-col gap-4 sm:flex-row mb-6">
                    <div className="flex items-center gap-3">
                        <Link href={route('principal.student-selections.index')} className="btn-ghost btn-icon">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="page-title">{student.full_name}</h1>
                            <p className="page-subtitle">
                                {student.class?.class}{student.class?.section ? `-${student.class.section}` : ''} · {student.group_stream}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3 mb-6">
                    <div className="card">
                        <div className="card-body text-center">
                            <p className="text-3xl font-bold text-indigo-600">{totalSelections}</p>
                            <p className="text-sm text-gray-600">Total Subjects Selected</p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body text-center">
                            <p className="text-3xl font-bold text-green-600">{compulsoryCount}</p>
                            <p className="text-sm text-gray-600">Compulsory Subjects</p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body text-center">
                            <p className="text-3xl font-bold text-blue-600">{optionalCount}</p>
                            <p className="text-sm text-gray-600">Optional Subjects</p>
                        </div>
                    </div>
                </div>

                {/* Selections by Group */}
                {Object.entries(selections).length > 0 ? (
                    <div className="space-y-4">
                        {Object.entries(selections).map(([groupId, groupSelections]) => (
                            <div key={groupId} className="card">
                                <div className="card-header bg-gray-50">
                                    <p className="card-title">
                                        {groupSelections[0]?.subjectGroup?.group_name}
                                    </p>
                                </div>
                                <div className="card-body">
                                    <div className="space-y-2">
                                        {groupSelections.map((selection) => (
                                            <div
                                                key={selection.id}
                                                className="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <CheckCircleIcon className="w-5 h-5 text-green-600" />
                                                    <div>
                                                        <p className="font-medium text-gray-900">
                                                            {selection.subject?.subject_name}
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            Selected {new Date(selection.selected_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                </div>
                                                <Badge color={selection.subject_type === 'compulsory' ? 'blue' : 'yellow'}>
                                                    {selection.subject_type}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="card">
                        <div className="card-body text-center text-gray-500">
                            <p>No subject selections found for this student</p>
                        </div>
                    </div>
                )}

                {/* Back Button */}
                <div className="mt-6">
                    <Link href={route('principal.student-selections.index')} className="btn-secondary">
                        Back to Selections
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
