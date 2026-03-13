import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { UsersIcon, PlusIcon, BuildingLibraryIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import type { PageProps, Student } from '@/types';

interface DashboardProps extends PageProps {
    stats: { total_students: number; new_this_month: number; total_classes: number };
    recentStudents: Student[];
}

export default function ReceptionistDashboard({ stats, recentStudents }: DashboardProps) {
    return (
        <AppLayout title="Receptionist Dashboard">
            <Head title="Receptionist Dashboard" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Reception Desk</h1>
                    <p className="page-subtitle">{new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' })}</p>
                </div>
                <Link href={route('receptionist.students.create')} className="btn-primary">
                    <PlusIcon className="w-4 h-4" />
                    Register Student
                </Link>
            </div>

            <div className="grid grid-cols-3 gap-4 mb-6">
                <StatCard label="Total Students" value={stats.total_students} icon={UsersIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
                <StatCard label="New This Month" value={stats.new_this_month} icon={UserGroupIcon}
                          iconBg="bg-emerald-50" iconColor="text-emerald-600" />
                <StatCard label="Total Classes" value={stats.total_classes} icon={BuildingLibraryIcon}
                          iconBg="bg-purple-50" iconColor="text-purple-600" />
            </div>

            {/* Quick Actions */}
            <div className="grid grid-cols-2 gap-4 mb-6">
                <Link href={route('receptionist.students.create')}
                      className="card p-6 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div className="w-12 h-12 bg-indigo-100 rounded-2xl flex items-center justify-center">
                        <PlusIcon className="w-6 h-6 text-indigo-600" />
                    </div>
                    <div>
                        <p className="font-semibold text-gray-900">Register New Student</p>
                        <p className="text-sm text-gray-500">Add a new student admission</p>
                    </div>
                </Link>
                <Link href={route('receptionist.students')}
                      className="card p-6 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div className="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                        <UsersIcon className="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                        <p className="font-semibold text-gray-900">All Students</p>
                        <p className="text-sm text-gray-500">View and search students</p>
                    </div>
                </Link>
            </div>

            {/* Recent */}
            <div className="card">
                <div className="card-header">
                    <p className="card-title">Recent Admissions</p>
                    <Link href={route('receptionist.students')} className="text-xs text-indigo-600 hover:underline font-medium">
                        View all
                    </Link>
                </div>
                <div className="table-wrapper rounded-none">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Gender</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentStudents.map(s => (
                                <tr key={s.id}>
                                    <td>
                                        <div className="flex items-center gap-2">
                                            <div className="avatar-sm bg-indigo-600">{s.full_name?.charAt(0) ?? 'S'}</div>
                                            <span className="font-medium text-gray-900">{s.full_name}</span>
                                        </div>
                                    </td>
                                    <td className="font-mono text-xs text-gray-500">{s.admission_no}</td>
                                    <td>{s.class?.name ?? '—'}</td>
                                    <td className="capitalize">{s.gender}</td>
                                    <td className="text-gray-500 text-xs">
                                        {new Date(s.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' })}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
