import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Breakdown {
    score: number; max: number; detail: string;
}
interface Teacher {
    id: number; name: string; email: string;
    total_score: number; performance: string; rank: number;
    classes_taught: string[];
    proficiency_test: { percentage: number; band_score: number; date: string } | null;
    breakdown: {
        lesson_plans: Breakdown;
        weekly_tests: Breakdown;
        lectures: Breakdown;
        resources: Breakdown;
        student_performance: Breakdown;
    };
}
interface Props extends PageProps {
    teachers: Teacher[];
    academicYear: string;
    years: string[];
}

const PERF_CONFIG: Record<string, { color: string; bg: string; emoji: string }> = {
    'Excellent':         { color: 'text-green-800',  bg: 'bg-green-100',  emoji: '🌟' },
    'Very Good':         { color: 'text-blue-800',   bg: 'bg-blue-100',   emoji: '👍' },
    'Good':              { color: 'text-teal-800',   bg: 'bg-teal-100',   emoji: '✅' },
    'Satisfactory':      { color: 'text-amber-800',  bg: 'bg-amber-100',  emoji: '⚠️' },
    'Needs Improvement': { color: 'text-red-800',    bg: 'bg-red-100',    emoji: '🔴' },
};

const BREAKDOWN_LABELS: Record<string, string> = {
    lesson_plans:        '📋 Lesson Plans',
    weekly_tests:        '📝 Weekly Tests',
    lectures:            '🎤 Lectures Delivered',
    resources:           '📁 Teaching Resources',
    student_performance: '🎓 Student Performance',
};

function ScoreBar({ score, max }: { score: number; max: number }) {
    const pct = Math.round((score / max) * 100);
    return (
        <div className="flex items-center gap-2">
            <div className="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                <div
                    className={`h-2 rounded-full transition-all ${pct >= 80 ? 'bg-green-500' : pct >= 50 ? 'bg-amber-500' : 'bg-red-500'}`}
                    style={{ width: `${pct}%` }} />
            </div>
            <span className="text-xs font-semibold text-gray-700 w-14 text-right">
                {score}/{max}
            </span>
        </div>
    );
}

