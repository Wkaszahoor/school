import React, { useState } from 'react';
import {
    ExclamationTriangleIcon,
    CheckCircleIcon,
    XCircleIcon,
} from '@heroicons/react/24/outline';
import { TimetableConflict } from '@/types/timetable';

interface ConflictReportProps {
    conflicts: TimetableConflict[];
    onResolve?: (conflictId: number) => void;
}

const conflictTypeDescriptions: Record<string, string> = {
    teacher_double_booking: 'Teacher is scheduled for two classes at the same time',
    room_double_booking: 'Room is booked for two different classes simultaneously',
    teacher_availability: 'Teacher is not available during this period',
    room_unavailable: 'Room is not available at this time',
    consecutive_classes: 'Teacher has too many consecutive classes',
    free_period_violation: 'Teacher does not have required free periods',
    unbalanced_workload: 'Workload is unbalanced across days',
};

const severityColors: Record<string, string> = {
    hard: 'bg-red-50 border-red-200 text-red-900',
    soft: 'bg-yellow-50 border-yellow-200 text-yellow-900',
};

const severityIcons = {
    hard: XCircleIcon,
    soft: ExclamationTriangleIcon,
};

export default function ConflictReport({ conflicts, onResolve }: ConflictReportProps) {
    const [expandedConflict, setExpandedConflict] = useState<number | null>(null);

    if (conflicts.length === 0) {
        return (
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                <div className="flex items-center gap-2">
                    <CheckCircleIcon className="w-6 h-6 text-green-600" />
                    <div>
                        <h3 className="font-semibold text-green-900">No Conflicts</h3>
                        <p className="text-sm text-green-700">Timetable is valid and ready to publish</p>
                    </div>
                </div>
            </div>
        );
    }

    const hardConflicts = conflicts.filter((c) => c.severity === 'hard');
    const softConflicts = conflicts.filter((c) => c.severity === 'soft');

    const groupedConflicts = conflicts.reduce(
        (acc, conflict) => {
            acc[conflict.conflict_type] = (acc[conflict.conflict_type] || 0) + 1;
            return acc;
        },
        {} as Record<string, number>
    );

    return (
        <div className="space-y-4">
            {/* Summary */}
            <div className="grid grid-cols-2 gap-4">
                {hardConflicts.length > 0 && (
                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p className="text-sm text-red-600 font-semibold mb-1">Hard Conflicts</p>
                        <p className="text-2xl font-bold text-red-900">{hardConflicts.length}</p>
                        <p className="text-xs text-red-700 mt-1">Must be resolved before publishing</p>
                    </div>
                )}
                {softConflicts.length > 0 && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p className="text-sm text-yellow-600 font-semibold mb-1">Soft Conflicts</p>
                        <p className="text-2xl font-bold text-yellow-900">{softConflicts.length}</p>
                        <p className="text-xs text-yellow-700 mt-1">Can be accepted or resolved</p>
                    </div>
                )}
            </div>

            {/* Conflict Type Summary */}
            <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <h3 className="font-semibold text-gray-900 mb-3">Breakdown by Type</h3>
                <div className="space-y-2">
                    {Object.entries(groupedConflicts).map(([type, count]) => (
                        <div key={type} className="flex items-center justify-between text-sm">
                            <span className="text-gray-700">{type.replace(/_/g, ' ')}</span>
                            <span className="font-semibold text-gray-900">{count}</span>
                        </div>
                    ))}
                </div>
            </div>

            {/* Detailed List */}
            <div className="space-y-2">
                <h3 className="font-semibold text-gray-900">Details</h3>
                {conflicts.map((conflict) => {
                    const IconComponent = severityIcons[conflict.severity];
                    const isExpanded = expandedConflict === conflict.id;

                    return (
                        <div
                            key={conflict.id}
                            className={`border rounded-lg overflow-hidden ${severityColors[conflict.severity]}`}
                        >
                            <button
                                onClick={() =>
                                    setExpandedConflict(isExpanded ? null : conflict.id)
                                }
                                className="w-full px-4 py-3 flex items-start gap-3 hover:opacity-80 transition"
                            >
                                <IconComponent className="w-5 h-5 flex-shrink-0 mt-0.5" />
                                <div className="text-left flex-1">
                                    <p className="font-semibold capitalize">
                                        {conflict.conflict_type.replace(/_/g, ' ')}
                                    </p>
                                    <p className="text-sm opacity-90 mt-1">
                                        {conflictTypeDescriptions[conflict.conflict_type] || conflict.description}
                                    </p>
                                </div>
                                {conflict.is_resolved && (
                                    <span className="text-xs font-semibold px-2 py-1 bg-green-100 text-green-800 rounded">
                                        Resolved
                                    </span>
                                )}
                            </button>

                            {isExpanded && (
                                <div className="px-4 py-3 border-t opacity-90 text-sm space-y-2">
                                    {conflict.entry && (
                                        <>
                                            <div>
                                                <p className="font-semibold">Class:</p>
                                                <p>{conflict.entry.schoolClass?.name}</p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">Subject:</p>
                                                <p>{conflict.entry.subject?.name}</p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">Teacher:</p>
                                                <p>{conflict.entry.teacher?.name}</p>
                                            </div>
                                            <div>
                                                <p className="font-semibold">Time:</p>
                                                <p>
                                                    {conflict.entry.day_of_week} •{' '}
                                                    {conflict.entry.timeSlot?.start_time} -
                                                    {conflict.entry.timeSlot?.end_time}
                                                </p>
                                            </div>
                                        </>
                                    )}
                                    {conflict.resolution_notes && (
                                        <div>
                                            <p className="font-semibold">Resolution Notes:</p>
                                            <p>{conflict.resolution_notes}</p>
                                        </div>
                                    )}
                                    {!conflict.is_resolved && onResolve && (
                                        <button
                                            onClick={() => onResolve(conflict.id)}
                                            className="mt-2 px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                                        >
                                            Mark as Resolved
                                        </button>
                                    )}
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
