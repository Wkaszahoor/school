import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, CheckIcon, TrashIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface Todo {
    id: number;
    title: string;
    description: string | null;
    due_date: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    category: 'academic' | 'administrative' | 'event' | 'maintenance' | 'other';
    assigned_to_id: number | null;
    assigned_to?: {
        id: number;
        name: string;
    };
    completed_at: string | null;
    is_overdue: boolean;
}

interface Props {
    todos: {
        data: Todo[];
        links: any[];
        current_page: number;
        last_page: number;
    };
    statistics: {
        total: number;
        pending: number;
        in_progress: number;
        completed: number;
        overdue: number;
    };
    userRole: string;
}

const priorityColors: Record<string, { bg: string; text: string; badge: string }> = {
    low: { bg: 'bg-blue-100', text: 'text-blue-800', badge: 'bg-blue-500' },
    medium: { bg: 'bg-yellow-100', text: 'text-yellow-800', badge: 'bg-yellow-500' },
    high: { bg: 'bg-orange-100', text: 'text-orange-800', badge: 'bg-orange-500' },
    urgent: { bg: 'bg-red-100', text: 'text-red-800', badge: 'bg-red-500' },
};

const statusColors: Record<string, string> = {
    pending: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
};

const categoryColors: Record<string, string> = {
    academic: 'bg-purple-100 text-purple-800',
    administrative: 'bg-indigo-100 text-indigo-800',
    event: 'bg-pink-100 text-pink-800',
    maintenance: 'bg-amber-100 text-amber-800',
    other: 'bg-gray-100 text-gray-800',
};

