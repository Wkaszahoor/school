import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

interface LeaveSetting {
    id: number;
    academic_year: string;
    leave_type: string;
    max_days: number;
    is_paid: boolean;
}

interface Props {
    settings: LeaveSetting[];
    academicYear: string;
    academicYears: string[];
}

export default function Settings({ settings, academicYear, academicYears }: Props) {
    const [selectedAcademicYear, setSelectedAcademicYear] = useState(academicYear);
    const [formSettings, setFormSettings] = useState<
        Array<{ leave_type: string; max_days: number; is_paid: boolean }>
    >(
        settings.length > 0
            ? settings.map((s) => ({
                  leave_type: s.leave_type,
                  max_days: s.max_days,
                  is_paid: s.is_paid,
              }))
            : [
                  { leave_type: 'casual', max_days: 10, is_paid: false },
                  { leave_type: 'annual', max_days: 14, is_paid: true },
                  { leave_type: 'emergency', max_days: 5, is_paid: true },
                  { leave_type: 'paid_total', max_days: 30, is_paid: true },
              ]
    );
    const [saving, setSaving] = useState(false);

    const leaveTypeLabels: Record<string, string> = {
        casual: 'Casual Leave',
        annual: 'Annual Leave',
        emergency: 'Emergency Leave',
        paid_total: 'Total Paid Leave',
    };

    const handleSettingChange = (
        index: number,
        field: 'max_days' | 'is_paid',
        value: number | boolean
    ) => {
        const updated = [...formSettings];
        if (field === 'max_days') {
            updated[index].max_days = value as number;
        } else if (field === 'is_paid') {
            updated[index].is_paid = value as boolean;
        }
        setFormSettings(updated);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);

        router.post(
            route('principal.leave.settings.store'),
            {
                academic_year: selectedAcademicYear,
                settings: formSettings,
            },
            {
                onFinish: () => setSaving(false),
            }
        );
    };

    const handleAcademicYearChange = (year: string) => {
        setSelectedAcademicYear(year);
        router.get(route('principal.leave.settings'), { academic_year: year }, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="Leave Settings" />

            <div className="py-8 px-4 sm:px-6 lg:px-8">
                <div className="max-w-4xl mx-auto">
                    {/* Header */}
                    <div className="mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">Leave Settings</h1>
                        <p className="text-gray-600">
                            Configure the maximum number of leave days allowed for each leave type per academic year.
                        </p>
                    </div>

                    {/* Academic Year Selector */}
                    <div className="mb-6 bg-white rounded-lg shadow-sm p-6">
                        <label className="block text-sm font-semibold text-gray-700 mb-3">
                            Academic Year
                        </label>
                        <select
                            value={selectedAcademicYear}
                            onChange={(e) => handleAcademicYearChange(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        >
                            {academicYears.map((year) => (
                                <option key={year} value={year}>
                                    {year}
                                </option>
                            ))}
                        </select>
                    </div>

                    {/* Settings Form */}
                    <form onSubmit={handleSubmit}>
                        <div className="bg-white rounded-lg shadow-sm overflow-hidden">
                            <div className="px-6 py-4 border-b border-gray-200">
                                <h2 className="text-lg font-bold text-gray-900">Leave Policy for {selectedAcademicYear}</h2>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                                                Leave Type
                                            </th>
                                            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                                                Max Days
                                            </th>
                                            <th className="px-6 py-3 text-left text-sm font-semibold text-gray-700">
                                                Paid Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {formSettings.map((setting, index) => (
                                            <tr key={setting.leave_type} className="hover:bg-gray-50">
                                                <td className="px-6 py-4">
                                                    <span className="text-sm font-medium text-gray-900">
                                                        {leaveTypeLabels[setting.leave_type]}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <input
                                                        type="number"
                                                        min="1"
                                                        value={setting.max_days}
                                                        onChange={(e) =>
                                                            handleSettingChange(
                                                                index,
                                                                'max_days',
                                                                parseInt(e.target.value)
                                                            )
                                                        }
                                                        className="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm"
                                                    />
                                                </td>
                                                <td className="px-6 py-4">
                                                    <label className="flex items-center gap-2 cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            checked={setting.is_paid}
                                                            onChange={(e) =>
                                                                handleSettingChange(
                                                                    index,
                                                                    'is_paid',
                                                                    e.target.checked
                                                                )
                                                            }
                                                            className="w-4 h-4 rounded border-gray-300"
                                                        />
                                                        <span className="text-sm text-gray-700">
                                                            {setting.is_paid ? '💰 Paid' : '🆓 Unpaid'}
                                                        </span>
                                                    </label>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                                <a
                                    href={route('principal.leave.calendar')}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                                >
                                    Back to Calendar
                                </a>
                                <button
                                    type="submit"
                                    disabled={saving}
                                    className="px-6 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {saving ? 'Saving...' : 'Save Settings'}
                                </button>
                            </div>
                        </div>
                    </form>

                    {/* Help Text */}
                    <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <h3 className="text-sm font-semibold text-blue-900 mb-2">💡 Help</h3>
                        <ul className="text-sm text-blue-800 space-y-1">
                            <li>• <strong>Casual Leave:</strong> Usually unpaid, for personal reasons</li>
                            <li>• <strong>Annual Leave:</strong> Usually paid, planned vacation time</li>
                            <li>• <strong>Emergency Leave:</strong> Urgent situations, usually paid</li>
                            <li>• <strong>Total Paid Leave:</strong> Maximum paid days allowed in the year</li>
                        </ul>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
