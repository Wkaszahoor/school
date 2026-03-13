import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { PlusIcon, ComputerDesktopIcon, TrashIcon } from '@heroicons/react/24/outline';
import type { TeacherDevice, User } from '@/types';

interface Props {
    teacher: User;
    devices: TeacherDevice[];
    canAssign?: boolean;
    onDeviceUpdate?: () => void;
}

export default function TeacherDevicesCard({
    teacher,
    devices,
    canAssign = false,
    onDeviceUpdate
}: Props) {
    const [showForm, setShowForm] = useState(false);
    const [formData, setFormData] = useState({
        device_type: 'laptop' as const,
        serial_number: '',
        model: '',
        made_year: new Date().getFullYear(),
        assigned_at: new Date().toISOString().split('T')[0],
        notes: '',
    });
    const [loading, setLoading] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setLoading(true);

        router.post(
            route('principal.teachers.devices.store', teacher.id),
            formData as any,
            {
                onSuccess: () => {
                    setFormData({
                        device_type: 'laptop',
                        serial_number: '',
                        model: '',
                        made_year: new Date().getFullYear(),
                        assigned_at: new Date().toISOString().split('T')[0],
                        notes: '',
                    });
                    setShowForm(false);
                    onDeviceUpdate?.();
                },
                onError: () => setLoading(false),
            }
        );
    };

    const handleDelete = (deviceId: number) => {
        if (confirm('Are you sure you want to unassign this device?')) {
            router.delete(route('principal.teachers.devices.destroy', deviceId), {
                onSuccess: () => onDeviceUpdate?.(),
            });
        }
    };

    const getDeviceIcon = (type: string) => {
        return <ComputerDesktopIcon className="w-5 h-5" />;
    };

    return (
        <div className="card">
            <div className="card-header">
                <div className="flex items-center justify-between">
                    <p className="card-title flex items-center gap-2">
                        <ComputerDesktopIcon className="w-5 h-5" />
                        Devices & Equipment
                    </p>
                    {canAssign && (
                        <button
                            onClick={() => setShowForm(!showForm)}
                            className="btn-sm btn-primary"
                        >
                            <PlusIcon className="w-4 h-4" /> Assign Device
                        </button>
                    )}
                </div>
            </div>

            {showForm && canAssign && (
                <div className="card-body border-t space-y-4 bg-gray-50/50">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div className="form-group">
                                <label className="form-label">Device Type</label>
                                <select
                                    value={formData.device_type}
                                    onChange={(e) => setFormData({ ...formData, device_type: e.target.value as any })}
                                    className="form-input"
                                >
                                    <option value="laptop">Laptop</option>
                                    <option value="chromebook">Chromebook</option>
                                    <option value="tablet">Tablet</option>
                                </select>
                            </div>
                            <div className="form-group">
                                <label className="form-label">Serial Number</label>
                                <input
                                    type="text"
                                    value={formData.serial_number}
                                    onChange={(e) => setFormData({ ...formData, serial_number: e.target.value })}
                                    className="form-input"
                                    required
                                />
                            </div>
                            <div className="form-group">
                                <label className="form-label">Model</label>
                                <input
                                    type="text"
                                    value={formData.model}
                                    onChange={(e) => setFormData({ ...formData, model: e.target.value })}
                                    className="form-input"
                                    required
                                />
                            </div>
                            <div className="form-group">
                                <label className="form-label">Made Year</label>
                                <input
                                    type="number"
                                    value={formData.made_year}
                                    onChange={(e) => setFormData({ ...formData, made_year: parseInt(e.target.value) })}
                                    className="form-input"
                                    min="2000"
                                    max={new Date().getFullYear()}
                                    required
                                />
                            </div>
                            <div className="form-group sm:col-span-2">
                                <label className="form-label">Assigned Date</label>
                                <input
                                    type="date"
                                    value={formData.assigned_at}
                                    onChange={(e) => setFormData({ ...formData, assigned_at: e.target.value })}
                                    className="form-input"
                                    required
                                />
                            </div>
                            <div className="form-group sm:col-span-2">
                                <label className="form-label">Notes</label>
                                <textarea
                                    value={formData.notes}
                                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    className="form-input"
                                    rows={2}
                                    placeholder="Optional notes..."
                                />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={loading}
                                className="btn-primary"
                            >
                                {loading ? 'Assigning...' : 'Assign Device'}
                            </button>
                            <button
                                type="button"
                                onClick={() => setShowForm(false)}
                                className="btn-secondary"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            )}

            <div className="card-body">
                {devices.length === 0 ? (
                    <p className="text-center text-gray-500">No devices assigned</p>
                ) : (
                    <div className="space-y-3">
                        {devices.map((device) => (
                            <div
                                key={device.id}
                                className="flex items-start justify-between rounded-lg border border-gray-200 p-3"
                            >
                                <div className="flex items-start gap-3">
                                    <div className="mt-1 text-gray-400">
                                        {getDeviceIcon(device.device_type)}
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-medium text-gray-900">
                                            {device.device_type.charAt(0).toUpperCase() + device.device_type.slice(1)}
                                        </p>
                                        <p className="text-sm text-gray-600">
                                            <span className="font-mono">{device.serial_number}</span> · {device.model}
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Made {device.made_year} · Assigned {new Date(device.assigned_at).toLocaleDateString()}
                                        </p>
                                        {device.unassigned_at && (
                                            <p className="text-xs text-orange-600">
                                                Unassigned on {new Date(device.unassigned_at).toLocaleDateString()}
                                            </p>
                                        )}
                                        {device.notes && (
                                            <p className="text-xs text-gray-600 mt-1">{device.notes}</p>
                                        )}
                                    </div>
                                </div>
                                {canAssign && (
                                    <button
                                        onClick={() => handleDelete(device.id)}
                                        className="text-gray-400 hover:text-red-600 transition"
                                        title="Unassign device"
                                    >
                                        <TrashIcon className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
