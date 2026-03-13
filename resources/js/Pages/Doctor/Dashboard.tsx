import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { HeartIcon, UserGroupIcon, PlusIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import StatCard from '@/Components/StatCard';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import type { PageProps, SickRecord, Student } from '@/types';

interface DashboardProps extends PageProps {
    stats: {
        today_visits: number;
        this_month_visits: number;
        referred_this_month: number;
        total_students: number;
    };
    recentRecords: SickRecord[];
}

export default function DoctorDashboard({ stats, recentRecords }: DashboardProps) {
    return (
        <AppLayout title="Medical Dashboard">
            <Head title="Medical Dashboard" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Medical Dashboard</h1>
                    <p className="page-subtitle">{new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' })}</p>
                </div>
                <a href={route('doctor.records')} className="btn-primary">
                    <PlusIcon className="w-4 h-4" />
                    New Record
                </a>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                <StatCard label="Today's Visits" value={stats.today_visits} icon={HeartIcon}
                          iconBg="bg-pink-50" iconColor="text-pink-600" />
                <StatCard label="This Month" value={stats.this_month_visits} icon={HeartIcon}
                          iconBg="bg-red-50" iconColor="text-red-600" />
                <StatCard label="Referred" value={stats.referred_this_month} icon={ExclamationTriangleIcon}
                          iconBg="bg-amber-50" iconColor="text-amber-600" />
                <StatCard label="Total Students" value={stats.total_students} icon={UserGroupIcon}
                          iconBg="bg-blue-50" iconColor="text-blue-600" />
            </div>

            <div className="card">
                <div className="card-header">
                    <p className="card-title">Recent Medical Records</p>
                    <a href={route('doctor.records')} className="text-xs text-indigo-600 hover:underline font-medium">View all</a>
                </div>
                <div className="table-wrapper rounded-none">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Symptoms</th>
                                <th>Diagnosis</th>
                                <th>Referred</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            {recentRecords.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state py-10">
                                            <HeartIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No records yet</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : recentRecords.map(r => (
                                <tr key={r.id}>
                                    <td className="font-medium text-gray-900">{r.student?.name ?? '—'}</td>
                                    <td>{r.student?.class?.name ?? '—'}</td>
                                    <td className="max-w-xs"><span className="truncate block">{r.symptoms}</span></td>
                                    <td>{r.diagnosis ?? <span className="text-gray-400">—</span>}</td>
                                    <td>
                                        <Badge color={r.referred_to_hospital ? 'red' : 'green'}>
                                            {r.referred_to_hospital ? 'Yes' : 'No'}
                                        </Badge>
                                    </td>
                                    <td className="text-gray-500">{new Date(r.visit_date).toLocaleDateString('en-GB')}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
