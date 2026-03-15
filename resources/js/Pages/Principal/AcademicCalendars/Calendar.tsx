import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Modal from '@/Components/Modal';
import axios from 'axios';

interface AcademicCalendar {
    id: number;
    title: string;
    start_date: string;
    end_date: string;
    type: string;
    color: string;
    academic_year: string;
    is_all_day: boolean;
    description?: string;
    location?: string;
}

interface Props {
    events: AcademicCalendar[];
    year: number;
    academicYear: string | null;
    academicYears: string[];
}

const COLOR_SWATCHES = ['#EF4444', '#F97316', '#EAB308', '#22C55E', '#3B82F6', '#8B5CF6', '#EC4899'];

const TYPE_COLORS: Record<string, string> = {
    holiday: '#EF4444',
    exam: '#3B82F6',
    term: '#10B981',
    event: '#A855F7',
    semester: '#6366F1',
    break: '#F59E0B',
    other: '#6B7280',
};

const typeLabels: Record<string, string> = {
    holiday: 'Holiday',
    exam: 'Exam',
    term: 'Term',
    event: 'Event',
    semester: 'Semester',
    break: 'Break',
    other: 'Other',
};

const MONTHS = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const WEEKDAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const WEEKDAYS_FULL = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

type ViewType = 'month' | 'week' | 'year';

// ============ MONTH VIEW COMPONENTS ============

interface MiniMonthProps {
    month: number;
    year: number;
    events: AcademicCalendar[];
    isSelected: boolean;
    onSelect: () => void;
}

const MiniMonth: React.FC<MiniMonthProps> = ({ month, year, events, isSelected, onSelect }) => {
    const getDaysInMonth = (m: number, y: number) => new Date(y, m + 1, 0).getDate();
    const getFirstDayOfMonth = (m: number, y: number) => new Date(y, m, 1).getDay();

    const getEventsForDate = (date: number) => {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
        return events.filter((e) => {
            const startDate = e.start_date.substring(0, 10);
            const endDate = e.end_date.substring(0, 10);
            return dateStr >= startDate && dateStr <= endDate;
        });
    };

    const monthDays = getDaysInMonth(month, year);
    const firstDay = getFirstDayOfMonth(month, year);
    const days = Array(firstDay).fill(null);
    for (let i = 1; i <= monthDays; i++) {
        days.push(i);
    }

    return (
        <div
            onClick={onSelect}
            className={`p-3 rounded-lg border-2 cursor-pointer transition ${
                isSelected ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'
            }`}
        >
            <h4 className="text-xs font-bold text-gray-900 text-center mb-2">
                {MONTHS[month]} {year}
            </h4>
            <div className="grid grid-cols-7 gap-0.5">
                {WEEKDAYS.map((day) => (
                    <div key={day} className="text-center text-xs font-semibold text-gray-600">
                        {day[0]}
                    </div>
                ))}
                {days.map((day, idx) => (
                    <div
                        key={idx}
                        className={`text-xs text-center py-0.5 rounded ${
                            !day
                                ? 'text-gray-300'
                                : getEventsForDate(day).length > 0
                                ? 'bg-indigo-200 text-indigo-900 font-semibold'
                                : 'text-gray-700'
                        }`}
                    >
                        {day}
                    </div>
                ))}
            </div>
        </div>
    );
};

interface MainMonthProps {
    month: number;
    year: number;
    events: AcademicCalendar[];
    draggingEvent: AcademicCalendar | null;
    dragOverDay: string | null;
    onDragStart: (e: React.DragEvent, event: AcademicCalendar) => void;
    onDragOver: (e: React.DragEvent) => void;
    onDragEnter: (dateStr: string) => void;
    onDragLeave: () => void;
    onDrop: (e: React.DragEvent, date: number, month: number, year: number) => void;
    onDayClick: (dateStr: string) => void;
    onEventClick: (event: AcademicCalendar) => void;
}

