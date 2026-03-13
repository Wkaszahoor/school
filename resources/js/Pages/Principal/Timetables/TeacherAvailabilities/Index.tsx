import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import { TeacherAvailability, TimeSlot, PaginatedData } from '@/types/timetable';
import { User, PageProps } from '@/types';

interface Props extends PageProps {
    availabilities: PaginatedData<TeacherAvailability>;
    teachers: User[];
    timeSlots: TimeSlot[];
}

const dayLabels: Record<string, string> = {
    'Monday': 'Mon',
    'Tuesday': 'Tue',
    'Wednesday': 'Wed',
    'Thursday': 'Thu',
    'Friday': 'Fri',
    'Saturday': 'Sat',
};

export default function Index({ availabilities, teachers, timeSlots }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        teacher_id: '',
        day_of_week: 'Monday',
        time_slot_id: '',
        availability_type: 'available',
        notes: '',
        max_periods_per_day: '6',
        min_free_periods: '1',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.teacher-availabilities.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this availability constraint?')) {
            router.delete(route('principal.teacher-availabilities.destroy', id));
        }
    };

    return (
        <AppLayout>
            <Head title="Teacher Availabilities" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Teacher Availabilities</h1>
                            <p className="text-gray-600 mt-1">Manage teacher availability constraints and preferences</p>
                        </div>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            <PlusIcon className="w-5 h-5" />
                            Add Constraint
                        </button>
                    </div>

                    {/* Form */}
                    {showForm && (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Create Availability Constraint</h2>
                            <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <select
                                    value={data.teacher_id}
                                    onChange={(e) => setData('teacher_id', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Select Teacher</option>
                                    {teachers.map((teacher) => (
                                        <option key={teacher.id} value={teacher.id}>
                                            {teacher.name}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={data.day_of_week}
                                    onChange={(e) => setData('day_of_week', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="Monday">Monday</option>
                                    <option value="Tuesday">Tuesday</option>
                                    <option value="Wednesday">Wednesday</option>
                                    <option value="Thursday">Thursday</option>
                                    <option value="Friday">Friday</option>
                                    <option value="Saturday">Saturday</option>
                                </select>

                                <select
                                    value={data.time_slot_id}
                                    onChange={(e) => setData('time_slot_id', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Select Time Slot (Optional)</option>
                                    {timeSlots.map((slot) => (
                                        <option key={slot.id} value={slot.id}>
                                            {slot.name} ({slot.start_time} - {slot.end_time})
                                        </option>
                                    ))}
                                </select>

                                <select
                                    value={data.availability_type}
                                    onChange={(e) => setData('availability_type', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="available">Available</option>
                                    <option value="unavailable">Unavailable</option>
                                    <option value="preferred">Preferred</option>
                                </select>

                                <input
                                    type="number"
                                    placeholder="Max Periods Per Day"
                                    value={data.max_periods_per_day}
                                    onChange={(e) => setData('max_periods_per_day', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />

                                <input
                                    type="number"
                                    placeholder="Min Free Periods"
                                    value={data.min_free_periods}
                                    onChange={(e) => setData('min_free_periods', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />

                                <textarea
                                    placeholder="Notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="col-span-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                    rows={2}
                                />

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="col-span-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Add Constraint'}
                                </button>
                            </form>
                        </div>
                    )}

                    {/* List */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        {availabilities.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Teacher</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Day</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Time Slot</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Constraints</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {availabilities.data.map((avail) => (
                                            <tr key={avail.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 font-semibold text-gray-900">{avail.teacher?.name}</td>
                                                <td className="px-6 py-4 text-gray-600">{avail.day_of_week}</td>
                                                <td className="px-6 py-4 text-sm text-gray-600">
                                                    {avail.timeSlot ? `${avail.timeSlot.name}` : '—'}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 text-xs rounded ${
                                                        avail.availability_type === 'available' ? 'bg-green-100 text-green-800' :
                                                        avail.availability_type === 'unavailable' ? 'bg-red-100 text-red-800' :
                                                        'bg-blue-100 text-blue-800'
                                                    }`}>
                                                        {avail.availability_type}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-xs text-gray-600">
                                                    <div className="space-y-0.5">
                                                        <div>Max: {avail.max_periods_per_day} periods/day</div>
                                                        <div>Min free: {avail.min_free_periods} periods</div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <button
                                                        onClick={() => handleDelete(avail.id)}
                                                        className="text-red-600 hover:text-red-700"
                                                        title="Delete"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="p-12 text-center text-gray-600">
                                No availability constraints configured. Teachers are available by default.
                            </div>
                        )}
                    </div>

                    {availabilities.data.length > 0 && <Pagination data={availabilities} />}
                </div>
            </div>
        </AppLayout>
    );
}
