import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface HolidayType {
    id: number;
    name: string;
}

interface Props {
    types: HolidayType[];
}

export default function Create({ types }: Props) {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        holiday_date: '',
        holiday_type_id: '',
        description: '',
        duration: 1,
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        is_gazetted: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.holidays.store'));
    };

    return (
        <AppLayout title="Add Holiday">
            <Head title="Add Holiday" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    <div className="flex items-center gap-3 mb-6">
                        <Link href={route('admin.holidays.index')} className="text-gray-600 hover:text-gray-900">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">Add Holiday</h1>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Holiday Name *
                            </label>
                            <input
                                type="text"
                                required
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., Eid ul-Fitr"
                            />
                            {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Holiday Date *
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={data.holiday_date}
                                    onChange={(e) => setData('holiday_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.holiday_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.holiday_date}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Holiday Type *
                                </label>
                                <select
                                    required
                                    value={data.holiday_type_id}
                                    onChange={(e) => setData('holiday_type_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Select type...</option>
                                    {types.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                                {errors.holiday_type_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.holiday_type_id}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Duration (days) *
                                </label>
                                <input
                                    type="number"
                                    required
                                    min="1"
                                    max="365"
                                    value={data.duration}
                                    onChange={(e) => setData('duration', parseInt(e.target.value))}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.duration && (
                                    <p className="mt-1 text-sm text-red-600">{errors.duration}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year *
                                </label>
                                <input
                                    type="text"
                                    required
                                    value={data.academic_year}
                                    onChange={(e) => setData('academic_year', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="e.g., 2026-2027"
                                />
                                {errors.academic_year && (
                                    <p className="mt-1 text-sm text-red-600">{errors.academic_year}</p>
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
                                placeholder="Optional description..."
                                rows={4}
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="is_gazetted"
                                checked={data.is_gazetted}
                                onChange={(e) => setData('is_gazetted', e.target.checked)}
                                className="rounded"
                            />
                            <label htmlFor="is_gazetted" className="text-sm text-gray-700">
                                Is Gazetted (Government Declared Holiday)
                            </label>
                        </div>

                        <div className="flex gap-2 pt-4 border-t">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                            >
                                Create Holiday
                            </button>
                            <Link
                                href={route('admin.holidays.index')}
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
