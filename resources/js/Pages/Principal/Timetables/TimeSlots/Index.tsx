import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import { TimeSlot, PaginatedData } from '@/types/timetable';
import { PageProps } from '@/types';

interface Props extends PageProps {
    timeSlots: PaginatedData<TimeSlot>;
}

export default function Index({ timeSlots }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        start_time: '',
        end_time: '',
        period_number: '',
        slot_type: 'regular',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.time-slots.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this time slot?')) {
            router.delete(route('principal.time-slots.destroy', id));
        }
    };

    return (
        <AppLayout>
            <Head title="Time Slots" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h1 className="text-3xl font-bold text-gray-900">Time Slots</h1>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            <PlusIcon className="w-5 h-5" />
                            Add Time Slot
                        </button>
                    </div>

                    {/* Form */}
                    {showForm && (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Create Time Slot</h2>
                            <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input
                                    type="text"
                                    placeholder="Name (e.g., Period 1)"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="number"
                                    placeholder="Period Number"
                                    value={data.period_number}
                                    onChange={(e) => setData('period_number', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="time"
                                    placeholder="Start Time"
                                    value={data.start_time}
                                    onChange={(e) => setData('start_time', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="time"
                                    placeholder="End Time"
                                    value={data.end_time}
                                    onChange={(e) => setData('end_time', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <select
                                    value={data.slot_type}
                                    onChange={(e) => setData('slot_type', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="regular">Regular</option>
                                    <option value="break">Break</option>
                                    <option value="lunch">Lunch</option>
                                    <option value="assembly">Assembly</option>
                                </select>
                                <input
                                    type="text"
                                    placeholder="Description (optional)"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="col-span-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Create Time Slot'}
                                </button>
                            </form>
                        </div>
                    )}

                    {/* List */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        {timeSlots.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Period</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Time</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Duration</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {timeSlots.data.map((slot) => (
                                            <tr key={slot.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 font-semibold text-gray-900">{slot.period_number}</td>
                                                <td className="px-6 py-4 text-gray-900">{slot.name}</td>
                                                <td className="px-6 py-4 text-gray-600">
                                                    {slot.start_time} - {slot.end_time}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                                        {slot.slot_type}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-gray-600">{slot.duration_minutes}m</td>
                                                <td className="px-6 py-4">
                                                    <button
                                                        onClick={() => handleDelete(slot.id)}
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
                            <div className="p-12 text-center text-gray-600">No time slots configured</div>
                        )}
                    </div>

                    {timeSlots.data.length > 0 && <Pagination data={timeSlots} />}
                </div>
            </div>
        </AppLayout>
    );
}
