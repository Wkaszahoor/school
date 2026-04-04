import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    test: {
        id: number; title: string; description: string | null;
        duration_minutes: number; passing_score: number; total_marks: number; status: string;
        sections: Array<{
            id: number; name: string; marks: number;
            questions: Array<{ id: number; question_text: string; question_type: string; marks: number }>;
        }>;
        assignments: Array<{
            id: number; status: string; due_date: string | null;
            user: { id: number; name: string; email: string; role: string };
            attempt: {
                id: number; percentage: number | null; band_score: number | null;
                status: string; completed_at: string | null;
            } | null;
        }>;
    };
    stats: { total_assigned: number; completed: number; pending: number; avg_score: number };
}

const bandColor = (band: number | null) => {
    if (!band) return 'text-gray-500';
    if (band >= 7) return 'text-green-700 font-bold';
    if (band >= 5) return 'text-amber-700 font-semibold';
    return 'text-red-700 font-semibold';
};

export default function ProficiencyTestShow({ test, stats }: Props) {
    const { flash } = usePage().props as any;
    const [activeTab, setActiveTab] = useState<'overview' | 'results' | 'grading'>('overview');

    const ungraded = test.assignments.filter(a =>
        a.attempt?.status === 'grading'
    );

    const removeAssignment = (userId: number, name: string) => {
        if (!confirm(`Remove assignment for ${name}?`)) return;
        router.delete(route('principal.professional-development.proficiency-tests.remove-assignment', [test.id, userId]));
    };

    return (
        <AppLayout title={test.title}>
            <Head title={test.title} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">{test.title}</h1>
                    <p className="page-subtitle">{test.duration_minutes} min · {test.total_marks} marks · Pass: {test.passing_score}%</p>
                </div>
                <div className="flex gap-2">
                    <Link href={route('principal.professional-development.proficiency-tests.assign', test.id)} className="btn btn-primary">
                        Assign Test
                    </Link>
                    <Link href={route('principal.professional-development.proficiency-tests.edit', test.id)} className="btn btn-secondary">
                        Edit
                    </Link>
                    <Link href={route('principal.professional-development.proficiency-tests.index')} className="btn btn-secondary">
                        Back
                    </Link>
                </div>
            </div>

            {flash?.success && (
                <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">{flash.success}</div>
            )}

            {/* Stats */}
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                {[
                    { label: 'Assigned', value: stats.total_assigned, color: 'blue' },
                    { label: 'Completed', value: stats.completed, color: 'green' },
                    { label: 'Pending', value: stats.pending, color: 'amber' },
                    { label: 'Avg Score', value: stats.avg_score ? `${Math.round(stats.avg_score)}%` : '—', color: 'purple' },
                ].map(s => (
                    <div key={s.label} className="card text-center">
                        <p className={`text-3xl font-bold text-${s.color}-600`}>{s.value}</p>
                        <p className="text-sm text-gray-500 mt-1">{s.label}</p>
                    </div>
                ))}
            </div>

            {ungraded.length > 0 && (
                <div className="mb-6 p-4 bg-amber-50 border border-amber-300 rounded-lg flex items-center gap-3">
                    <span className="text-amber-600 font-bold text-lg">✍️</span>
                    <div>
                        <p className="font-semibold text-amber-800">{ungraded.length} submission(s) awaiting essay grading</p>
                        <p className="text-sm text-amber-700">Go to the Grading tab to review and score essay answers.</p>
                    </div>
                </div>
            )}

            {/* Tabs */}
            <div className="border-b border-gray-200 mb-6">
                {(['overview', 'results', 'grading'] as const).map(tab => (
                    <button key={tab} onClick={() => setActiveTab(tab)}
                        className={`px-5 py-3 text-sm font-medium border-b-2 -mb-px capitalize transition ${
                            activeTab === tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                        }`}>
                        {tab}{tab === 'grading' && ungraded.length > 0 ? ` (${ungraded.length})` : ''}
                    </button>
                ))}
            </div>

            {/* Overview Tab */}
            {activeTab === 'overview' && (
                <div className="space-y-4">
                    {test.sections.map(section => (
                        <div key={section.id} className="card">
                            <div className="flex items-center justify-between mb-3">
                                <h3 className="font-semibold text-gray-900">{section.name}</h3>
                                <span className="text-sm text-gray-500">{section.marks} marks · {section.questions.length} questions</span>
                            </div>
                            <div className="space-y-2">
                                {section.questions.map((q, qi) => (
                                    <div key={q.id} className="flex gap-3 text-sm p-2 bg-gray-50 rounded">
                                        <span className="text-gray-400 font-medium w-5">{qi + 1}.</span>
                                        <span className="flex-1 text-gray-800">{q.question_text}</span>
                                        <span className="text-xs text-gray-500 capitalize whitespace-nowrap">{q.question_type.replace('_', ' ')}</span>
                                        <span className="text-xs text-blue-600 whitespace-nowrap">{q.marks}m</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Results Tab */}
            {activeTab === 'results' && (
                <div className="card overflow-hidden">
                    {test.assignments.length > 0 ? (
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b">
                                <tr>
                                    <th className="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                                    <th className="text-left py-3 px-4 font-semibold text-gray-700">Role</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Score</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Band</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Completed</th>
                                    <th className="text-right py-3 px-4 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {test.assignments.map((a, idx) => (
                                    <tr key={a.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                        <td className="py-3 px-4">
                                            <p className="font-medium text-gray-900">{a.user.name}</p>
                                            <p className="text-xs text-gray-500">{a.user.email}</p>
                                        </td>
                                        <td className="py-3 px-4 capitalize text-gray-600">{a.user.role}</td>
                                        <td className="text-center py-3 px-4">
                                            <span className={`px-2 py-0.5 rounded-full text-xs font-medium capitalize ${
                                                a.status === 'completed' ? 'bg-green-100 text-green-800' :
                                                a.status === 'in_progress' ? 'bg-blue-100 text-blue-800' :
                                                a.status === 'grading' ? 'bg-amber-100 text-amber-800' :
                                                'bg-gray-100 text-gray-700'
                                            }`}>{a.status.replace('_', ' ')}</span>
                                        </td>
                                        <td className="text-center py-3 px-4 text-gray-700">
                                            {a.attempt?.percentage != null ? `${a.attempt.percentage}%` : '—'}
                                        </td>
                                        <td className={`text-center py-3 px-4 ${bandColor(a.attempt?.band_score ?? null)}`}>
                                            {a.attempt?.band_score ?? '—'}
                                        </td>
                                        <td className="text-center py-3 px-4 text-xs text-gray-500">
                                            {a.attempt?.completed_at
                                                ? new Date(a.attempt.completed_at).toLocaleDateString('en-GB')
                                                : '—'}
                                        </td>
                                        <td className="text-right py-3 px-4">
                                            <div className="flex gap-1 justify-end">
                                                {a.attempt && (
                                                    <a href={route('proficiency-tests.result', a.attempt.id)}
                                                        className="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">
                                                        View
                                                    </a>
                                                )}
                                                <button onClick={() => removeAssignment(a.user.id, a.user.name)}
                                                    className="px-2 py-1 bg-red-100 text-red-600 rounded text-xs hover:bg-red-200">
                                                    Remove
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    ) : (
                        <p className="text-center text-gray-500 py-8">No one assigned yet.</p>
                    )}
                </div>
            )}

            {/* Grading Tab */}
            {activeTab === 'grading' && (
                <div className="space-y-4">
                    {ungraded.length === 0 ? (
                        <div className="card text-center py-8 text-gray-500">
                            <p className="text-2xl mb-2">✅</p>
                            <p>No pending essay grading.</p>
                        </div>
                    ) : (
                        ungraded.map(a => (
                            <div key={a.id} className="card">
                                <div className="flex items-center justify-between mb-4">
                                    <div>
                                        <p className="font-semibold text-gray-900">{a.user.name}</p>
                                        <p className="text-sm text-gray-500">{a.user.email}</p>
                                    </div>
                                    {a.attempt && (
                                        <a href={route('proficiency-tests.result', a.attempt.id)}
                                            className="btn btn-primary text-sm">
                                            Grade Essays →
                                        </a>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}
        </AppLayout>
    );
}
