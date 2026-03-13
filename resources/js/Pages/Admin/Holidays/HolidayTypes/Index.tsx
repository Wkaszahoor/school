import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { TrashIcon, PencilIcon, PlusIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';

interface HolidayType {
    id: number;
    name: string;
    color: string;
    description: string | null;
    is_active: boolean;
}

interface Props {
    types: {
        data: HolidayType[];
        links: any[];
        current_page: number;
        last_page: number;
    };
}

export default function HolidayTypesIndex({ types }: Props) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [formData, setFormData] = useState<Partial<HolidayType>>({});
    const [showForm, setShowForm] = useState(false);

    const handleAdd = () => {
        setEditingId(null);
        setFormData({ name: '', color: '#FF6B6B', is_active: true });
        setShowForm(true);
    };

    const handleEdit = (type: HolidayType) => {
        setEditingId(type.id);
        setFormData(type);
        setShowForm(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const url = editingId
            ? route('admin.holiday-types.update', editingId)
            : route('admin.holiday-types.store');
        const method = editingId ? 'put' : 'post';

        router[method as 'post' | 'put'](url, formData, {
            onSuccess: () => setShowForm(false),
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this holiday type?')) {
            router.delete(route('admin.holiday-types.destroy', id));
        }
    };

    return (
        <AppLayout title="Holiday Types">
            <Head title="Holiday Types" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-6xl mx-auto">
                    <div className="flex justify-between items-center mb-6">
                        <h1 className="text-2xl font-bold text-gray-900">Holiday Types</h1>
                        <button
                            onClick={handleAdd}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                        >
                            <PlusIcon className="w-5 h-5" />
                            Add Holiday Type
                        </button>
                    </div>

                    {showForm && (
                        <div className="bg-white rounded-lg shadow p-6 mb-6">
                            <h2 className="text-lg font-semibold mb-4">
                                {editingId ? 'Edit Holiday Type' : 'Add Holiday Type'}
                            </h2>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Name *
                                    </label>
                                    <input
                                        type="text"
                                        required
                                        value={formData.name || ''}
                                        onChange={(e) =>
                                            setFormData({ ...formData, name: e.target.value })
                                        }
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="e.g., National Holiday"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Color *
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            type="color"
                                            required
                                            value={formData.color || '#FF6B6B'}
                                            onChange={(e) =>
                                                setFormData({ ...formData, color: e.target.value })
                                            }
                                            className="w-16 h-10 border border-gray-300 rounded cursor-pointer"
                                        />
                                        <input
                                            type="text"
                                            value={formData.color || '#FF6B6B'}
                                            onChange={(e) =>
                                                setFormData({ ...formData, color: e.target.value })
                                            }
                                            className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            placeholder="#FF6B6B"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Description
                                    </label>
                                    <textarea
                                        value={formData.description || ''}
                                        onChange={(e) =>
                                            setFormData({ ...formData, description: e.target.value })
                                        }
                                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="Description (optional)"
                                        rows={3}
                                    />
                                </div>

                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        checked={formData.is_active !== false}
                                        onChange={(e) =>
                                            setFormData({ ...formData, is_active: e.target.checked })
                                        }
                                        className="rounded"
                                    />
                                    <label htmlFor="is_active" className="text-sm text-gray-700">
                                        Active
                                    </label>
                                </div>

                                <div className="flex gap-2">
                                    <button
                                        type="submit"
                                        className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"
                                    >
                                        {editingId ? 'Update' : 'Create'}
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setShowForm(false)}
                                        className="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg hover:bg-gray-400 transition"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {types.data.map((type) => (
                            <div
                                key={type.id}
                                className="bg-white rounded-lg shadow p-4 border-l-4"
                                style={{ borderColor: type.color }}
                            >
                                <div className="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 className="font-semibold text-gray-900">{type.name}</h3>
                                        {!type.is_active && (
                                            <span className="text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded mt-1 inline-block">
                                                Inactive
                                            </span>
                                        )}
                                    </div>
                                    <div
                                        className="w-8 h-8 rounded-full"
                                        style={{ backgroundColor: type.color }}
                                    />
                                </div>
                                {type.description && (
                                    <p className="text-sm text-gray-600 mb-4">{type.description}</p>
                                )}
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleEdit(type)}
                                        className="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition text-sm"
                                    >
                                        <PencilIcon className="w-4 h-4" />
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => handleDelete(type.id)}
                                        className="flex-1 flex items-center justify-center gap-1 px-3 py-2 bg-red-50 text-red-600 rounded hover:bg-red-100 transition text-sm"
                                    >
                                        <TrashIcon className="w-4 h-4" />
                                        Delete
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
