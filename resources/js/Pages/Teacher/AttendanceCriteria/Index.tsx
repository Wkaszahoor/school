import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    groupedAssignments?: Array<{
        classId: string;
        className: string;
        classSection?: string;
        subjects: Array<{ id: string; name: string }>;
    }>;
    criteria?: Record<string, any>;
    academicYear?: string;
    message?: string;
}

declare const route: (name: string, params?: any) => string;

export default function Index({ groupedAssignments = [], criteria = {}, academicYear = new Date().getFullYear().toString(), message }: Props) {
    const [expandedClass, setExpandedClass] = useState<string | null>(null);
    const [editingKey, setEditingKey] = useState<string | null>(null);
    const [minAttendance, setMinAttendance] = useState(75);

    const handleSave = (classId: string, subjectId: string) => {
        router.post(route('teacher.attendance-criteria.store'), {
            class_id: classId,
            subject_id: subjectId,
            min_attendance_percent: minAttendance,
            academic_year: academicYear
        });
        setEditingKey(null);
    };

    const handleDelete = (classId: string, subjectId: string) => {
        if (confirm('Delete this criteria?')) {
            router.delete(route('teacher.attendance-criteria.destroy'), {
                data: {
                    class_id: classId,
                    subject_id: subjectId,
                    academic_year: academicYear
                }
            });
        }
    };

    const getCriteria = (classId: string, subjectId: string) => {
        return criteria[`${classId}_${subjectId}`];
    };

    return (
        <AppLayout title="Attendance Criteria">
            <Head title="Attendance Criteria" />
            <div className="page-header mb-6">
                <h1 className="page-title">Attendance Criteria</h1>
                <p className="page-subtitle">Set minimum attendance requirements for your subjects</p>
            </div>

            {message && (
                <div className="card bg-blue-50 border border-blue-200 mb-6">
                    <div className="card-body">
                        <p className="text-sm text-blue-900">{message}</p>
                    </div>
                </div>
            )}

            {!groupedAssignments || groupedAssignments.length === 0 ? (
                <div className="card bg-amber-50 border border-amber-200">
                    <div className="card-body">
                        <p className="text-sm text-amber-900">No subjects assigned to you yet.</p>
                    </div>
                </div>
            ) : (
                <div className="space-y-4">
                    {groupedAssignments.map((classGroup: any) => (
                        <div key={classGroup.classId} className="card">
                            <div
                                className="card-header cursor-pointer hover:bg-gray-50 transition"
                                onClick={() => setExpandedClass(expandedClass === classGroup.classId ? null : classGroup.classId)}
                            >
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="text-lg font-bold text-gray-900">
                                            Class {classGroup.className}
                                            {classGroup.classSection && ` - Section ${classGroup.classSection}`}
                                        </div>
                                        <div className="text-sm text-gray-600">
                                            {classGroup.subjects?.length || 0} subjects
                                        </div>
                                    </div>
                                    <span className="text-gray-400">▼</span>
                                </div>
                            </div>

                            {expandedClass === classGroup.classId && classGroup.subjects && (
                                <div className="card-body space-y-4">
                                    {classGroup.subjects.map((subject: any) => {
                                        const criterion = getCriteria(classGroup.classId, subject.id);
                                        const isEditing = editingKey === `${classGroup.classId}_${subject.id}`;

                                        return (
                                            <div key={subject.id} className="border border-gray-200 rounded-lg p-4 hover:border-blue-300">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1">
                                                        <h3 className="font-semibold text-gray-900">{subject.name}</h3>
                                                        {criterion ? (
                                                            <p className="text-sm text-gray-600 mt-2">Min Attendance: <strong>{criterion.min_attendance_percent}%</strong></p>
                                                        ) : (
                                                            <p className="text-sm text-gray-400 mt-2">No criteria set</p>
                                                        )}
                                                    </div>

                                                    {isEditing ? (
                                                        <form
                                                            onSubmit={(e) => {
                                                                e.preventDefault();
                                                                handleSave(classGroup.classId, subject.id);
                                                            }}
                                                            className="flex-1 ml-4 space-y-3"
                                                        >
                                                            <div>
                                                                <label className="form-label text-xs">Min Attendance %</label>
                                                                <input
                                                                    type="number"
                                                                    min="0"
                                                                    max="100"
                                                                    value={minAttendance}
                                                                    onChange={(e) => setMinAttendance(Number(e.target.value))}
                                                                    className="form-input text-sm"
                                                                />
                                                            </div>
                                                            <div className="flex gap-2 pt-2">
                                                                <button type="submit" className="px-3 py-2 bg-emerald-600 text-white rounded text-sm font-semibold hover:bg-emerald-700">
                                                                    Save
                                                                </button>
                                                                <button
                                                                    type="button"
                                                                    onClick={() => setEditingKey(null)}
                                                                    className="px-3 py-2 bg-gray-200 text-gray-700 rounded text-sm font-semibold hover:bg-gray-300"
                                                                >
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </form>
                                                    ) : (
                                                        <div className="flex gap-2">
                                                            <button
                                                                onClick={() => {
                                                                    setEditingKey(`${classGroup.classId}_${subject.id}`);
                                                                    setMinAttendance(criterion?.min_attendance_percent || 75);
                                                                }}
                                                                className="px-3 py-2 bg-blue-600 text-white rounded text-sm font-semibold hover:bg-blue-700"
                                                            >
                                                                <PlusIcon className="w-4 h-4 inline mr-1" />
                                                                {criterion ? 'Edit' : 'Add'}
                                                            </button>
                                                            {criterion && (
                                                                <button
                                                                    onClick={() => handleDelete(classGroup.classId, subject.id)}
                                                                    className="px-3 py-2 bg-red-600 text-white rounded text-sm font-semibold hover:bg-red-700"
                                                                >
                                                                    <TrashIcon className="w-4 h-4 inline mr-1" />
                                                                    Delete
                                                                </button>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            <div className="card bg-blue-50 border border-blue-200 mt-6">
                <div className="card-body space-y-2">
                    <h3 className="font-semibold text-blue-900">ℹ️ How This Works</h3>
                    <ul className="text-sm text-blue-800 space-y-1 ml-4">
                        <li>• Set minimum attendance % for each subject you teach</li>
                        <li>• Students below this threshold will need intervention</li>
                        <li>• You can edit or delete criteria anytime</li>
                        <li>• Academic Year: <strong>{academicYear}</strong></li>
                    </ul>
                </div>
            </div>
        </AppLayout>
    );
}
