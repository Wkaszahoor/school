import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface Props {
    academicYears: string[];
}

export default function Create({ academicYears }: Props) {
    const { data, setData, post, errors, processing } = useForm({
        title: '',
        description: '',
        start_date: '',
        end_date: '',
        type: 'event' as 'holiday' | 'exam' | 'term' | 'event' | 'semester' | 'break' | 'other',
        color: '#3B82F6',
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        is_all_day: true,
        location: '',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.academic-calendars.store'));
    };

    return (
        <AppLayout title="Add Academic Event">
            <Head title="Add Academic Event" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    <div className="flex items-center gap-3 mb-6">
                        <Link href={route('principal.academic-calendars.index')} className="text-gray-600 hover:text-gray-900">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">Add Academic Event</h1>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Event Title *
                            </label>
                            <input
                                type="text"
                                required
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., Mid-Term Exams"
                            />
                            {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Start Date *
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={data.start_date}
                                    onChange={(e) => setData('start_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.start_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.start_date}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    End Date *
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={data.end_date}
                                    onChange={(e) => setData('end_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.end_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.end_date}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Event Type *
                                </label>
                                <select
                                    required
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value as any)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="event">Event</option>
                                    <option value="holiday">Holiday</option>
                                    <option value="exam">Exam</option>
                                    <option value="term">Term</option>
                                    <option value="semester">Semester</option>
                                    <option value="break">Break</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.type && (
                                    <p className="mt-1 text-sm text-red-600">{errors.type}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year *
                                </label>
                                <select
                                    required
                                    value={data.academic_year}
                                    onChange={(e) => setData('academic_year', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    {academicYears.map((year) => (
                                        <option key={year} value={year}>
                                            {year}
                                        </option>
                                    ))}
                                </select>
                                {errors.academic_year && (
                                    <p className="mt-1 text-sm text-red-600">{errors.academic_year}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Color *
                                </label>
                                <div className="flex gap-2">
                                    <input
                                        type="color"
                                        required
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="w-16 h-10 border border-gray-300 rounded cursor-pointer"
                                    />
                                    <input
                                        type="text"
                                        value={data.color}
                                        onChange={(e) => setData('color', e.target.value)}
                                        className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="#3B82F6"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Location
                                </label>
                                <input
                                    type="text"
                                    value={data.location}
                                    onChange={(e) => setData('location', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="e.g., School Auditorium"
                                />
                                {errors.location && (
                                    <p className="mt-1 text-sm text-red-600">{errors.location}</p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Event description..."
                                rows={4}
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                            )}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Notes
                            </label>
                            <textarea
                                value={data.notes || ''}
                                onChange={(e) => setData('notes', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Additional notes..."
                                rows={3}
                            />
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_all_day"
                                checked={data.is_all_day}
                                onChange={(e) => setData('is_all_day', e.target.checked)}
                                className="rounded"
                            />
                            <label htmlFor="is_all_day" className="text-sm text-gray-700">
                                All Day Event
                            </label>
                        </div>

                        <div className="flex gap-2 pt-4 border-t">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                            >
                                Create Event
                            </button>
                            <Link
                                href={route('principal.academic-calendars.index')}
                                className="px-6 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition"
                            >
                                Cancel
                            </Link>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
