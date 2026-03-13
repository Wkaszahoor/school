import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface Holiday {
    id: number;
    name: string;
    holiday_date: string;
    duration: number;
    holidayType: {
        id: number;
        name: string;
        color: string;
    };
}

interface Props {
    holidays: Holiday[];
    year: number;
    academicYear: string | null;
    academicYears: string[];
}

export default function Calendar({ holidays, year, academicYear, academicYears }: Props) {
    const [currentMonth, setCurrentMonth] = useState(new Date().getMonth());
    const [currentYear, setCurrentYear] = useState(year);

    const getDaysInMonth = (month: number, year: number) => {
        return new Date(year, month + 1, 0).getDate();
    };

    const getFirstDayOfMonth = (month: number, year: number) => {
        return new Date(year, month, 1).getDay();
    };

    const isHoliday = (date: number, month: number, year: number) => {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
        return holidays.filter((h) => h.holiday_date === dateStr);
    };

    const monthDays = getDaysInMonth(currentMonth, currentYear);
    const firstDay = getFirstDayOfMonth(currentMonth, currentYear);
    const monthName = new Date(currentYear, currentMonth).toLocaleString('default', {
        month: 'long',
        year: 'numeric',
    });

    const days = Array(firstDay).fill(null);
    for (let i = 1; i <= monthDays; i++) {
        days.push(i);
    }

    const handlePrevMonth = () => {
        if (currentMonth === 0) {
            setCurrentMonth(11);
            setCurrentYear(currentYear - 1);
        } else {
            setCurrentMonth(currentMonth - 1);
        }
    };

    const handleNextMonth = () => {
        if (currentMonth === 11) {
            setCurrentMonth(0);
            setCurrentYear(currentYear + 1);
        } else {
            setCurrentMonth(currentMonth + 1);
        }
    };

    const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    return (
        <AppLayout title="Holiday Calendar">
            <Head title="Holiday Calendar" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Holiday Calendar</h1>
                        <div className="flex gap-2">
                            <Link
                                href={route('admin.holiday-types.index')}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                            >
                                Manage Types
                            </Link>
                            <Link
                                href={route('admin.holidays.index')}
                                className="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition text-sm"
                            >
                                View List
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Academic Year
                                </label>
                                <select
                                    value={academicYear || ''}
                                    onChange={(e) =>
                                        router.get(
                                            route('admin.holidays.calendar'),
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

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Year
                                </label>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="number"
                                        value={currentYear}
                                        onChange={(e) => setCurrentYear(parseInt(e.target.value))}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Month
                                </label>
                                <select
                                    value={currentMonth}
                                    onChange={(e) => setCurrentMonth(parseInt(e.target.value))}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    {Array.from({ length: 12 }).map((_, i) => (
                                        <option key={i} value={i}>
                                            {new Date(currentYear, i).toLocaleString('default', {
                                                month: 'long',
                                            })}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Calendar */}
                    <div className="bg-white rounded-lg shadow overflow-hidden">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4 flex items-center justify-between">
                            <button
                                onClick={handlePrevMonth}
                                className="p-2 hover:bg-indigo-500 rounded-lg transition"
                            >
                                <ChevronLeftIcon className="w-5 h-5 text-white" />
                            </button>
                            <h2 className="text-xl font-bold text-white">{monthName}</h2>
                            <button
                                onClick={handleNextMonth}
                                className="p-2 hover:bg-indigo-500 rounded-lg transition"
                            >
                                <ChevronRightIcon className="w-5 h-5 text-white" />
                            </button>
                        </div>

                        {/* Week days */}
                        <div className="grid grid-cols-7 bg-gray-100 border-b">
                            {weekDays.map((day) => (
                                <div key={day} className="p-4 text-center font-semibold text-gray-700">
                                    {day}
                                </div>
                            ))}
                        </div>

                        {/* Days */}
                        <div className="grid grid-cols-7">
                            {days.map((day, index) => {
                                const dayHolidays = day ? isHoliday(day, currentMonth, currentYear) : [];
                                return (
                                    <div
                                        key={index}
                                        className={`border-r border-b p-3 min-h-[120px] ${
                                            !day ? 'bg-gray-50' : 'hover:bg-indigo-50'
                                        }`}
                                    >
                                        {day && (
                                            <>
                                                <div className="font-semibold text-gray-900 mb-2">{day}</div>
                                                <div className="space-y-1">
                                                    {dayHolidays.map((holiday) => (
                                                        <div
                                                            key={holiday.id}
                                                            className="text-xs p-1 rounded text-white truncate"
                                                            style={{
                                                                backgroundColor: holiday.holidayType.color,
                                                            }}
                                                            title={holiday.name}
                                                        >
                                                            {holiday.name}
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

                    {/* Legend */}
                    <div className="bg-white rounded-lg shadow p-6 mt-6">
                        <h3 className="font-semibold text-gray-900 mb-4">Holiday Types</h3>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                            {Array.from(new Set(holidays.map((h) => h.holidayType.id))).map((typeId) => {
                                const holiday = holidays.find((h) => h.holidayType.id === typeId);
                                if (!holiday) return null;
                                return (
                                    <div key={typeId} className="flex items-center gap-2">
                                        <div
                                            className="w-4 h-4 rounded"
                                            style={{
                                                backgroundColor: holiday.holidayType.color,
                                            }}
                                        />
                                        <span className="text-sm text-gray-700">{holiday.holidayType.name}</span>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
