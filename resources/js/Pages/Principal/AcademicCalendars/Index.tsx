import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PlusIcon, PencilIcon, TrashIcon, CalendarIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface AcademicCalendar {
    id: number;
    title: string;
    description: string | null;
    start_date: string;
    end_date: string;
    type: 'holiday' | 'exam' | 'term' | 'event' | 'semester' | 'break' | 'other';
    color: string;
    academic_year: string;
    is_all_day: boolean;
    location: string | null;
}

interface Props {
    events: {
        data: AcademicCalendar[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    academicYears: string[];
}

const typeLabels: Record<string, string> = {
    holiday: 'Holiday',
    exam: 'Exam',
    term: 'Term',
    event: 'Event',
    semester: 'Semester',
    break: 'Break',
    other: 'Other',
};

const typeColors: Record<string, { bg: string; text: string }> = {
    holiday: { bg: 'bg-red-100', text: 'text-red-800' },
    exam: { bg: 'bg-blue-100', text: 'text-blue-800' },
    term: { bg: 'bg-green-100', text: 'text-green-800' },
    event: { bg: 'bg-purple-100', text: 'text-purple-800' },
    semester: { bg: 'bg-indigo-100', text: 'text-indigo-800' },
    break: { bg: 'bg-yellow-100', text: 'text-yellow-800' },
    other: { bg: 'bg-gray-100', text: 'text-gray-800' },
};

export default function AcademicCalendarsIndex({ events, academicYears }: Props) {
    const { props: pageProps } = usePage();
    const params = pageProps.ziggy?.params || {};

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this event?')) {
            router.delete(route('principal.academic-calendars.destroy', id));
        }
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const formatDateRange = (startDate: string, endDate: string, isAllDay: boolean) => {
        if (startDate === endDate) {
            return formatDate(startDate);
        }
        return `${formatDate(startDate)} - ${formatDate(endDate)}`;
    };

    return (
        <AppLayout title="Academic Calendar">
            <Head title="Academic Calendar Management" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Academic Calendar</h1>
                        <div className="flex gap-2">
                            <Link
                                href={route('principal.academic-calendars.calendar')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                            >
                                <CalendarIcon className="w-5 h-5" />
                                View Calendar
                            </Link>
                            <Link
                                href={route('principal.academic-calendars.create')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                <PlusIcon className="w-5 h-5" />
                                Add Event
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Search
                                </label>
                                <input
                                    type="text"
                                    defaultValue={params.search || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('principal.academic-calendars.index'),
                                            { ...params, search: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    placeholder="Search events..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Type
                                </label>
                                <select
                                    defaultValue={params.type || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('principal.academic-calendars.index'),
                                            { ...params, type: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Types</option>
                                    {Object.entries(typeLabels).map(([key, label]) => (
                                        <option key={key} value={key}>
                                            {label}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year
                                </label>
                                <select
                                    defaultValue={params.academic_year || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('principal.academic-calendars.index'),
                                            { ...params, academic_year: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Years</option>
                                    {academicYears.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Events List */}
                    <div className="grid grid-cols-1 gap-4">
                        {events.data.map((event) => (
                            <div
                                key={event.id}
                                className="bg-white rounded-lg shadow p-6 border-l-4"
                                style={{ borderColor: event.color }}
                            >
                                <div className="flex justify-between items-start mb-2">
                                    <div className="flex-1">
                                        <h3 className="text-lg font-semibold text-gray-900">
                                            {event.title}
                                        </h3>
                                        <div className="flex flex-wrap gap-2 mt-2">
                                            <span
                                                className={`text-xs px-3 py-1 rounded-full font-medium ${
                                                    typeColors[event.type]?.bg || 'bg-gray-100'
                                                } ${
                                                    typeColors[event.type]?.text || 'text-gray-800'
                                                }`}
                                            >
                                                {typeLabels[event.type] || event.type}
                                            </span>
                                            <span className="text-xs px-3 py-1 rounded-full font-medium bg-blue-100 text-blue-800">
                                                {event.academic_year}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex gap-2">
                                        <Link
                                            href={route('principal.academic-calendars.edit', event.id)}
                                            className="text-blue-600 hover:text-blue-900"
                                            title="Edit"
                                        >
                                            <PencilIcon className="w-4 h-4" />
                                        </Link>
                                        <button
                                            onClick={() => handleDelete(event.id)}
                                            className="text-red-600 hover:text-red-900"
                                            title="Delete"
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                <p className="text-sm text-gray-600 mb-3">
                                    {formatDateRange(event.start_date, event.end_date, event.is_all_day)}
                                </p>

                                {event.description && (
                                    <p className="text-sm text-gray-700 mb-3">{event.description}</p>
                                )}

                                {event.location && (
                                    <p className="text-sm text-gray-600">
                                        <strong>Location:</strong> {event.location}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>

                    {events.data.length === 0 && (
                        <div className="text-center py-12 bg-white rounded-lg shadow">
                            <p className="text-gray-500">No events found</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {events.last_page > 1 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {events.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || '#'}
                                    className={`px-3 py-2 rounded ${
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
