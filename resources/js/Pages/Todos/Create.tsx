import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props {
    users: User[];
}

export default function Create({ users }: Props) {
    const { data, setData, post, errors, processing } = useForm({
        title: '',
        description: '',
        due_date: '',
        priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
        category: 'academic' as 'academic' | 'administrative' | 'event' | 'maintenance' | 'other',
        assigned_to_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('todos.store'));
    };

    return (
        <AppLayout title="Create Task">
            <Head title="Create Task" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    <div className="flex items-center gap-3 mb-6">
                        <Link href={route('todos.index')} className="text-gray-600 hover:text-gray-900">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <h1 className="text-2xl font-bold text-gray-900">Create Task</h1>
                    </div>

                    <form onSubmit={handleSubmit} className="bg-white rounded-lg shadow p-6 space-y-6">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Task Title *
                            </label>
                            <input
                                type="text"
                                required
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., Review exam papers"
                            />
                            {errors.title && <p className="mt-1 text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                placeholder="Task description..."
                                rows={4}
                            />
                            {errors.description && (
                                <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Due Date *
                                </label>
                                <input
                                    type="date"
                                    required
                                    value={data.due_date}
                                    onChange={(e) => setData('due_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {errors.due_date && (
                                    <p className="mt-1 text-sm text-red-600">{errors.due_date}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Priority *
                                </label>
                                <select
                                    required
                                    value={data.priority}
                                    onChange={(e) => setData('priority', e.target.value as any)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                                {errors.priority && (
                                    <p className="mt-1 text-sm text-red-600">{errors.priority}</p>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Category *
                                </label>
                                <select
                                    required
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value as any)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="academic">Academic</option>
                                    <option value="administrative">Administrative</option>
                                    <option value="event">Event</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="other">Other</option>
                                </select>
                                {errors.category && (
                                    <p className="mt-1 text-sm text-red-600">{errors.category}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Assign To *
                                </label>
                                <select
                                    required
                                    value={data.assigned_to_id}
                                    onChange={(e) => setData('assigned_to_id', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">Select a person...</option>
                                    {users.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                        </option>
                                    ))}
                                </select>
                                {errors.assigned_to_id && (
                                    <p className="mt-1 text-sm text-red-600">{errors.assigned_to_id}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex gap-2 pt-4 border-t">
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50"
                            >
                                Create Task
                            </button>
                            <Link
                                href={route('todos.index')}
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
