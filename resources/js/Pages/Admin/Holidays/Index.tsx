import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { PlusIcon, PencilIcon, TrashIcon, CalendarIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface Holiday {
    id: number;
    name: string;
    holiday_date: string;
    duration: number;
    academic_year: string;
    is_gazetted: boolean;
    holidayType: {
        id: number;
        name: string;
        color: string;
    };
}

interface Props {
    holidays: {
        data: Holiday[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    types: Array<{ id: number; name: string; color: string }>;
    academicYears: string[];
    months: Array<{ value: number; label: string }>;
}

export default function HolidaysIndex({ holidays, types, academicYears, months }: Props) {
    const { props: pageProps } = usePage();
    const params = pageProps.ziggy?.params || {};

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this holiday?')) {
            router.delete(route('admin.holidays.destroy', id));
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

    return (
        <AppLayout title="Holidays">
            <Head title="Holidays Management" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Holidays Management</h1>
                        <div className="flex gap-2">
                            <Link
                                href={route('admin.holidays.calendar')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
                            >
                                <CalendarIcon className="w-5 h-5" />
                                View Calendar
                            </Link>
                            <Link
                                href={route('admin.holidays.create')}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                            >
                                <PlusIcon className="w-5 h-5" />
                                Add Holiday
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Search
                                </label>
                                <input
                                    type="text"
                                    defaultValue={params.search || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('admin.holidays.index'),
                                            { ...params, search: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    placeholder="Search holidays..."
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year
                                </label>
                                <select
                                    defaultValue={params.academic_year || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('admin.holidays.index'),
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

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Holiday Type
                                </label>
                                <select
                                    defaultValue={params.type_id || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('admin.holidays.index'),
                                            { ...params, type_id: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Types</option>
                                    {types.map((type) => (
                                        <option key={type.id} value={type.id}>
                                            {type.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Month
                                </label>
                                <select
                                    defaultValue={params.month || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('admin.holidays.index'),
                                            { ...params, month: e.target.value },
                                            { preserveScroll: true }
                                        )
                                    }
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Months</option>
                                    {months.map((month) => (
                                        <option key={month.value} value={month.value}>
                                            {month.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Holiday Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Type
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Duration
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Academic Year
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {holidays.data.map((holiday) => (
                                        <tr key={holiday.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="font-medium text-gray-900">
                                                    {holiday.name}
                                                </div>
                                                {holiday.is_gazetted && (
                                                    <span className="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                                        Gazetted
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {formatDate(holiday.holiday_date)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span
                                                    className="text-sm font-medium px-3 py-1 rounded text-white"
                                                    style={{
                                                        backgroundColor: holiday.holidayType.color,
                                                    }}
                                                >
                                                    {holiday.holidayType.name}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {holiday.duration} day{holiday.duration > 1 ? 's' : ''}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {holiday.academic_year}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="flex gap-2">
                                                    <Link
                                                        href={route('admin.holidays.edit', holiday.id)}
                                                        className="text-blue-600 hover:text-blue-900"
                                                        title="Edit"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(holiday.id)}
                                                        className="text-red-600 hover:text-red-900"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {holidays.data.length === 0 && (
                            <div className="text-center py-12">
                                <p className="text-gray-500">No holidays found</p>
                            </div>
                        )}
                    </div>

                    {/* Pagination */}
                    {holidays.last_page > 1 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {holidays.links.map((link, i) => (
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
