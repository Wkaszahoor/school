import React, { useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowDownTrayIcon, PresentationChartBarIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Statistics {
    total_students: number;
    completed_selections: number;
    pending_selections: number;
    completion_percentage: number;
}

interface SubjectSummary {
    subject_name: string;
    count: number;
    percentage: number;
}

interface GroupSummary {
    group_name: string;
    students_count: number;
    avg_subjects_selected: number;
    total_selections: number;
}

interface Props extends PageProps {
    statistics: Statistics;
    subjectSummary: SubjectSummary[];
    groupSummary: GroupSummary[];
    classes: Array<{ id: number; class: string; section: string | null }>;
    students: Array<{ id: number; full_name: string; class_id: number }>;
    classId?: number;
    stream?: string;
}

export default function SelectionReports({
    statistics,
    subjectSummary,
    groupSummary,
    classes,
    classId,
    stream,
}: Props) {
    const selectedClass = classes.find(c => c.id === classId);

    const exportData = () => {
        const data = {
            statistics,
            subjectSummary,
            groupSummary,
            filters: { classId, stream },
            exportedAt: new Date().toISOString(),
        };

        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(JSON.stringify(data, null, 2)));
        element.setAttribute('download', `selection-report-${new Date().toISOString().split('T')[0]}.json`);
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
    };

    return (
        <AppLayout title="Selection Reports">
            <Head title="Selection Reports" />

            <div className="max-w-7xl mx-auto">
                <div className="page-header mb-6">
                    <div>
                        <h1 className="page-title">Subject Selection Reports</h1>
                        <p className="page-subtitle">Analytics and statistics on student subject selections</p>
                    </div>
                    <button onClick={exportData} className="btn-secondary flex gap-2">
                        <ArrowDownTrayIcon className="w-4 h-4" />
                        Export Report
                    </button>
                </div>

                {/* Statistics Cards */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                    <div className="card">
                        <div className="card-body">
                            <p className="text-sm text-gray-600">Total Students</p>
                            <p className="text-3xl font-bold text-indigo-600">{statistics.total_students}</p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body">
                            <p className="text-sm text-gray-600">Completed</p>
                            <p className="text-3xl font-bold text-green-600">{statistics.completed_selections}</p>
                            <p className="text-xs text-gray-500 mt-2">{statistics.completion_percentage}% complete</p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body">
                            <p className="text-sm text-gray-600">Pending</p>
                            <p className="text-3xl font-bold text-orange-600">{statistics.pending_selections}</p>
                        </div>
                    </div>
                    <div className="card">
                        <div className="card-body">
                            <p className="text-sm text-gray-600">Completion Rate</p>
                            <div className="mt-2">
                                <div className="w-full bg-gray-200 rounded-full h-2">
                                    <div
                                        className="bg-green-600 h-2 rounded-full"
                                        style={{ width: `${statistics.completion_percentage}%` }}
                                    />
                                </div>
                                <p className="text-lg font-bold text-green-600 mt-2">
                                    {statistics.completion_percentage}%
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Subject Summary */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title flex items-center gap-2">
                                <PresentationChartBarIcon className="w-5 h-5" />
                                Most Selected Subjects
                            </p>
                        </div>
                        <div className="card-body">
                            {subjectSummary.length > 0 ? (
                                <div className="space-y-4">
                                    {subjectSummary.slice(0, 10).map((subject, idx) => (
                                        <div key={idx}>
                                            <div className="flex items-center justify-between mb-1">
                                                <p className="text-sm font-medium text-gray-900">
                                                    {subject.subject_name}
                                                </p>
                                                <p className="text-sm text-gray-600">{subject.count} students</p>
                                            </div>
                                            <div className="w-full bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-indigo-600 h-2 rounded-full"
                                                    style={{ width: `${subject.percentage}%` }}
                                                />
                                            </div>
                                            <p className="text-xs text-gray-500 mt-1">{subject.percentage}%</p>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">No data available</p>
                            )}
                        </div>
                    </div>

                    {/* Group Summary */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title flex items-center gap-2">
                                <PresentationChartBarIcon className="w-5 h-5" />
                                Group Selection Summary
                            </p>
                        </div>
                        <div className="card-body">
                            {groupSummary.length > 0 ? (
                                <div className="space-y-4">
                                    {groupSummary.map((group, idx) => (
                                        <div
                                            key={idx}
                                            className="p-3 border border-gray-200 rounded-lg"
                                        >
                                            <div className="flex items-center justify-between mb-2">
                                                <p className="font-medium text-gray-900">{group.group_name}</p>
                                                <span className="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-1 rounded">
                                                    {group.students_count} students
                                                </span>
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                <p>Avg. selections: <strong>{group.avg_subjects_selected}</strong></p>
                                                <p>Total selections: <strong>{group.total_selections}</strong></p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">No data available</p>
                            )}
                        </div>
                    </div>
                </div>

                {/* Navigation */}
                <div className="flex gap-3">
                    <Link href={route('principal.student-selections.index')} className="btn-secondary">
                        Back to Selections
                    </Link>
                </div>
            </div>
        </AppLayout>
    );
}