const MainMonth: React.FC<MainMonthProps> = ({
    month,
    year,
    events,
    draggingEvent,
    dragOverDay,
    onDragStart,
    onDragOver,
    onDragEnter,
    onDragLeave,
    onDrop,
    onDayClick,
    onEventClick,
}) => {
    const getDaysInMonth = (m: number, y: number) => new Date(y, m + 1, 0).getDate();
    const getFirstDayOfMonth = (m: number, y: number) => new Date(y, m, 1).getDay();

    const getDateString = (date: number) => {
        return `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
    };

    const getEventsForDate = (date: number) => {
        const dateStr = getDateString(date);
        return events.filter((e) => {
            const startDate = e.start_date.substring(0, 10);
            const endDate = e.end_date.substring(0, 10);
            return dateStr >= startDate && dateStr <= endDate;
        });
    };

    const monthDays = getDaysInMonth(month, year);
    const firstDay = getFirstDayOfMonth(month, year);
    const days = Array(firstDay).fill(null);
    for (let i = 1; i <= monthDays; i++) {
        days.push(i);
    }

    return (
        <div className="bg-white rounded-lg shadow overflow-hidden">
            <div className="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                <h2 className="text-2xl font-bold text-white text-center">
                    {MONTHS[month]} {year}
                </h2>
            </div>

            <div className="grid grid-cols-7 gap-0 bg-gray-100 border-b">
                {WEEKDAYS_FULL.map((day) => (
                    <div key={day} className="p-3 text-center font-semibold text-gray-700 text-sm">
                        {day}
                    </div>
                ))}
            </div>

            <div className="grid grid-cols-7 gap-0">
                {days.map((day, index) => {
                    const dateStr = day ? getDateString(day) : null;
                    const dayEvents = day ? getEventsForDate(day) : [];
                    const isDragOver = dateStr === dragOverDay;

                    return (
                        <div
                            key={index}
                            onDragOver={onDragOver}
                            onDragEnter={() => day && dateStr && onDragEnter(dateStr)}
                            onDragLeave={onDragLeave}
                            onDrop={(e) => day && onDrop(e, day, month, year)}
                            onClick={() => day && !draggingEvent && dateStr && onDayClick(dateStr)}
                            className={`border-r border-b p-4 min-h-[120px] transition ${
                                !day
                                    ? 'bg-gray-50'
                                    : isDragOver
                                    ? 'bg-indigo-50 ring-2 ring-indigo-400'
                                    : 'hover:bg-gray-50 cursor-pointer'
                            }`}
                        >
                            {day && (
                                <>
                                    <div className="font-bold text-lg text-gray-900 mb-2">{day}</div>
                                    <div className="space-y-1">
                                        {dayEvents.map((event) => (
                                            <div
                                                key={event.id}
                                                draggable
                                                onDragStart={(e) => onDragStart(e, event)}
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    onEventClick(event);
                                                }}
                                                className="px-2 py-1 rounded text-white text-xs truncate cursor-move hover:opacity-75 transition font-semibold"
                                                style={{
                                                    backgroundColor: event.color || TYPE_COLORS[event.type] || '#6B7280',
                                                }}
                                                title={event.title}
                                            >
                                                {event.title}
                                            </div>
                                        ))}
                                    </div>
                                </>
                            )}
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

// ============ WEEK VIEW COMPONENT ============

interface WeekViewProps {
    events: AcademicCalendar[];
    currentDate: Date;
    onPrevWeek: () => void;
    onNextWeek: () => void;
    onEventClick: (event: AcademicCalendar) => void;
}

const WeekView: React.FC<WeekViewProps> = ({ events, currentDate, onPrevWeek, onNextWeek, onEventClick }) => {
    const weekStart = new Date(currentDate);
    weekStart.setDate(weekStart.getDate() - weekStart.getDay());

    const weekDays = Array(7)
        .fill(0)
        .map((_, i) => {
            const date = new Date(weekStart);
            date.setDate(date.getDate() + i);
            return date;
        });

    const getEventsForDate = (date: Date) => {
        const dateStr = date.toISOString().substring(0, 10);
        return events.filter((e) => {
            const startDate = e.start_date.substring(0, 10);
            const endDate = e.end_date.substring(0, 10);
            return dateStr >= startDate && dateStr <= endDate;
        });
    };

    const hours = Array(24)
        .fill(0)
        .map((_, i) => i);

    return (
        <div className="space-y-4">
            <div className="flex justify-between items-center">
                <button onClick={onPrevWeek} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <ChevronLeftIcon className="w-5 h-5" />
                </button>
                <span className="text-lg font-semibold">
                    {weekDays[0].toLocaleDateString('en-GB')} - {weekDays[6].toLocaleDateString('en-GB')}
                </span>
                <button onClick={onNextWeek} className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    <ChevronRightIcon className="w-5 h-5" />
                </button>
            </div>

            <div className="bg-white rounded-lg shadow overflow-hidden">
                <div className="grid grid-cols-8 gap-0 border-b">
                    <div className="p-3 font-semibold text-gray-700 text-sm">Time</div>
                    {weekDays.map((date, idx) => (
                        <div key={idx} className="p-3 font-semibold text-gray-700 text-sm text-center border-l">
                            <div>{WEEKDAYS_FULL[date.getDay()]}</div>
                            <div className="text-xs text-gray-500">{date.getDate()}</div>
                        </div>
                    ))}
                </div>

                <div className="divide-y">
                    {hours.map((hour) => (
                        <div key={hour} className="grid grid-cols-8 gap-0 min-h-[60px] border-b divide-x">
                            <div className="p-2 text-xs font-semibold text-gray-700 bg-gray-50">
                                {String(hour).padStart(2, '0')}:00
                            </div>
                            {weekDays.map((date, idx) => {
                                const dayEvents = getEventsForDate(date);
                                return (
                                    <div key={idx} className="p-2 text-xs space-y-1">
                                        {dayEvents.map((event) => (
                                            <div
                                                key={event.id}
                                                onClick={() => onEventClick(event)}
                                                className="p-1 rounded text-white text-xs cursor-pointer hover:opacity-75 transition font-semibold truncate"
                                                style={{
                                                    backgroundColor: event.color || TYPE_COLORS[event.type] || '#6B7280',
                                                }}
                                                title={event.title}
                                            >
                                                {event.title}
                                            </div>
                                        ))}
                                    </div>
                                );
                            })}
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
};

// ============ YEAR VIEW COMPONENT ============

interface YearCardProps {
    month: number;
    year: number;
    events: AcademicCalendar[];
    onSelect: () => void;
}

const YearCard: React.FC<YearCardProps> = ({ month, year, events, onSelect }) => {
    const getDaysInMonth = (m: number, y: number) => new Date(y, m + 1, 0).getDate();
    const getFirstDayOfMonth = (m: number, y: number) => new Date(y, m, 1).getDay();

    const getEventsForDate = (date: number) => {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
        return events.filter((e) => {
            const startDate = e.start_date.substring(0, 10);
            const endDate = e.end_date.substring(0, 10);
            return dateStr >= startDate && dateStr <= endDate;
        });
    };

    const monthDays = getDaysInMonth(month, year);
    const firstDay = getFirstDayOfMonth(month, year);
    const days = Array(firstDay).fill(null);
    for (let i = 1; i <= monthDays; i++) {
        days.push(i);
    }

    return (
        <div onClick={onSelect} className="bg-white rounded-lg shadow-md hover:shadow-lg transition cursor-pointer p-3">
            <h4 className="text-sm font-bold text-gray-900 text-center mb-2">
                {MONTHS[month]} {year}
            </h4>
            <div className="grid grid-cols-7 gap-0.5">
                {WEEKDAYS.map((day) => (
                    <div key={day} className="text-center text-xs font-semibold text-gray-600">
                        {day[0]}
                    </div>
                ))}
                {days.map((day, idx) => (
                    <div
                        key={idx}
                        className={`text-xs text-center py-1 rounded font-medium ${
                            !day
                                ? 'text-gray-200'
                                : getEventsForDate(day).length > 0
                                ? 'bg-indigo-600 text-white'
                                : 'text-gray-700'
                        }`}
                    >
                        {day}
                    </div>
                ))}
            </div>
        </div>
    );
};

// ============ MAIN COMPONENT ============

export default function AcademicCalendarView({ events: initialEvents, year, academicYear, academicYears }: Props) {
    const [viewType, setViewType] = useState<ViewType>('month');
    const [currentMonth, setCurrentMonth] = useState(new Date().getMonth());
    const [currentYear, setCurrentYear] = useState(year);
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth());
    const [selectedYear, setSelectedYear] = useState(year);
    const [weekDate, setWeekDate] = useState(new Date());
    const [localEvents, setLocalEvents] = useState<AcademicCalendar[]>(initialEvents);
    const [draggingEvent, setDraggingEvent] = useState<AcademicCalendar | null>(null);
    const [dragOverDay, setDragOverDay] = useState<string | null>(null);
    const [addingToDate, setAddingToDate] = useState<string | null>(null);
    const [selectedEvent, setSelectedEvent] = useState<AcademicCalendar | null>(null);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [quickForm, setQuickForm] = useState({
        title: '',
        type: 'event',
        color: '#A855F7',
        is_all_day: true,
    });

    const calculateDateDifference = (startDate: string, endDate: string) => {
        const start = new Date(startDate);
        const end = new Date(endDate);
        return Math.floor((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
    };

    const handleDragStart = (e: React.DragEvent, event: AcademicCalendar) => {
        setDraggingEvent(event);
        e.dataTransfer.effectAllowed = 'move';
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDragEnter = (dateStr: string) => {
        setDragOverDay(dateStr);
    };

    const handleDragLeave = () => {
        setDragOverDay(null);
    };

    const handleDrop = (e: React.DragEvent, date: number, month: number, year: number) => {
        e.preventDefault();
        if (!draggingEvent) return;

        const dropDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
        const duration = calculateDateDifference(draggingEvent.start_date, draggingEvent.end_date);
        const newStart = dropDate;
        const newEnd = new Date(new Date(dropDate).getTime() + duration * 24 * 60 * 60 * 1000)
            .toISOString()
            .substring(0, 10);

        setLocalEvents((prev) =>
            prev.map((ev) =>
                ev.id === draggingEvent.id ? { ...ev, start_date: newStart, end_date: newEnd } : ev
            )
        );

        setDraggingEvent(null);
        setDragOverDay(null);

        axios.patch(route('principal.academic-calendars.reschedule', draggingEvent.id), {
            start_date: newStart,
            end_date: newEnd,
        }).catch(() => {
            setLocalEvents(localEvents);
        });
    };

    const handleQuickAdd = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!addingToDate || !quickForm.title.trim()) return;

        setError(null);
        setIsSubmitting(true);
        try {
            const academicYearValue = academicYear || academicYears[0] || '2025-2026';
            console.log('Submitting event:', {
                title: quickForm.title,
                start_date: addingToDate,
                end_date: addingToDate,
                type: quickForm.type,
                color: quickForm.color,
                academic_year: academicYearValue,
                is_all_day: quickForm.is_all_day,
            });

            const response = await axios.post(route('principal.academic-calendars.quick-store'), {
                title: quickForm.title,
                start_date: addingToDate,
                end_date: addingToDate,
                type: quickForm.type,
                color: quickForm.color,
                academic_year: academicYearValue,
                is_all_day: quickForm.is_all_day,
            });

            console.log('Event created:', response.data);
            setLocalEvents((prev) => [...prev, response.data]);
            setAddingToDate(null);
            setQuickForm({ title: '', type: 'event', color: '#A855F7', is_all_day: true });
        } catch (err: any) {
            console.error('Error creating event:', err);
            const errorMessage = err.response?.data?.message || err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] || 'Failed to create event';
            setError(errorMessage);
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDeleteEvent = async (eventId: number) => {
        if (!confirm('Are you sure you want to delete this event?')) return;

        try {
            await axios.delete(route('principal.academic-calendars.quick-destroy', eventId));
            setLocalEvents((prev) => prev.filter((e) => e.id !== eventId));
            setSelectedEvent(null);
        } catch (error) {
            console.error('Error deleting event:', error);
        }
    };

    return (
        <AppLayout title="Academic Calendar View">
            <Head title="Academic Calendar" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Academic Calendar</h1>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-4 mb-6">
                        <div className="flex items-center justify-between">
                            <div className="w-64">
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year
                                </label>
                                <select
                                    value={academicYear || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('principal.academic-calendars.calendar'),
                                            { academic_year: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Years</option>
                                    {academicYears.map((acy) => (
                                        <option key={acy} value={acy}>
                                            {acy}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* View Switcher */}
                            <div className="flex gap-2">
                                {(['month', 'week', 'year'] as ViewType[]).map((type) => (
                                    <button
                                        key={type}
                                        onClick={() => setViewType(type)}
                                        className={`px-4 py-2 rounded-lg font-medium transition ${
                                            viewType === type
                                                ? 'bg-indigo-600 text-white'
                                                : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
                                        }`}
                                    >
                                        {type.charAt(0).toUpperCase() + type.slice(1)}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    {/* MONTH VIEW */}
                    {viewType === 'month' && (
                        <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                            {/* Sidebar - Mini Months */}
                            <div className="space-y-2 max-h-screen overflow-y-auto">
                                {Array(24)
                                    .fill(0)
                                    .map((_, i) => {
                                        const m = i % 12;
                                        const y = currentYear + Math.floor(i / 12);
                                        return (
                                            <MiniMonth
                                                key={`${y}-${m}`}
                                                month={m}
                                                year={y}
                                                events={localEvents}
                                                isSelected={m === selectedMonth && y === selectedYear}
                                                onSelect={() => {
                                                    setSelectedMonth(m);
                                                    setSelectedYear(y);
                                                }}
                                            />
                                        );
                                    })}
                            </div>

                            {/* Main - Large Month */}
                            <div className="lg:col-span-3">
                                <MainMonth
                                    month={selectedMonth}
                                    year={selectedYear}
                                    events={localEvents}
                                    draggingEvent={draggingEvent}
                                    dragOverDay={dragOverDay}
                                    onDragStart={handleDragStart}
                                    onDragOver={handleDragOver}
                                    onDragEnter={handleDragEnter}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                    onDayClick={(dateStr) => {
                                        if (!draggingEvent) setAddingToDate(dateStr);
                                    }}
                                    onEventClick={setSelectedEvent}
                                />
                            </div>
                        </div>
                    )}

                    {/* WEEK VIEW */}
                    {viewType === 'week' && (
                        <WeekView
                            events={localEvents}
                            currentDate={weekDate}
                            onPrevWeek={() => {
                                const d = new Date(weekDate);
                                d.setDate(d.getDate() - 7);
                                setWeekDate(d);
                            }}
                            onNextWeek={() => {
                                const d = new Date(weekDate);
                                d.setDate(d.getDate() + 7);
                                setWeekDate(d);
                            }}
                            onEventClick={setSelectedEvent}
                        />
                    )}

                    {/* YEAR VIEW */}
                    {viewType === 'year' && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            {Array(24)
                                .fill(0)
                                .map((_, i) => {
                                    const m = i % 12;
                                    const y = currentYear + Math.floor(i / 12);
                                    return (
                                        <YearCard
                                            key={`${y}-${m}`}
                                            month={m}
                                            year={y}
                                            events={localEvents}
                                            onSelect={() => {
                                                setViewType('month');
                                                setSelectedMonth(m);
                                                setSelectedYear(y);
                                            }}
                                        />
                                    );
                                })}
                        </div>
                    )}

                    {/* Legend */}
                    <div className="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 className="font-semibold text-gray-900 mb-4">Event Types</h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {Array.from(new Set(localEvents.map((e) => e.type))).map((type) => {
                                const event = localEvents.find((e) => e.type === type);
                                if (!event) return null;
                                return (
                                    <div key={type} className="flex items-center gap-2">
                                        <div
                                            className="w-4 h-4 rounded"
                                            style={{
                                                backgroundColor: event.color || TYPE_COLORS[type] || '#6B7280',
                                            }}
                                        />
                                        <span className="text-sm text-gray-700 capitalize">{typeLabels[type] || type}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>

            {/* Quick Add Modal */}
            <Modal isOpen={!!addingToDate} onClose={() => { setAddingToDate(null); setError(null); }} title="Add Quick Event">
                <form onSubmit={handleQuickAdd} className="space-y-4">
                    {error && (
                        <div className="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                            {error}
                        </div>
                    )}
                    <div className="form-group">
                        <label className="form-label">Title <span className="text-red-500">*</span></label>
                        <input
                            type="text"
                            className="form-control"
                            value={quickForm.title}
                            onChange={(e) => setQuickForm({ ...quickForm, title: e.target.value })}
                            placeholder="Event title"
                            maxLength={255}
                            required
                        />
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="form-group">
                            <label className="form-label">Type <span className="text-red-500">*</span></label>
                            <select
                                className="form-control"
                                value={quickForm.type}
                                onChange={(e) => setQuickForm({ ...quickForm, type: e.target.value })}
                            >
                                {Object.entries(typeLabels).map(([key, label]) => (
                                    <option key={key} value={key}>
                                        {label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="form-group">
                            <label className="form-label">Color <span className="text-red-500">*</span></label>
                            <div className="flex gap-2 flex-wrap">
                                {COLOR_SWATCHES.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        onClick={() => setQuickForm({ ...quickForm, color })}
                                        className={`w-6 h-6 rounded border-2 ${
                                            quickForm.color === color ? 'border-gray-900' : 'border-gray-300'
                                        }`}
                                        style={{ backgroundColor: color }}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="form-group">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={quickForm.is_all_day}
                                onChange={(e) => setQuickForm({ ...quickForm, is_all_day: e.target.checked })}
                                className="rounded"
                            />
                            <span className="form-label mb-0">All Day Event</span>
                        </label>
                    </div>

                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => { setAddingToDate(null); setError(null); }} className="btn-secondary flex-1">
                            Cancel
                        </button>
                        <button type="submit" disabled={isSubmitting} className="btn-primary flex-1">
                            {isSubmitting ? 'Adding…' : 'Add Event'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Event Detail Popup */}
            <Modal isOpen={!!selectedEvent} onClose={() => setSelectedEvent(null)} title="Event Details">
                {selectedEvent && (
                    <div className="space-y-4">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">{selectedEvent.title}</h3>
                            <div className="flex flex-wrap gap-2 mt-2">
                                <span
                                    className="text-xs px-3 py-1 rounded-full text-white"
                                    style={{ backgroundColor: selectedEvent.color || TYPE_COLORS[selectedEvent.type] }}
                                >
                                    {typeLabels[selectedEvent.type] || selectedEvent.type}
                                </span>
                                {selectedEvent.is_all_day && (
                                    <span className="text-xs px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                                        All Day
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="text-sm text-gray-600">
                            <p>
                                <strong>Dates:</strong> {new Date(selectedEvent.start_date).toLocaleDateString('en-GB')}
                                {selectedEvent.start_date !== selectedEvent.end_date &&
                                    ` - ${new Date(selectedEvent.end_date).toLocaleDateString('en-GB')}`}
                            </p>
                            {selectedEvent.location && (
                                <p className="mt-2">
                                    <strong>Location:</strong> {selectedEvent.location}
                                </p>
                            )}
                            {selectedEvent.description && (
                                <p className="mt-2">
                                    <strong>Description:</strong> {selectedEvent.description}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-3 pt-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setSelectedEvent(null);
                                    router.get(route('principal.academic-calendars.edit', selectedEvent.id));
                                }}
                                className="btn-secondary flex-1 inline-flex items-center justify-center gap-2"
                            >
                                <PencilIcon className="w-4 h-4" />
                                Edit
                            </button>
                            <button
                                type="button"
                                onClick={() => handleDeleteEvent(selectedEvent.id)}
                                className="btn-danger flex-1 inline-flex items-center justify-center gap-2"
                            >
                                <TrashIcon className="w-4 h-4" />
                                Delete
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </AppLayout>
    );
}