export default function TeacherPerformance({ teachers, academicYear, years }: Props) {
    const [expanded, setExpanded] = useState<number | null>(null);
    const [search, setSearch] = useState('');
    const [perfFilter, setPerfFilter] = useState('');

    const filtered = teachers.filter(t =>
        t.name.toLowerCase().includes(search.toLowerCase()) &&
        (!perfFilter || t.performance === perfFilter)
    );

    const top3 = [...teachers].slice(0, 3);

    return (
        <AppLayout title="Teacher Performance">
            <Head title="Teacher Performance Analytics" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Teacher Performance Analytics</h1>
                    <p className="page-subtitle">Scored on lesson plans, tests, lectures, resources, and student results</p>
                </div>
                <select value={academicYear}
                    onChange={e => router.get(route('principal.analytics.teacher-performance'), { academic_year: e.target.value })}
                    className="form-select">
                    {years.map(y => <option key={y} value={y}>{y}</option>)}
                    <option value={academicYear}>{academicYear}</option>
                </select>
            </div>

            {/* Top 3 Podium */}
            {top3.length > 0 && (
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    {top3.map((t, i) => {
                        const cfg = PERF_CONFIG[t.performance] ?? PERF_CONFIG['Satisfactory'];
                        const medals = ['🥇', '🥈', '🥉'];
                        return (
                            <div key={t.id} className={`card text-center border-2 ${i === 0 ? 'border-yellow-400' : i === 1 ? 'border-gray-400' : 'border-amber-700'}`}>
                                <p className="text-4xl mb-2">{medals[i]}</p>
                                <p className="font-bold text-gray-900">{t.name}</p>
                                <p className="text-xs text-gray-500 mb-3">{t.email}</p>
                                <p className="text-4xl font-bold text-blue-700">{t.total_score}<span className="text-base font-normal text-gray-400">/100</span></p>
                                <span className={`inline-block mt-2 px-3 py-1 rounded-full text-xs font-medium ${cfg.bg} ${cfg.color}`}>
                                    {cfg.emoji} {t.performance}
                                </span>
                                {t.proficiency_test && (
                                    <p className="text-xs text-purple-700 mt-2 font-medium">
                                        IELTS Band: {t.proficiency_test.band_score} · {t.proficiency_test.percentage}%
                                    </p>
                                )}
                            </div>
                        );
                    })}
                </div>
            )}

            {/* Filters */}
            <div className="card mb-6">
                <div className="flex flex-wrap gap-4 items-end">
                    <div className="flex-1 min-w-48">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Search Teacher</label>
                        <input type="text" value={search} onChange={e => setSearch(e.target.value)}
                            className="form-input" placeholder="Name..." />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Performance</label>
                        <select value={perfFilter} onChange={e => setPerfFilter(e.target.value)} className="form-select">
                            <option value="">All</option>
                            {Object.keys(PERF_CONFIG).map(p => <option key={p} value={p}>{p}</option>)}
                        </select>
                    </div>
                </div>
            </div>

            {/* Table */}
            <div className="space-y-3">
                {filtered.length === 0 && (
                    <div className="card text-center py-10 text-gray-500">No teachers found.</div>
                )}
                {filtered.map(teacher => {
                    const cfg = PERF_CONFIG[teacher.performance] ?? PERF_CONFIG['Satisfactory'];
                    const isOpen = expanded === teacher.id;
                    const barPct = Math.round(teacher.total_score);

                    return (
                        <div key={teacher.id} className="card">
                            {/* Collapsed Row */}
                            <div className="flex items-center gap-4 cursor-pointer" onClick={() => setExpanded(isOpen ? null : teacher.id)}>
                                {/* Rank */}
                                <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-bold text-gray-600 flex-shrink-0">
                                    {teacher.rank}
                                </div>

                                {/* Name */}
                                <div className="flex-1 min-w-0">
                                    <p className="font-semibold text-gray-900">{teacher.name}</p>
                                    <p className="text-xs text-gray-500">{teacher.classes_taught.length > 0 ? teacher.classes_taught.join(' · ') : 'No classes'}</p>
                                </div>

                                {/* Score bar */}
                                <div className="hidden sm:flex items-center gap-3 flex-1 max-w-xs">
                                    <div className="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                                        <div className={`h-3 rounded-full transition-all ${
                                            barPct >= 70 ? 'bg-green-500' : barPct >= 50 ? 'bg-amber-500' : 'bg-red-500'
                                        }`} style={{ width: `${barPct}%` }} />
                                    </div>
                                    <span className="font-bold text-gray-800 w-14 text-right">{teacher.total_score}/100</span>
                                </div>

                                {/* Badge */}
                                <span className={`px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap ${cfg.bg} ${cfg.color}`}>
                                    {cfg.emoji} {teacher.performance}
                                </span>

                                {/* Proficiency */}
                                {teacher.proficiency_test && (
                                    <span className="hidden lg:inline-flex px-2 py-0.5 bg-purple-100 text-purple-800 rounded-full text-xs font-medium whitespace-nowrap">
                                        Band {teacher.proficiency_test.band_score}
                                    </span>
                                )}

                                <svg className={`w-5 h-5 text-gray-400 transition-transform flex-shrink-0 ${isOpen ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>

                            {/* Expanded Detail */}
                            {isOpen && (
                                <div className="mt-5 pt-5 border-t border-gray-100">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                                        {Object.entries(teacher.breakdown).map(([key, val]) => (
                                            <div key={key} className="bg-gray-50 rounded-lg p-3">
                                                <p className="text-xs font-medium text-gray-700 mb-2">{BREAKDOWN_LABELS[key]}</p>
                                                <ScoreBar score={val.score} max={val.max} />
                                                <p className="text-xs text-gray-500 mt-2">{val.detail}</p>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Proficiency Test Detail */}
                                    {teacher.proficiency_test ? (
                                        <div className="p-3 bg-purple-50 rounded-lg text-sm">
                                            <p className="font-medium text-purple-800 mb-1">📋 English Proficiency Test Result</p>
                                            <div className="flex gap-6">
                                                <span className="text-purple-700">Score: <strong>{teacher.proficiency_test.percentage}%</strong></span>
                                                <span className="text-purple-700">Band: <strong>{teacher.proficiency_test.band_score}</strong></span>
                                                <span className="text-purple-500 text-xs">
                                                    Taken: {new Date(teacher.proficiency_test.date).toLocaleDateString('en-GB')}
                                                </span>
                                            </div>
                                        </div>
                                    ) : (
                                        <p className="text-xs text-gray-400 italic">No English proficiency test completed yet.</p>
                                    )}

                                    {/* Score explanation */}
                                    <div className="mt-4 p-3 bg-blue-50 rounded-lg text-xs text-blue-700">
                                        <strong>Score Breakdown:</strong> Lesson Plans (25pts) + Weekly Tests (20pts) + Lectures Delivered (20pts) + Teaching Resources (15pts) + Student Performance (20pts) = 100 pts
                                    </div>
                                </div>
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Legend */}
            <div className="card mt-6">
                <p className="text-sm font-semibold text-gray-700 mb-3">Performance Scale</p>
                <div className="flex flex-wrap gap-3">
                    {Object.entries(PERF_CONFIG).map(([label, cfg]) => (
                        <span key={label} className={`px-3 py-1.5 rounded-full text-xs font-medium ${cfg.bg} ${cfg.color}`}>
                            {cfg.emoji} {label}
                        </span>
                    ))}
                </div>
                <div className="mt-3 grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs text-gray-500">
                    <span>Excellent: 85-100</span>
                    <span>Very Good: 70-84</span>
                    <span>Good: 55-69</span>
                    <span>Satisfactory: 40-54</span>
                    <span>Needs Improvement: 0-39</span>
                </div>
            </div>
        </AppLayout>
    );
}
