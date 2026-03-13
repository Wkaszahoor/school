import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { UsersIcon, BuildingLibraryIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import type { PageProps, SchoolClass } from '@/types';

interface DashboardProps extends PageProps {
    stats: { total_students: number; total_classes: number; unassigned: number };
    classBreakdown: (SchoolClass & { students_count: number })[];
}

export default function HelperDashboard({ stats, classBreakdown }: DashboardProps) {
    return (
        <AppLayout title="Helper Dashboard">
            <Head title="Helper Dashboard" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Principal Helper</h1>
                    <p className="page-subtitle">Student and class management support</p>
                </div>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-6">
                <StatCard label="Total Students" value={stats.total_students} icon={UsersIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
                <StatCard label="Total Classes" value={stats.total_classes} icon={BuildingLibraryIcon}
                          iconBg="bg-purple-50" iconColor="text-purple-600" />
                <StatCard label="Unassigned Groups" value={stats.unassigned} icon={UserGroupIcon}
                          iconBg="bg-amber-50" iconColor="text-amber-600" />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Quick Actions</p>
                    </div>
                    <div className="card-body space-y-3">
                        <Link href={route('helper.students')}
                              className="flex items-center gap-3 p-4 rounded-xl bg-indigo-50 hover:bg-indigo-100 transition-colors">
                            <UsersIcon className="w-6 h-6 text-indigo-600" />
                            <div>
                                <p className="font-semibold text-indigo-800">View Students</p>
                                <p className="text-sm text-indigo-600">Browse and search all students</p>
                            </div>
                        </Link>
                        <Link href={route('helper.students') + '?unassigned=1'}
                              className="flex items-center gap-3 p-4 rounded-xl bg-amber-50 hover:bg-amber-100 transition-colors">
                            <UserGroupIcon className="w-6 h-6 text-amber-600" />
                            <div>
                                <p className="font-semibold text-amber-800">Assign Groups</p>
                                <p className="text-sm text-amber-600">{stats.unassigned} students need group assignment</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <div className="card">
                    <div className="card-header">
                        <p className="card-title">Class Breakdown</p>
                    </div>
                    <div className="divide-y divide-gray-50">
                        {classBreakdown.map(cls => (
                            <div key={cls.id} className="flex items-center justify-between px-5 py-3.5">
                                <div>
                                    <p className="font-medium text-gray-900">{cls.name}{cls.section ? ` — ${cls.section}` : ''}</p>
                                    <p className="text-xs text-gray-400">{cls.academic_year}</p>
                                </div>
                                <span className="badge-blue badge">{cls.students_count} students</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
