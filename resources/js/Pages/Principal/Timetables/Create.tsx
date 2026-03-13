import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import { PageProps } from '@/types';
import { TimeSlot } from '@/types/timetable';

interface Props extends PageProps {
    timeSlots: TimeSlot[];
}

const termOptions = [
    { value: 'spring', label: 'Spring' },
    { value: 'summer', label: 'Summer' },
    { value: 'autumn', label: 'Autumn' },
];

export default function Create({ timeSlots }: Props) {
    const [step, setStep] = useState(1);
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        term: 'spring',
        start_date: '',
        end_date: '',
        total_days: '5',
        notes: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.timetables.store'));
    };

    const isStep1Valid = data.name && data.academic_year && data.term;
    const isStep2Valid = data.start_date && data.end_date;
    const canProceed = isStep1Valid && isStep2Valid;

    return (
        <AppLayout>
            <Head title="Create Timetable" />

            <div className="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8">
                <div className="max-w-2xl mx-auto">
                    {/* Back Button */}
                    <button
                        onClick={() => window.history.back()}
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-6"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                        Back
                    </button>

                    {/* Card */}
                    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sm:p-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-2">Create New Timetable</h1>
                        <p className="text-gray-600 mb-8">
                            {step === 1 ? 'Step 1 of 2: Basic Information' : 'Step 2 of 2: Schedule Details'}
                        </p>

                        {/* Progress Bar */}
                        <div className="flex gap-2 mb-8">
                            <div className={`h-2 flex-1 rounded ${step >= 1 ? 'bg-indigo-600' : 'bg-gray-200'}`} />
                            <div className={`h-2 flex-1 rounded ${step >= 2 ? 'bg-indigo-600' : 'bg-gray-200'}`} />
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Step 1 */}
                            {step === 1 && (
                                <>
                                    {/* Name */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Timetable Name <span className="text-red-600">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="e.g., Spring 2026 Timetable"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        {errors.name && <p className="text-red-600 text-sm mt-1">{errors.name}</p>}
                                    </div>

                                    {/* Academic Year */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Academic Year <span className="text-red-600">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            value={data.academic_year}
                                            onChange={(e) => setData('academic_year', e.target.value)}
                                            placeholder="e.g., 2026-2027"
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        {errors.academic_year && <p className="text-red-600 text-sm mt-1">{errors.academic_year}</p>}
                                    </div>

                                    {/* Term */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Term <span className="text-red-600">*</span>
                                        </label>
                                        <select
                                            value={data.term}
                                            onChange={(e) => setData('term', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        >
                                            {termOptions.map((option) => (
                                                <option key={option.value} value={option.value}>
                                                    {option.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.term && <p className="text-red-600 text-sm mt-1">{errors.term}</p>}
                                    </div>
                                </>
                            )}

                            {/* Step 2 */}
                            {step === 2 && (
                                <>
                                    {/* Start Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            Start Date <span className="text-red-600">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.start_date}
                                            onChange={(e) => setData('start_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        {errors.start_date && <p className="text-red-600 text-sm mt-1">{errors.start_date}</p>}
                                    </div>

                                    {/* End Date */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            End Date <span className="text-red-600">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            value={data.end_date}
                                            onChange={(e) => setData('end_date', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                        {errors.end_date && <p className="text-red-600 text-sm mt-1">{errors.end_date}</p>}
                                    </div>

                                    {/* Total Days */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">
                                            School Days Per Week <span className="text-red-600">*</span>
                                        </label>
                                        <select
                                            value={data.total_days}
                                            onChange={(e) => setData('total_days', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        >
                                            <option value="5">5 Days (Monday-Friday)</option>
                                            <option value="6">6 Days (Monday-Saturday)</option>
                                        </select>
                                        {errors.total_days && <p className="text-red-600 text-sm mt-1">{errors.total_days}</p>}
                                    </div>

                                    {/* Notes */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-900 mb-2">Notes</label>
                                        <textarea
                                            value={data.notes}
                                            onChange={(e) => setData('notes', e.target.value)}
                                            placeholder="Any additional notes about this timetable..."
                                            rows={4}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        />
                                    </div>
                                </>
                            )}

                            {/* Buttons */}
                            <div className="flex gap-3 justify-end pt-6 border-t border-gray-200">
                                {step === 2 && (
                                    <button
                                        type="button"
                                        onClick={() => setStep(1)}
                                        className="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition"
                                    >
                                        Previous
                                    </button>
                                )}
                                {step === 1 && (
                                    <button
                                        type="button"
                                        onClick={() => setStep(2)}
                                        disabled={!isStep1Valid}
                                        className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Next
                                    </button>
                                )}
                                {step === 2 && (
                                    <button
                                        type="submit"
                                        disabled={!canProceed || processing}
                                        className="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Creating...' : 'Create Timetable'}
                                    </button>
                                )}
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
