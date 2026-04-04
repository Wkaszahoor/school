import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeftIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps } from '@/types';

interface AcademicYear {
    id: number;
    year: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    description: string | null;
    created_at: string;
}

interface Props extends PageProps {
    academicYear: AcademicYear;
    classesCount: number;
    studentsCount: number;
}

export default function PrincipalShowAcademicYear({ academicYear, classesCount, studentsCount }: Props) {
    const startDate = new Date(academicYear.start_date);
    const endDate = new Date(academicYear.end_date);
    const createdDate = new Date(academicYear.created_at);

    return (
        <AppLayout title={`Academic Year — ${academicYear.year}`}>
            <Head title={academicYear.year} />

            <div className="page-header">
                <div className="flex items-center gap-3 flex-1">
                    <Link href={route('principal.academic-years.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">{academicYear.year}</h1>
                        <p className="page-subtitle">Academic Year Details</p>
                    </div>
                </div>
                <Link href={route('principal.academic-years.edit', academicYear.id)} className="btn-secondary">
                    <PencilSquareIcon className="w-4 h-4" /> Edit
                </Link>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Main Information */}
                <div className="lg:col-span-2 space-y-5">
                    {/* Overview Card */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Overview</p></div>
                        <div className="card-body space-y-4">
                            <div className="flex justify-between items-center pb-3 border-b">
                                <span className="text-gray-600">Academic Year</span>
                                <span className="font-semibold text-lg">{academicYear.year}</span>
                            </div>
                            <div className="flex justify-between items-center pb-3 border-b">
                                <span className="text-gray-600">Status</span>
                                <Badge color={academicYear.is_active ? 'green' : 'yellow'}>{academicYear.is_active ? 'Active' : 'Inactive'}</Badge>
                            </div>
                            <div className="flex justify-between items-center pb-3 border-b">
                                <span className="text-gray-600">Start Date</span>
                                <span className="font-medium">{startDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            </div>
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600">End Date</span>
                                <span className="font-medium">{endDate.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            </div>
                        </div>
                    </div>

                    {/* Description */}
                    {academicYear.description && (
                        <div className="card">
                            <div className="card-header"><p className="card-title">Description</p></div>
                            <div className="card-body">
                                <p className="text-gray-700 whitespace-pre-wrap">{academicYear.description}</p>
                            </div>
                        </div>
                    )}
                </div>

                {/* Sidebar */}
                <div className="space-y-5">
                    {/* Statistics */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Statistics</p></div>
                        <div className="card-body space-y-3">
                            <div className="bg-blue-50 rounded-lg p-3">
                                <p className="text-sm text-gray-600 mb-1">Classes</p>
                                <p className="text-2xl font-bold text-blue-600">{classesCount}</p>
                            </div>
                            <div className="bg-green-50 rounded-lg p-3">
                                <p className="text-sm text-gray-600 mb-1">Students</p>
                                <p className="text-2xl font-bold text-green-600">{studentsCount}</p>
                            </div>
                        </div>
                    </div>

                    {/* Duration */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Duration</p></div>
                        <div className="card-body space-y-2 text-sm">
                            <div>
                                <p className="text-gray-600 mb-1">Starts</p>
                                <p className="font-medium">{startDate.toLocaleDateString()}</p>
                            </div>
                            <div>
                                <p className="text-gray-600 mb-1">Ends</p>
                                <p className="font-medium">{endDate.toLocaleDateString()}</p>
                            </div>
                            <div>
                                <p className="text-gray-600 mb-1">Total Days</p>
                                <p className="font-medium">{Math.floor((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)) + 1} days</p>
                            </div>
                        </div>
                    </div>

                    {/* Metadata */}
                    <div className="card">
                        <div className="card-header"><p className="card-title">Metadata</p></div>
                        <div className="card-body space-y-2 text-xs">
                            <div>
                                <p className="text-gray-600 mb-1">Created</p>
                                <p className="font-medium text-gray-700">{createdDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
