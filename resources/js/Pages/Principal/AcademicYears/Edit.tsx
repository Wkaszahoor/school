import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface AcademicYear {
    id: number;
    year: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    description: string | null;
}

interface Props extends PageProps {
    academicYear: AcademicYear;
}

export default function PrincipalEditAcademicYear({ academicYear }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        year: academicYear.year,
        start_date: academicYear.start_date,
        end_date: academicYear.end_date,
        is_active: academicYear.is_active,
        description: academicYear.description || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('principal.academic-years.update', academicYear.id));
    };

    return (
        <AppLayout title={`Edit Academic Year — ${academicYear.year}`}>
            <Head title={`Edit — ${academicYear.year}`} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('principal.academic-years.show', academicYear.id)} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Edit Academic Year</h1>
                        <p className="page-subtitle">{academicYear.year}</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="max-w-2xl">
                <div className="space-y-5">
                    {/* Basic Information */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Academic Year Details</p></div>
                        <div className="card-body space-y-4">
                            <div className="form-group">
                                <label className="form-label">Academic Year *</label>
                                <input
                                    type="text"
                                    placeholder="e.g., 2025-26"
                                    className="form-input"
                                    value={data.year}
                                    onChange={(e) => setData('year', e.target.value)}
                                />
                                {errors.year && <p className="text-red-600 text-sm mt-1">{errors.year}</p>}
                                <p className="text-xs text-gray-500 mt-1">Format: YYYY-YY (e.g., 2025-26)</p>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="form-group">
                                    <label className="form-label">Start Date *</label>
                                    <input
                                        type="date"
                                        className="form-input"
                                        value={data.start_date}
                                        onChange={(e) => setData('start_date', e.target.value)}
                                    />
                                    {errors.start_date && <p className="text-red-600 text-sm mt-1">{errors.start_date}</p>}
                                </div>

                                <div className="form-group">
                                    <label className="form-label">End Date *</label>
                                    <input
                                        type="date"
                                        className="form-input"
                                        value={data.end_date}
                                        onChange={(e) => setData('end_date', e.target.value)}
                                    />
                                    {errors.end_date && <p className="text-red-600 text-sm mt-1">{errors.end_date}</p>}
                                </div>
                            </div>

                            <div className="form-group">
                                <label className="form-label">Description</label>
                                <textarea
                                    className="form-textarea"
                                    rows={3}
                                    placeholder="Add any notes or description for this academic year"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                {errors.description && <p className="text-red-600 text-sm mt-1">{errors.description}</p>}
                            </div>

                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="w-4 h-4 text-indigo-600 rounded"
                                />
                                <span className="text-sm font-semibold text-gray-700">Set as Active</span>
                            </label>
                        </div>
                    </div>

                    {/* Submit Buttons */}
                    <div className="card">
                        <div className="card-body space-y-3">
                            <button type="submit" disabled={processing} className="btn-primary w-full">
                                {processing ? 'Saving…' : 'Save Changes'}
                            </button>
                            <Link href={route('principal.academic-years.show', academicYear.id)} className="btn-secondary w-full text-center">
                                Cancel
                            </Link>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
