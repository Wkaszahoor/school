import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { EyeIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, LessonPlan } from '@/types';

interface Props extends PageProps {
    plans: PaginatedData<LessonPlan>;
}

const STATUS_COLOR = { approved: 'green' as const, rejected: 'red' as const, pending: 'yellow' as const };

export default function LessonPlansIndex({ plans }: Props) {
    return (
        <AppLayout title="Lesson Plans">
            <Head title="Lesson Plans" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Lesson Plans</h1>
                    <p className="page-subtitle">{plans.total} submitted</p>
                </div>
                <div className="flex gap-2">
                    {['all', 'pending', 'approved', 'rejected'].map(s => (
                        <button
                            key={s}
                            onClick={() => router.get(route('principal.lesson-plans.index'), { status: s === 'all' ? '' : s }, { preserveState: true, replace: true })}
                            className="btn-secondary btn-sm capitalize"
                        >
                            {s}
                        </button>
                    ))}
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Teacher</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Week Starting</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {plans.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8}>
                                        <div className="empty-state py-12">
                                            <DocumentTextIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No lesson plans found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : plans.data.map(plan => (
                                <tr key={plan.id}>
                                    <td className="font-medium text-gray-900">{plan.teacher?.user?.name ?? '—'}</td>
                                    <td>{plan.class?.name ?? '—'}</td>
                                    <td>{plan.subject?.name ?? '—'}</td>
                                    <td>{new Date(plan.week_starting).toLocaleDateString('en-GB')}</td>
                                    <td>
                                        <span className="block max-w-xs truncate" title={plan.topic}>{plan.topic}</span>
                                    </td>
                                    <td>
                                        <Badge color={STATUS_COLOR[plan.status] ?? 'gray'}>
                                            {plan.status}
                                        </Badge>
                                    </td>
                                    <td className="text-gray-500 text-xs">—</td>
                                    <td>
                                        <div className="flex items-center justify-end">
                                            <Link href={route('principal.lesson-plans.show', plan.id)}
                                                  className="btn-primary btn-sm">
                                                <EyeIcon className="w-3.5 h-3.5" />
                                                Review
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={plans} />
                </div>
            </div>
        </AppLayout>
    );
}
