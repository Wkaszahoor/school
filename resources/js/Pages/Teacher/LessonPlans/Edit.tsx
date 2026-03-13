import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import type { PageProps, LessonPlan, TeacherAssignment } from '@/types';

interface Props extends PageProps {
    lessonPlan: LessonPlan;
    assignments: TeacherAssignment[];
}

export default function EditLessonPlan({ lessonPlan, assignments }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        class_id:      String(lessonPlan.class_id ?? ''),
        subject_id:    String(lessonPlan.subject_id ?? ''),
        week_starting: lessonPlan.week_starting,
        topic:         lessonPlan.topic,
        objectives:    lessonPlan.objectives,
        resources:     lessonPlan.resources ?? '',
        activities:    lessonPlan.activities ?? '',
        homework:      lessonPlan.homework ?? '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('teacher.lesson-plans.update', lessonPlan.id));
    };

    const uniqueClasses = Array.from(new Map(assignments.map(a => [a.class_id, a.class])).entries())
        .map(([, cls]) => cls!).filter(Boolean);

    const subjectsForClass = assignments
        .filter(a => String(a.class_id) === String(data.class_id))
        .map(a => a.subject!)
        .filter(Boolean);

    return (
        <AppLayout title="Edit Lesson Plan">
            <Head title="Edit Lesson Plan" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('teacher.lesson-plans.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Edit Lesson Plan</h1>
                        <p className="page-subtitle">{lessonPlan.topic}</p>
                    </div>
                </div>
                <Badge color={lessonPlan.status === 'approved' ? 'green' : lessonPlan.status === 'rejected' ? 'red' : 'yellow'}>
                    {lessonPlan.status}
                </Badge>
            </div>

            {lessonPlan.principal_feedback && (
                <div className="alert-warning mb-5">
                    <p className="font-semibold">Principal Feedback</p>
                    <p>{lessonPlan.principal_feedback}</p>
                </div>
            )}

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        <div className="card">
                            <div className="card-header"><p className="card-title">Plan Details</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Class <span className="text-red-500">*</span></label>
                                        <select className="form-select" value={data.class_id}
                                                onChange={e => { setData('class_id', e.target.value); setData('subject_id', ''); }}>
                                            <option value="">Select class…</option>
                                            {uniqueClasses.map(c => (
                                                <option key={c.id} value={c.id}>
                                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.class_id && <p className="form-error">{errors.class_id}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Subject <span className="text-red-500">*</span></label>
                                        <select className="form-select" value={data.subject_id}
                                                onChange={e => setData('subject_id', e.target.value)}
                                                disabled={!data.class_id}>
                                            <option value="">Select subject…</option>
                                            {subjectsForClass.map(s => (
                                                <option key={s.id} value={s.id}>{s.full_name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Week Starting <span className="text-red-500">*</span></label>
                                        <input type="date" className="form-input" value={data.week_starting}
                                               onChange={e => setData('week_starting', e.target.value)} required />
                                    </div>
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Topic <span className="text-red-500">*</span></label>
                                    <input className="form-input" value={data.topic}
                                           onChange={e => setData('topic', e.target.value)}
                                           placeholder="Lesson topic or chapter title" required />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Learning Objectives <span className="text-red-500">*</span></label>
                                    <textarea className="form-textarea" rows={4} value={data.objectives}
                                              onChange={e => setData('objectives', e.target.value)}
                                              placeholder="What students will learn…" required />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Resources &amp; Materials</label>
                                    <textarea className="form-textarea" rows={2} value={data.resources}
                                              onChange={e => setData('resources', e.target.value)}
                                              placeholder="Books, worksheets, equipment…" />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Classroom Activities</label>
                                    <textarea className="form-textarea" rows={3} value={data.activities}
                                              onChange={e => setData('activities', e.target.value)}
                                              placeholder="Teaching methods, group work…" />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Homework / Assessment</label>
                                    <textarea className="form-textarea" rows={2} value={data.homework}
                                              onChange={e => setData('homework', e.target.value)}
                                              placeholder="Any homework or assessment tasks…" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div className="card">
                            <div className="card-header"><p className="card-title">Resubmit</p></div>
                            <div className="card-body space-y-3">
                                <p className="text-sm text-gray-500">After saving, the plan will be resubmitted for principal review.</p>
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                    ) : 'Save & Resubmit'}
                                </button>
                                <Link href={route('teacher.lesson-plans.index')} className="btn-secondary w-full text-center">
                                    Cancel
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
