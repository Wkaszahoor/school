import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Assignment {
    id: number;
    status: string;
    due_date: string | null;
    test: {
        id: number; title: string; description: string | null;
        duration_minutes: number; passing_score: number; total_marks: number;
    };
    attempt: {
        id: number; percentage: number | null; band_score: number | null; status: string;
    } | null;
}

interface Props extends PageProps { assignments: Assignment[]; }

const statusConfig: Record<string, { label: string; color: string }> = {
    pending:     { label: 'Not Started', color: 'bg-gray-100 text-gray-700' },
    in_progress: { label: 'In Progress', color: 'bg-blue-100 text-blue-700' },
    grading:     { label: 'Awaiting Grading', color: 'bg-amber-100 text-amber-700' },
    completed:   { label: 'Completed', color: 'bg-green-100 text-green-700' },
    expired:     { label: 'Expired', color: 'bg-red-100 text-red-700' },
};

export default function MyTests({ assignments }: Props) {
    return (
        <AppLayout title="My Proficiency Tests">
            <Head title="My Proficiency Tests" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">My Proficiency Tests</h1>
                    <p className="page-subtitle">English proficiency tests assigned to you</p>
                </div>
            </div>

            {assignments.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    {assignments.map(a => {
                        const cfg = statusConfig[a.attempt?.status === 'grading' ? 'grading' : a.status] ?? statusConfig.pending;
                        const canTake = a.status !== 'completed' && a.status !== 'expired' && a.attempt?.status !== 'grading';
                        return (
                            <div key={a.id} className="card flex flex-col gap-4">
                                <div className="flex items-start justify-between gap-2">
                                    <h2 className="font-semibold text-gray-900 flex-1">{a.test.title}</h2>
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap ${cfg.color}`}>
                                        {cfg.label}
                                    </span>
                                </div>

                                {a.test.description && (
                                    <p className="text-sm text-gray-500 line-clamp-2">{a.test.description}</p>
                                )}

                                <div className="grid grid-cols-3 gap-2 text-center text-sm">
                                    <div className="bg-gray-50 rounded-lg p-2">
                                        <p className="font-semibold text-gray-900">{a.test.duration_minutes}</p>
                                        <p className="text-xs text-gray-500">minutes</p>
                                    </div>
                                    <div className="bg-gray-50 rounded-lg p-2">
                                        <p className="font-semibold text-gray-900">{a.test.total_marks}</p>
                                        <p className="text-xs text-gray-500">marks</p>
                                    </div>
                                    <div className="bg-gray-50 rounded-lg p-2">
                                        <p className="font-semibold text-gray-900">{a.test.passing_score}%</p>
                                        <p className="text-xs text-gray-500">to pass</p>
                                    </div>
                                </div>

                                {a.attempt?.percentage != null && (
                                    <div className="p-3 bg-blue-50 rounded-lg text-center">
                                        <p className="text-2xl font-bold text-blue-700">{a.attempt.percentage}%</p>
                                        <p className="text-sm text-blue-600">
                                            Band {a.attempt.band_score} score
                                        </p>
                                    </div>
                                )}

                                {a.due_date && (
                                    <p className="text-xs text-gray-500">
                                        Due: {new Date(a.due_date).toLocaleDateString('en-GB')}
                                    </p>
                                )}

                                <div className="mt-auto">
                                    {a.attempt?.status === 'completed' || a.attempt?.status === 'grading' ? (
                                        <Link href={route('proficiency-tests.result', a.attempt.id)}
                                            className="btn btn-secondary w-full text-center">
                                            {a.attempt.status === 'grading' ? 'View Submission' : 'View Result'}
                                        </Link>
                                    ) : canTake ? (
                                        <Link href={route('proficiency-tests.take', a.id)}
                                            className="btn btn-primary w-full text-center">
                                            {a.status === 'in_progress' ? 'Continue Test' : 'Start Test'}
                                        </Link>
                                    ) : (
                                        <p className="text-center text-sm text-gray-400">Test expired</p>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            ) : (
                <div className="text-center py-16 bg-gray-50 rounded-lg">
                    <p className="text-4xl mb-4">📋</p>
                    <h2 className="text-xl font-semibold text-gray-700 mb-2">No tests assigned yet</h2>
                    <p className="text-gray-500">Your principal will assign proficiency tests here.</p>
                </div>
            )}
        </AppLayout>
    );
}
