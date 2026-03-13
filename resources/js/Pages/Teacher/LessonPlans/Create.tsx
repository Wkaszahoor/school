import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, TeacherAssignment } from '@/types';

interface Props extends PageProps {
    assignments: TeacherAssignment[];
}

export default function CreateLessonPlan({ assignments }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        class_id:      '',
        subject_id:    '',
        week_starting: '',
        topic:         '',
        objectives:    '',
        resources:     '',
        activities:    '',
        homework:      '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('teacher.lesson-plans.store'));
    };

    const uniqueClasses = Array.from(new Map(assignments.map(a => [a.class_id, a.class])).entries())
        .map(([, cls]) => cls!).filter(Boolean);

    const subjectsForClass = assignments
        .filter(a => String(a.class_id) === String(data.class_id))
        .map(a => a.subject!)
        .filter(Boolean);

    return (
        <AppLayout title="New Lesson Plan">
            <Head title="New Lesson Plan" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('teacher.lesson-plans.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">New Lesson Plan</h1>
                        <p className="page-subtitle">Submit for principal review</p>
                    </div>
                </div>
            </div>

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
                                              placeholder="What students will be able to do after this lesson…" required />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Resources & Materials</label>
                                    <textarea className="form-textarea" rows={2} value={data.resources}
                                              onChange={e => setData('resources', e.target.value)}
                                              placeholder="Books, worksheets, equipment…" />
                                </div>

                                <div className="form-group">
                                    <label className="form-label">Classroom Activities</label>
                                    <textarea className="form-textarea" rows={3} value={data.activities}
                                              onChange={e => setData('activities', e.target.value)}
                                              placeholder="Teaching methods, group work, presentations…" />
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
                            <div className="card-header"><p className="card-title">Submit Plan</p></div>
                            <div className="card-body space-y-4">
                                <div className="bg-indigo-50 rounded-xl p-4 text-sm text-indigo-700">
                                    <p className="font-semibold mb-1">Review Process</p>
                                    <p className="text-indigo-600">After submission, your lesson plan will be reviewed by the principal. You'll be able to edit it if returned for revision.</p>
                                </div>
                                <div className="flex flex-col gap-2">
                                    <button type="submit" disabled={processing} className="btn-primary w-full">
                                        {processing ? (
                                            <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Submitting…</>
                                        ) : 'Submit for Review'}
                                    </button>
                                    <Link href={route('teacher.lesson-plans.index')} className="btn-secondary w-full text-center">
                                        Cancel
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
