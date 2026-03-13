import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    SparklesIcon,
    ArrowLeftIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    DocumentArrowDownIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import { Timetable, TimeSlot, TimetableEntry, TimetableConflict } from '@/types/timetable';
import { PageProps } from '@/types';

interface Props extends PageProps {
    timetable: Timetable;
    entries: TimetableEntry[];
    conflicts: TimetableConflict[];
    timeSlots: TimeSlot[];
    days: string[];
}

const statusBadgeStyles: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    generating: 'bg-blue-100 text-blue-800',
    generated: 'bg-green-100 text-green-800',
    published: 'bg-indigo-100 text-indigo-800',
    archived: 'bg-slate-100 text-slate-800',
};

export default function Show({ timetable, entries, conflicts, timeSlots, days }: Props) {
    const [isGenerating, setIsGenerating] = useState(false);
    const [showConflicts, setShowConflicts] = useState(false);

    const handleGenerate = () => {
        if (confirm('Generate timetable using CSP algorithm? This may take a few moments.')) {
            setIsGenerating(true);
            router.post(route('principal.timetables.generate', timetable.id), {}, {
                onFinish: () => setIsGenerating(false),
            });
        }
    };

    const handlePublish = () => {
        if (timetable.conflict_count > 0) {
            alert('Cannot publish: There are unresolved conflicts. Please resolve them first.');
            return;
        }
        if (confirm('Publish this timetable? It will be visible to teachers.')) {
            router.post(route('principal.timetables.publish', timetable.id));
        }
    };

    const conflictTypesCount = conflicts.reduce(
        (acc, c) => {
            acc[c.conflict_type] = (acc[c.conflict_type] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>
    );

    return (
        <AppLayout>
            <Head title={timetable.name} />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    {/* Back Button */}
                    <button
                        onClick={() => router.visit(route('principal.timetables.index'))}
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                        Back to Timetables
                    </button>

                    {/* Header */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 className="text-3xl font-bold text-gray-900">{timetable.name}</h1>
                                <p className="text-gray-600 mt-1">
                                    {timetable.academic_year} • {timetable.term.charAt(0).toUpperCase() + timetable.term.slice(1)}
                                </p>
                            </div>
                            <span
                                className={`px-4 py-2 text-sm font-semibold rounded-lg ${
                                    statusBadgeStyles[timetable.status] || 'bg-gray-100 text-gray-800'
                                }`}
                            >
                                {timetable.status.charAt(0).toUpperCase() + timetable.status.slice(1)}
                            </span>
                        </div>
                    </div>

                    {/* Info Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <p className="text-sm text-gray-600 mb-1">Classes</p>
                            <p className="text-2xl font-bold text-gray-900">{timetable.total_classes}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <p className="text-sm text-gray-600 mb-1">Teachers</p>
                            <p className="text-2xl font-bold text-gray-900">{timetable.total_teachers}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <p className="text-sm text-gray-600 mb-1">Rooms</p>
                            <p className="text-2xl font-bold text-gray-900">{timetable.total_rooms}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                            <p className="text-sm text-gray-600 mb-1">Entries</p>
                            <p className="text-2xl font-bold text-gray-900">{entries.length}</p>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    {timetable.status === 'draft' && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                <div>
                                    <h3 className="font-semibold text-blue-900">Ready to generate?</h3>
                                    <p className="text-sm text-blue-700 mt-1">
                                        Configure time slots, rooms, and teacher availabilities before generating
                                    </p>
                                </div>
                                <button
                                    onClick={handleGenerate}
                                    disabled={isGenerating}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50"
                                >
                                    <SparklesIcon className="w-5 h-5" />
                                    {isGenerating ? 'Generating...' : 'Generate Timetable'}
                                </button>
                            </div>
                        </div>
                    )}

                    {timetable.status === 'generated' && (
                        <div className="flex flex-col sm:flex-row gap-4 mb-6">
                            <button
                                onClick={handlePublish}
                                disabled={timetable.conflict_count > 0}
                                className="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50"
                            >
                                <CheckCircleIcon className="w-5 h-5 inline mr-2" />
                                Publish Timetable
                            </button>
                            <Link
                                href={route('principal.timetables.export-pdf', timetable.id)}
                                className="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-center"
                            >
                                <DocumentArrowDownIcon className="w-5 h-5 inline mr-2" />
                                Export PDF
                            </Link>
                        </div>
                    )}

                    {/* Conflicts Section */}
                    {conflicts.length > 0 && (
                        <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                            <button
                                onClick={() => setShowConflicts(!showConflicts)}
                                className="flex items-center gap-2 text-red-900 font-semibold hover:text-red-700"
                            >
                                <ExclamationTriangleIcon className="w-5 h-5" />
                                {conflicts.length} Conflict{conflicts.length !== 1 ? 's' : ''} Found
                            </button>

                            {showConflicts && (
                                <div className="mt-4 space-y-3">
                                    {Object.entries(conflictTypesCount).map(([type, count]) => (
                                        <div key={type} className="text-sm text-red-800">
                                            <span className="font-medium">{type.replace(/_/g, ' ')}:</span> {count}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Views Section */}
                    {entries.length > 0 && (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">View Timetable</h2>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Link
                                    href={route('principal.timetables.by-class', { timetable: timetable.id, class: 1 })}
                                    className="p-4 border border-gray-200 rounded-lg hover:border-indigo-600 hover:bg-indigo-50 transition"
                                >
                                    <h3 className="font-semibold text-gray-900">By Class</h3>
                                    <p className="text-sm text-gray-600 mt-1">View schedule for each class</p>
                                </Link>
                                <Link
                                    href={route('principal.timetables.by-teacher', { timetable: timetable.id, teacher: 1 })}
                                    className="p-4 border border-gray-200 rounded-lg hover:border-indigo-600 hover:bg-indigo-50 transition"
                                >
                                    <h3 className="font-semibold text-gray-900">By Teacher</h3>
                                    <p className="text-sm text-gray-600 mt-1">View schedule for each teacher</p>
                                </Link>
                                <Link
                                    href={route('principal.timetables.by-room', { timetable: timetable.id, room: 1 })}
                                    className="p-4 border border-gray-200 rounded-lg hover:border-indigo-600 hover:bg-indigo-50 transition"
                                >
                                    <h3 className="font-semibold text-gray-900">By Room</h3>
                                    <p className="text-sm text-gray-600 mt-1">View schedule for each room</p>
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Details */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">Details</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Period from</p>
                                <p className="font-semibold text-gray-900">{timetable.start_date}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Period to</p>
                                <p className="font-semibold text-gray-900">{timetable.end_date}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Days per week</p>
                                <p className="font-semibold text-gray-900">{timetable.total_days}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Created by</p>
                                <p className="font-semibold text-gray-900">{timetable.creator?.name}</p>
                            </div>
                        </div>
                        {timetable.notes && (
                            <div className="mt-4 p-3 bg-gray-50 rounded border border-gray-200">
                                <p className="text-sm text-gray-600 mb-1">Notes</p>
                                <p className="text-gray-900">{timetable.notes}</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
