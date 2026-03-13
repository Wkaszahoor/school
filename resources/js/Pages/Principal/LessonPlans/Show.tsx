import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckIcon, XMarkIcon, ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, LessonPlan } from '@/types';

interface Props extends PageProps {
    lessonPlan: LessonPlan;
}

const STATUS_COLOR = { approved: 'green' as const, rejected: 'red' as const, pending: 'yellow' as const };

export default function LessonPlanShow({ lessonPlan }: Props) {
    const approveForm = useForm({ feedback: '' });
    const rejectForm = useForm({ feedback: '' });
    const [action, setAction] = useState<'approve' | 'reject' | null>(null);

    const handleApprove = (e: React.FormEvent) => {
        e.preventDefault();
        approveForm.post(route('principal.lesson-plans.approve', lessonPlan.id), {
            onSuccess: () => setAction(null),
        });
    };

    const handleReject = (e: React.FormEvent) => {
        e.preventDefault();
        rejectForm.post(route('principal.lesson-plans.reject', lessonPlan.id), {
            onSuccess: () => setAction(null),
        });
    };

    return (
        <AppLayout title="Review Lesson Plan">
            <Head title="Review Lesson Plan" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('principal.lesson-plans.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Lesson Plan Review</h1>
                        <p className="page-subtitle">{lessonPlan.teacher?.user?.name} · {lessonPlan.class?.name} · {lessonPlan.subject?.name}</p>
                    </div>
                </div>
                <Badge color={STATUS_COLOR[lessonPlan.status] ?? 'gray'}>
                    {lessonPlan.status}
                </Badge>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Main Content */}
                <div className="lg:col-span-2 space-y-5">
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Plan Details</p>
                        </div>
                        <div className="card-body space-y-5">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Topic</p>
                                <p className="text-gray-900 font-medium">{lessonPlan.topic}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Learning Objectives</p>
                                <p className="text-gray-700 whitespace-pre-wrap">{lessonPlan.objectives}</p>
                            </div>
                            {lessonPlan.resources && (
                                <div>
                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Resources</p>
                                    <p className="text-gray-700 whitespace-pre-wrap">{lessonPlan.resources}</p>
                                </div>
                            )}
                            {lessonPlan.activities && (
                                <div>
                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Activities</p>
                                    <p className="text-gray-700 whitespace-pre-wrap">{lessonPlan.activities}</p>
                                </div>
                            )}
                            {lessonPlan.homework && (
                                <div>
                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Homework</p>
                                    <p className="text-gray-700 whitespace-pre-wrap">{lessonPlan.homework}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {lessonPlan.principal_feedback && (
                        <div className="card border-l-4 border-indigo-400">
                            <div className="card-body">
                                <p className="text-xs font-semibold text-indigo-600 uppercase tracking-wider mb-1">Principal Feedback</p>
                                <p className="text-gray-700">{lessonPlan.principal_feedback}</p>
                            </div>
                        </div>
                    )}
                </div>

                {/* Sidebar */}
                <div className="space-y-4">
                    <div className="card">
                        <div className="card-header"><p className="card-title">Info</p></div>
                        <div className="card-body space-y-3">
                            {[
                                { label: 'Teacher',       value: lessonPlan.teacher?.user?.name ?? '—' },
                                { label: 'Class',         value: lessonPlan.class?.name ?? '—' },
                                { label: 'Subject',       value: lessonPlan.subject?.name ?? '—' },
                                { label: 'Week Starting', value: new Date(lessonPlan.week_starting).toLocaleDateString('en-GB') },
                            ].map(({ label, value }) => (
                                <div key={label} className="flex justify-between items-start">
                                    <span className="text-xs font-semibold text-gray-500 uppercase tracking-wider">{label}</span>
                                    <span className="text-sm text-gray-900 text-right">{value}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {lessonPlan.status === 'pending' && (
                        <div className="card">
                            <div className="card-header"><p className="card-title">Take Action</p></div>
                            <div className="card-body space-y-3">
                                {action === null && (
                                    <div className="flex flex-col gap-2">
                                        <button onClick={() => setAction('approve')} className="btn-success w-full">
                                            <CheckIcon className="w-4 h-4" /> Approve
                                        </button>
                                        <button onClick={() => setAction('reject')} className="btn-danger w-full">
                                            <XMarkIcon className="w-4 h-4" /> Return for Revision
                                        </button>
                                    </div>
                                )}
                                {action === 'approve' && (
                                    <form onSubmit={handleApprove} className="space-y-3">
                                        <div className="form-group">
                                            <label className="form-label">Feedback (optional)</label>
                                            <textarea className="form-textarea" rows={3}
                                                      value={approveForm.data.feedback}
                                                      onChange={e => approveForm.setData('feedback', e.target.value)}
                                                      placeholder="Any commendations or suggestions…" />
                                        </div>
                                        <div className="flex gap-2">
                                            <button type="button" onClick={() => setAction(null)} className="btn-secondary flex-1">Cancel</button>
                                            <button type="submit" disabled={approveForm.processing} className="btn-success flex-1">
                                                Confirm Approve
                                            </button>
                                        </div>
                                    </form>
                                )}
                                {action === 'reject' && (
                                    <form onSubmit={handleReject} className="space-y-3">
                                        <div className="form-group">
                                            <label className="form-label">Feedback <span className="text-red-500">*</span></label>
                                            <textarea className="form-textarea" rows={3}
                                                      value={rejectForm.data.feedback}
                                                      onChange={e => rejectForm.setData('feedback', e.target.value)}
                                                      placeholder="What needs to be improved?" required />
                                        </div>
                                        <div className="flex gap-2">
                                            <button type="button" onClick={() => setAction(null)} className="btn-secondary flex-1">Cancel</button>
                                            <button type="submit" disabled={rejectForm.processing} className="btn-danger flex-1">
                                                Return
                                            </button>
                                        </div>
                                    </form>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
