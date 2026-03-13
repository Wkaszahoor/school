import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { PlusIcon, DocumentTextIcon, PencilSquareIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, LessonPlan } from '@/types';

interface Props extends PageProps {
    plans: PaginatedData<LessonPlan>;
}

const STATUS_COLOR = { approved: 'green' as const, rejected: 'red' as const, pending: 'yellow' as const };

export default function TeacherLessonPlansIndex({ plans }: Props) {
    return (
        <AppLayout title="Lesson Plans">
            <Head title="My Lesson Plans" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Lesson Plans</h1>
                    <p className="page-subtitle">{plans.total} plans submitted</p>
                </div>
                <Link href={route('teacher.lesson-plans.create')} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> New Plan
                </Link>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Week Starting</th>
                                <th>Topic</th>
                                <th>Status</th>
                                <th>Feedback</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {plans.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state py-12">
                                            <DocumentTextIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No lesson plans yet</p>
                                            <Link href={route('teacher.lesson-plans.create')} className="btn-primary btn-sm mt-3">
                                                Submit First Plan
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            ) : plans.data.map(plan => (
                                <tr key={plan.id}>
                                    <td>{plan.class?.name}</td>
                                    <td>{plan.subject?.name}</td>
                                    <td>{new Date(plan.week_starting).toLocaleDateString('en-GB')}</td>
                                    <td><span className="block max-w-xs truncate">{plan.topic}</span></td>
                                    <td>
                                        <Badge color={STATUS_COLOR[plan.status] ?? 'gray'}>{plan.status}</Badge>
                                    </td>
                                    <td>
                                        {plan.principal_feedback
                                            ? <span className="text-xs text-gray-500 italic truncate block max-w-xs">"{plan.principal_feedback}"</span>
                                            : <span className="text-gray-300">—</span>
                                        }
                                    </td>
                                    <td>
                                        <div className="flex items-center justify-end">
                                            {plan.status !== 'approved' && (
                                                <Link href={route('teacher.lesson-plans.edit', plan.id)}
                                                      className="btn-ghost btn-icon btn-sm">
                                                    <PencilSquareIcon className="w-4 h-4" />
                                                </Link>
                                            )}
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
