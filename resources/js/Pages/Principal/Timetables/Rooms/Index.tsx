import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { PlusIcon, TrashIcon, PencilIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import { RoomConfiguration, PaginatedData } from '@/types/timetable';
import { PageProps } from '@/types';

interface Props extends PageProps {
    rooms: PaginatedData<RoomConfiguration>;
}

const roomTypeColors: Record<string, string> = {
    classroom: 'bg-blue-100 text-blue-800',
    lab: 'bg-purple-100 text-purple-800',
    auditorium: 'bg-green-100 text-green-800',
    sports: 'bg-yellow-100 text-yellow-800',
    art: 'bg-pink-100 text-pink-800',
    music: 'bg-indigo-100 text-indigo-800',
    library: 'bg-red-100 text-red-800',
};

export default function Index({ rooms }: Props) {
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        room_name: '',
        room_type: 'classroom',
        capacity: '',
        block: '',
        floor: '',
        has_projector: false,
        has_lab_equipment: false,
        has_ac: false,
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.rooms.store'), {
            onSuccess: () => {
                reset();
                setShowForm(false);
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Delete this room?')) {
            router.delete(route('principal.rooms.destroy', id));
        }
    };

    return (
        <AppLayout>
            <Head title="Rooms" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <h1 className="text-3xl font-bold text-gray-900">Room Configuration</h1>
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="mt-4 sm:mt-0 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"
                        >
                            <PlusIcon className="w-5 h-5" />
                            Add Room
                        </button>
                    </div>

                    {/* Form */}
                    {showForm && (
                        <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                            <h2 className="text-xl font-bold text-gray-900 mb-4">Create Room</h2>
                            <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input
                                    type="text"
                                    placeholder="Room Name"
                                    value={data.room_name}
                                    onChange={(e) => setData('room_name', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <select
                                    value={data.room_type}
                                    onChange={(e) => setData('room_type', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="classroom">Classroom</option>
                                    <option value="lab">Lab</option>
                                    <option value="auditorium">Auditorium</option>
                                    <option value="sports">Sports</option>
                                    <option value="art">Art</option>
                                    <option value="music">Music</option>
                                    <option value="library">Library</option>
                                </select>
                                <input
                                    type="number"
                                    placeholder="Capacity"
                                    value={data.capacity}
                                    onChange={(e) => setData('capacity', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="text"
                                    placeholder="Block (e.g., A, B)"
                                    value={data.block}
                                    onChange={(e) => setData('block', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="text"
                                    placeholder="Floor"
                                    value={data.floor}
                                    onChange={(e) => setData('floor', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <input
                                    type="text"
                                    placeholder="Description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                                />
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={data.has_projector}
                                        onChange={(e) => setData('has_projector', e.target.checked)}
                                        className="rounded"
                                    />
                                    <span className="text-sm">Has Projector</span>
                                </label>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={data.has_lab_equipment}
                                        onChange={(e) => setData('has_lab_equipment', e.target.checked)}
                                        className="rounded"
                                    />
                                    <span className="text-sm">Has Lab Equipment</span>
                                </label>
                                <label className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        checked={data.has_ac}
                                        onChange={(e) => setData('has_ac', e.target.checked)}
                                        className="rounded"
                                    />
                                    <span className="text-sm">Has AC</span>
                                </label>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="col-span-full px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                                >
                                    {processing ? 'Creating...' : 'Create Room'}
                                </button>
                            </form>
                        </div>
                    )}

                    {/* List */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                        {rooms.data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Room Name</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Type</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Capacity</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Location</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Amenities</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {rooms.data.map((room) => (
                                            <tr key={room.id} className="hover:bg-gray-50">
                                                <td className="px-6 py-4 font-semibold text-gray-900">{room.room_name}</td>
                                                <td className="px-6 py-4">
                                                    <span className={`px-2 py-1 text-xs rounded ${roomTypeColors[room.room_type] || 'bg-gray-100'}`}>
                                                        {room.room_type}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-gray-600">{room.capacity}</td>
                                                <td className="px-6 py-4 text-sm text-gray-600">
                                                    {room.block && `Block ${room.block}`}
                                                    {room.floor && `, ${room.floor}`}
                                                </td>
                                                <td className="px-6 py-4 text-sm flex gap-2">
                                                    {room.has_projector && <span title="Projector">📽️</span>}
                                                    {room.has_ac && <span title="AC">❄️</span>}
                                                    {room.has_lab_equipment && <span title="Lab Equipment">🔧</span>}
                                                    {!room.has_projector && !room.has_ac && !room.has_lab_equipment && (
                                                        <span className="text-gray-400">—</span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <button
                                                        onClick={() => handleDelete(room.id)}
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
                            <div className="p-12 text-center text-gray-600">No rooms configured</div>
                        )}
                    </div>

                    {rooms.data.length > 0 && <Pagination data={rooms} />}
                </div>
            </div>
        </AppLayout>
    );
}
