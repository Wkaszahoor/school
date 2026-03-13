import React from 'react';
import { TimeSlot, TimetableEntry, ScheduleGrid } from '@/types/timetable';

interface TimetableGridProps {
    schedule: ScheduleGrid;
    timeSlots: TimeSlot[];
    days: string[];
    title: string;
    onEntryClick?: (entry: TimetableEntry) => void;
}

export default function TimetableGrid({
    schedule,
    timeSlots,
    days,
    title,
    onEntryClick,
}: TimetableGridProps) {
    return (
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div className="p-6 border-b border-gray-200">
                <h2 className="text-2xl font-bold text-gray-900">{title}</h2>
            </div>

            <div className="overflow-x-auto">
                <table className="w-full text-sm">
                    {/* Header */}
                    <thead className="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th className="px-4 py-3 text-left font-semibold text-gray-700 bg-gray-100 sticky left-0 z-10 min-w-[120px]">
                                Time
                            </th>
                            {days.map((day) => (
                                <th
                                    key={day}
                                    className="px-4 py-3 text-center font-semibold text-gray-700 whitespace-nowrap"
                                >
                                    {day}
                                </th>
                            ))}
                        </tr>
                    </thead>

                    {/* Body */}
                    <tbody className="divide-y divide-gray-200">
                        {timeSlots.map((slot) => (
                            <tr key={slot.id} className="hover:bg-gray-50 transition">
                                {/* Time Slot */}
                                <td className="px-4 py-3 font-semibold text-gray-900 bg-gray-50 sticky left-0 z-10 min-w-[120px]">
                                    <div className="text-xs text-gray-600">{slot.period_number}</div>
                                    <div className="text-sm">
                                        {slot.start_time} - {slot.end_time}
                                    </div>
                                </td>

                                {/* Day Cells */}
                                {days.map((day) => {
                                    const entry = schedule[day]?.[slot.id];
                                    const isBusySlot = slot.slot_type !== 'regular';

                                    return (
                                        <td
                                            key={`${day}-${slot.id}`}
                                            className={`px-4 py-3 border-l border-gray-200 ${
                                                isBusySlot ? 'bg-yellow-50' : ''
                                            }`}
                                        >
                                            {entry ? (
                                                <button
                                                    onClick={() => onEntryClick?.(entry)}
                                                    className="w-full p-2 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 transition text-left text-xs cursor-pointer group"
                                                >
                                                    <div className="font-semibold text-indigo-900">
                                                        {entry.schoolClass?.name}
                                                    </div>
                                                    <div className="text-indigo-700">{entry.subject?.name}</div>
                                                    <div className="text-indigo-600 mt-1">{entry.teacher?.name}</div>
                                                    <div className="text-indigo-600 text-xs mt-1">{entry.room?.room_name}</div>
                                                    {entry.is_locked && (
                                                        <div className="mt-1 text-xs bg-amber-100 text-amber-800 px-1 py-0.5 rounded w-fit">
                                                            🔒 Locked
                                                        </div>
                                                    )}
                                                </button>
                                            ) : isBusySlot ? (
                                                <div className="text-center text-gray-500 text-xs font-semibold">
                                                    {slot.slot_type.toUpperCase()}
                                                </div>
                                            ) : (
                                                <div className="text-center text-gray-300 text-xs">—</div>
                                            )}
                                        </td>
                                    );
                                })}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Legend */}
            <div className="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <p className="text-xs font-medium text-gray-600 mb-2">Legend:</p>
                <div className="flex flex-wrap gap-4 text-xs">
                    <div className="flex items-center gap-2">
                        <div className="w-4 h-4 bg-indigo-50 border border-indigo-200 rounded" />
                        <span>Class Assignment</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <div className="w-4 h-4 bg-yellow-50 rounded" />
                        <span>Break/Lunch/Assembly</span>
                    </div>
                    <div className="flex items-center gap-2">
                        <span>🔒 Locked (Cannot be rescheduled)</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