export default function TodosIndex({ todos, statistics, userRole }: Props) {
    const [statusFilter, setStatusFilter] = useState<string>('');
    const [priorityFilter, setPriorityFilter] = useState<string>('');

    const handleStatusChange = (todoId: number, newStatus: string) => {
        router.patch(
            route('todos.update', todoId),
            { status: newStatus },
            { preserveScroll: true }
        );
    };

    const handleComplete = (todoId: number) => {
        router.post(
            route('todos.mark-complete', todoId),
            {},
            { preserveScroll: true }
        );
    };

    const handleDelete = (todoId: number) => {
        if (confirm('Are you sure you want to delete this task?')) {
            router.delete(route('todos.destroy', todoId));
        }
    };

    const filteredTodos = todos.data.filter((todo) => {
        let matches = true;
        if (statusFilter && todo.status !== statusFilter) matches = false;
        if (priorityFilter && todo.priority !== priorityFilter) matches = false;
        return matches;
    });

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    };

    const isOverdue = (dueDate: string, status: string): boolean => {
        if (status === 'completed' || status === 'cancelled') return false;
        return new Date(dueDate) < new Date();
    };

    return (
        <AppLayout title="To-Do List">
            <Head title="To-Do List" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-7xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">To-Do List</h1>
                        <Link
                            href={route('todos.create')}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            <PlusIcon className="w-5 h-5" />
                            Add Task
                        </Link>
                    </div>

                    {/* Statistics Dashboard */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div className="bg-white rounded-lg shadow p-6">
                            <div className="text-3xl font-bold text-gray-900">{statistics.total}</div>
                            <p className="text-sm text-gray-600 mt-1">Total Tasks</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                            <div className="text-3xl font-bold text-blue-600">{statistics.pending}</div>
                            <p className="text-sm text-gray-600 mt-1">Pending</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                            <div className="text-3xl font-bold text-purple-600">{statistics.in_progress}</div>
                            <p className="text-sm text-gray-600 mt-1">In Progress</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                            <div className="text-3xl font-bold text-green-600">{statistics.completed}</div>
                            <p className="text-sm text-gray-600 mt-1">Completed</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                            <div className="text-3xl font-bold text-red-600">{statistics.overdue}</div>
                            <p className="text-sm text-gray-600 mt-1">Overdue</p>
                        </div>
                    </div>

                    {/* Filters */}
                    <div className="bg-white rounded-lg shadow p-4 mb-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Status
                                </label>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Filter by Priority
                                </label>
                                <select
                                    value={priorityFilter}
                                    onChange={(e) => setPriorityFilter(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Tasks List */}
                    <div className="space-y-4">
                        {filteredTodos.map((todo) => {
                            const overdue = isOverdue(todo.due_date, todo.status);
                            return (
                                <div
                                    key={todo.id}
                                    className={`bg-white rounded-lg shadow p-6 border-l-4 transition ${
                                        todo.status === 'completed'
                                            ? 'border-gray-400 opacity-75'
                                            : `border-${priorityColors[todo.priority]?.badge}`
                                    }`}
                                    style={{
                                        borderLeftColor: todo.status === 'completed'
                                            ? '#9CA3AF'
                                            : (priorityColors[todo.priority]?.badge === 'bg-red-500' ? '#EF4444' :
                                               priorityColors[todo.priority]?.badge === 'bg-orange-500' ? '#F97316' :
                                               priorityColors[todo.priority]?.badge === 'bg-yellow-500' ? '#EAB308' :
                                               '#3B82F6'),
                                    }}
                                >
                                    <div className="flex justify-between items-start mb-4">
                                        <div className="flex-1">
                                            <div className="flex items-start gap-3">
                                                <button
                                                    onClick={() => handleComplete(todo.id)}
                                                    className={`mt-1 p-1.5 rounded ${
                                                        todo.status === 'completed'
                                                            ? 'bg-green-100 text-green-600'
                                                            : 'bg-gray-100 text-gray-400 hover:bg-gray-200'
                                                    }`}
                                                >
                                                    <CheckIcon className="w-5 h-5" />
                                                </button>
                                                <div>
                                                    <h3
                                                        className={`font-semibold ${
                                                            todo.status === 'completed'
                                                                ? 'line-through text-gray-500'
                                                                : 'text-gray-900'
                                                        }`}
                                                    >
                                                        {todo.title}
                                                    </h3>
                                                    {todo.description && (
                                                        <p className="text-sm text-gray-600 mt-1">{todo.description}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => handleDelete(todo.id)}
                                            className="text-red-600 hover:text-red-900 ml-2"
                                            title="Delete"
                                        >
                                            <TrashIcon className="w-4 h-4" />
                                        </button>
                                    </div>

                                    <div className="flex flex-wrap gap-2 mb-3">
                                        <span
                                            className={`text-xs px-3 py-1 rounded-full font-medium ${
                                                priorityColors[todo.priority]?.bg || 'bg-gray-100'
                                            } ${
                                                priorityColors[todo.priority]?.text || 'text-gray-800'
                                            }`}
                                        >
                                            {todo.priority.charAt(0).toUpperCase() + todo.priority.slice(1)}
                                        </span>
                                        <span
                                            className={`text-xs px-3 py-1 rounded-full font-medium ${
                                                statusColors[todo.status] || 'bg-gray-100'
                                            }`}
                                        >
                                            {todo.status.replace('_', ' ').charAt(0).toUpperCase() + todo.status.slice(1).replace('_', ' ')}
                                        </span>
                                        <span
                                            className={`text-xs px-3 py-1 rounded-full font-medium ${
                                                categoryColors[todo.category] || 'bg-gray-100'
                                            }`}
                                        >
                                            {todo.category.charAt(0).toUpperCase() + todo.category.slice(1)}
                                        </span>
                                        {overdue && (
                                            <span className="text-xs px-3 py-1 rounded-full font-medium bg-red-100 text-red-800 flex items-center gap-1">
                                                <ExclamationTriangleIcon className="w-3 h-3" />
                                                Overdue
                                            </span>
                                        )}
                                    </div>

                                    <div className="flex justify-between items-center text-sm">
                                        <div className="text-gray-600">
                                            Due: {formatDate(todo.due_date)}
                                            {todo.assigned_to && ` • Assigned to: ${todo.assigned_to.name}`}
                                        </div>
                                        {todo.status !== 'completed' && todo.status !== 'cancelled' && (
                                            <select
                                                value={todo.status}
                                                onChange={(e) => handleStatusChange(todo.id, e.target.value)}
                                                className="px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            >
                                                <option value="pending">Pending</option>
                                                <option value="in_progress">In Progress</option>
                                                <option value="completed">Completed</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {filteredTodos.length === 0 && (
                        <div className="text-center py-12 bg-white rounded-lg shadow">
                            <p className="text-gray-500">No tasks found</p>
                        </div>
                    )}

                    {/* Pagination */}
                    {todos.last_page > 1 && (
                        <div className="mt-6 flex justify-center gap-2">
                            {todos.links.map((link, i) => (
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
