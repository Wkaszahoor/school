import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, PencilIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import TimetableGrid from '@/Components/Timetable/TimetableGrid';
import { Timetable, TimeSlot, TimetableEntry, ScheduleGrid } from '@/types/timetable';
import { User } from '@/types';
import { PageProps } from '@/types';

interface Props extends PageProps {
    timetable: Timetable;
    teacher: User;
    schedule: ScheduleGrid;
    timeSlots: TimeSlot[];
    days: string[];
}

export default function ByTeacherView({ timetable, teacher, schedule, timeSlots, days }: Props) {
    const [selectedEntry, setSelectedEntry] = useState<TimetableEntry | null>(null);

    const handleEntryClick = (entry: TimetableEntry) => {
        setSelectedEntry(entry);
    };

    const closeModal = () => {
        setSelectedEntry(null);
    };

    return (
        <AppLayout>
            <Head title={`${teacher.name} - Timetable`} />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    {/* Back Button */}
                    <button
                        onClick={() => router.visit(route('principal.timetables.show', timetable.id))}
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                        Back to Timetable
                    </button>

                    {/* Header */}
                    <div className="mb-6">
                        <h1 className="text-3xl font-bold text-gray-900">{timetable.name}</h1>
                        <p className="text-lg text-gray-600 mt-1">Teacher: {teacher.name}</p>
                        <p className="text-sm text-gray-500 mt-0.5">{teacher.email}</p>
                    </div>

                    {/* Grid */}
                    <TimetableGrid
                        schedule={schedule}
                        timeSlots={timeSlots}
                        days={days}
                        title={`${teacher.name} Schedule`}
                        onEntryClick={handleEntryClick}
                    />

                    {/* Entry Details Modal */}
                    {selectedEntry && (
                        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
                            <div className="bg-white rounded-lg shadow-lg max-w-md w-full p-6">
                                <div className="flex items-start justify-between mb-4">
                                    <div>
                                        <h2 className="text-xl font-bold text-gray-900">
                                            {selectedEntry.schoolClass?.name} - {selectedEntry.subject?.name}
                                        </h2>
                                        <p className="text-sm text-gray-600 mt-1">
                                            {selectedEntry.day_of_week} • {selectedEntry.timeSlot?.name}
                                        </p>
                                    </div>
                                    <button
                                        onClick={closeModal}
                                        className="text-gray-400 hover:text-gray-600"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <div className="space-y-3 border-t border-b border-gray-200 py-4 mb-4">
                                    <div>
                                        <p className="text-xs text-gray-600">Class</p>
                                        <p className="font-semibold text-gray-900">{selectedEntry.schoolClass?.name}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-600">Room</p>
                                        <p className="font-semibold text-gray-900">{selectedEntry.room?.room_name}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs text-gray-600">Time</p>
                                        <p className="font-semibold text-gray-900">
                                            {selectedEntry.timeSlot?.start_time} - {selectedEntry.timeSlot?.end_time}
                                        </p>
                                    </div>
                                </div>

                                {selectedEntry.is_locked && (
                                    <div className="mb-4 p-3 bg-amber-50 border border-amber-200 rounded">
                                        <p className="text-sm text-amber-800">
                                            <strong>🔒 Locked</strong> - Cannot be manually rescheduled
                                        </p>
                                    </div>
                                )}

                                <button
                                    onClick={closeModal}
                                    className="w-full px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300 transition"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
