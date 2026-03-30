import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface SubjectScore { subject_name: string; subject_code: string; exam_pct: number; weekly_pct: number; composite: number; }
interface StudentData {
    id: number; name: string; admission_no: string; position: number;
    overall: number; is_weak: boolean; weak_subjects: string[];
    subjects: Record<number, SubjectScore>;
}
interface StreamRec {
    student_id: number; name: string; admission_no: string;
    recommended: string; score: number; confidence: 'high' | 'medium' | 'low';
    all_scores: Record<string, number>; overall: number;
}
interface SubjectAvg { subject_name: string; subject_code: string; avg: number; min: number; max: number; below_50: number; total: number; }
interface Analytics {
    class: string; class_num: number; total_students: number;
    students: StudentData[]; top3: StudentData[]; weak: StudentData[];
    stream_recommendations: StreamRec[]; subject_averages: SubjectAvg[];
    academic_year: string;
}
interface Props extends PageProps {
    classes: { id: number; class: string; section: string }[];
    selectedId: number | null;
    academicYear: string;
    years: string[];
    analytics: Analytics | null;
}

const MEDAL = ['🥇', '🥈', '🥉'];
const STREAM_COLORS: Record<string, string> = {
    'Pre-Medical':     'bg-green-100 text-green-800 border-green-300',
    'Pre-Engineering': 'bg-blue-100 text-blue-800 border-blue-300',
    'ICS':             'bg-purple-100 text-purple-800 border-purple-300',
    'Arts/FA':         'bg-amber-100 text-amber-800 border-amber-300',
    'Commerce':        'bg-pink-100 text-pink-800 border-pink-300',
};
const CONFIDENCE_BADGE: Record<string, string> = {
    high:   'bg-green-500 text-white',
    medium: 'bg-amber-500 text-white',
    low:    'bg-gray-400 text-white',
};

export default function StudentResultsAnalytics({ classes, selectedId, academicYear, years, analytics }: Props) {
    const [tab, setTab] = useState<'top3' | 'weak' | 'stream' | 'subjects' | 'all'>('top3');
    const [expandedStudent, setExpandedStudent] = useState<number | null>(null);
    const [selectedStream, setSelectedStream] = useState('');

    const filter = (classId: string | number, year: string) => {
        router.get(route('principal.analytics.student-results'), { class_id: classId, academic_year: year }, { preserveScroll: true });
    };

    const streamGroups = analytics ? analytics.stream_recommendations.reduce((acc, r) => {
        (acc[r.recommended] = acc[r.recommended] || []).push(r);
        return acc;
    }, {} as Record<string, StreamRec[]>) : {};

    const filteredStream = selectedStream
        ? (streamGroups[selectedStream] || [])
        : analytics?.stream_recommendations ?? [];

    return (
        <AppLayout title="Student Results Analytics">
            <Head title="Student Results Analytics" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Student Results Analytics</h1>
                    <p className="page-subtitle">Top performers, weak students, and stream recommendations</p>
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-6">
                <div className="flex flex-wrap gap-4 items-end">
                    <div className="flex-1 min-w-48">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Select Class</label>
                        <select defaultValue={selectedId ?? ''}
                            onChange={e => e.target.value && filter(e.target.value, academicYear)}
                            className="form-select">
                            <option value="">-- Choose a class --</option>
                            {classes.map(c => (
                                <option key={c.id} value={c.id}>{c.class} {c.section}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Academic Year</label>
                        <select value={academicYear} onChange={e => selectedId && filter(selectedId, e.target.value)} className="form-select">
                            {years.map(y => <option key={y} value={y}>{y}</option>)}
                            <option value={academicYear}>{academicYear}</option>
                        </select>
                    </div>
                </div>
            </div>

            {!analytics && (
                <div className="text-center py-20 bg-gray-50 rounded-xl">
                    <p className="text-5xl mb-4">📊</p>
                    <h2 className="text-xl font-semibold text-gray-700 mb-2">Select a class to view analytics</h2>
                    <p className="text-gray-500">Choose a class from the dropdown above to see student performance data.</p>
                </div>
            )}

            {analytics && (
                <>
                    {/* Summary Cards */}
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                        <div className="card text-center">
                            <p className="text-3xl font-bold text-blue-600">{analytics.total_students}</p>
                            <p className="text-sm text-gray-500 mt-1">Total Students</p>
                        </div>
                        <div className="card text-center">
                            <p className="text-3xl font-bold text-green-600">{analytics.top3.length}</p>
                            <p className="text-sm text-gray-500 mt-1">Top Performers</p>
                        </div>
                        <div className="card text-center">
                            <p className="text-3xl font-bold text-red-600">{analytics.weak.length}</p>
                            <p className="text-sm text-gray-500 mt-1">Weak Students</p>
                        </div>
                        <div className="card text-center">
                            <p className="text-3xl font-bold text-purple-600">{analytics.stream_recommendations.length}</p>
                            <p className="text-sm text-gray-500 mt-1">Stream Recs</p>
                        </div>
                    </div>

                    {/* Tabs */}
                    <div className="border-b border-gray-200 mb-6 flex flex-wrap">
                        {([
                            { key: 'top3',     label: `🏆 Top 3` },
                            { key: 'weak',     label: `⚠️ Weak Students (${analytics.weak.length})` },
                            { key: 'stream',   label: `🎓 Stream Fit${analytics.class_num < 8 ? ' (N/A)' : ` (${analytics.stream_recommendations.length})`}` },
                            { key: 'subjects', label: `📚 Subject Averages` },
                            { key: 'all',      label: `👥 All Students` },
                        ] as const).map(t => (
                            <button key={t.key} onClick={() => setTab(t.key)}
                                className={`px-4 py-3 text-sm font-medium border-b-2 -mb-px transition ${
                                    tab === t.key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}>
                                {t.label}
                            </button>
                        ))}
                    </div>

                    {/* TOP 3 */}
                    {tab === 'top3' && (
                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            {analytics.top3.length === 0 && <p className="col-span-3 text-center text-gray-500 py-8">No result data available for this class yet.</p>}
                            {analytics.top3.map((s, i) => (
                                <div key={s.id} className={`card text-center border-2 ${i === 0 ? 'border-yellow-400' : i === 1 ? 'border-gray-400' : 'border-amber-700'}`}>
                                    <p className="text-5xl mb-2">{MEDAL[i]}</p>
                                    <p className="text-xl font-bold text-gray-900">{s.name}</p>
                                    <p className="text-sm text-gray-500 mb-3">{s.admission_no}</p>
                                    <p className={`text-4xl font-bold ${s.overall >= 70 ? 'text-green-600' : s.overall >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                        {s.overall}%
                                    </p>
                                    <p className="text-xs text-gray-400 mt-1">Composite Score (70% Exam + 30% Weekly)</p>
                                    <div className="mt-4 text-left space-y-1">
                                        {Object.values(s.subjects).slice(0, 5).map(sub => (
                                            <div key={sub.subject_code} className="flex justify-between text-sm">
                                                <span className="text-gray-600">{sub.subject_name}</span>
                                                <span className={`font-medium ${sub.composite >= 70 ? 'text-green-600' : sub.composite >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                                    {sub.composite}%
                                                </span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* WEAK STUDENTS */}
                    {tab === 'weak' && (
                        <div className="space-y-4">
                            {analytics.weak.length === 0 && (
                                <div className="card text-center py-10">
                                    <p className="text-3xl mb-2">✅</p>
                                    <p className="text-gray-600">No weak students found. All students scored above 50%.</p>
                                </div>
                            )}
                            {analytics.weak.map(s => (
                                <div key={s.id} className="card border-l-4 border-red-400">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-3 mb-1">
                                                <p className="font-semibold text-gray-900">{s.name}</p>
                                                <span className="text-xs text-gray-500">#{s.admission_no}</span>
                                                <span className="text-xs text-gray-400">Position {s.position}/{analytics.total_students}</span>
                                            </div>
                                            <p className="text-sm text-red-600 mb-2">
                                                ⚠️ Weak in: {s.weak_subjects.join(', ')}
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {Object.values(s.subjects).map(sub => (
                                                    <span key={sub.subject_code}
                                                        className={`px-2 py-0.5 rounded text-xs font-medium ${
                                                            sub.composite < 33 ? 'bg-red-100 text-red-800' :
                                                            sub.composite < 50 ? 'bg-amber-100 text-amber-800' :
                                                            'bg-green-100 text-green-800'
                                                        }`}>
                                                        {sub.subject_code}: {sub.composite}%
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-3xl font-bold text-red-600">{s.overall}%</p>
                                            <p className="text-xs text-gray-500">Overall</p>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    {/* STREAM RECOMMENDATIONS */}
                    {tab === 'stream' && (
                        <div>
                            {analytics.class_num < 8 ? (
                                <div className="card text-center py-10">
                                    <p className="text-3xl mb-2">📚</p>
                                    <p className="text-gray-600">Stream recommendations are available for Class 8 and above.</p>
                                </div>
                            ) : (
                                <>
                                    {/* Stream filter */}
                                    <div className="flex flex-wrap gap-2 mb-6">
                                        <button onClick={() => setSelectedStream('')}
                                            className={`px-4 py-2 rounded-lg text-sm font-medium border transition ${!selectedStream ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'}`}>
                                            All Streams ({analytics.stream_recommendations.length})
                                        </button>
                                        {Object.entries(streamGroups).map(([stream, recs]) => (
                                            <button key={stream} onClick={() => setSelectedStream(stream)}
                                                className={`px-4 py-2 rounded-lg text-sm font-medium border transition ${selectedStream === stream ? 'bg-blue-600 text-white border-blue-600' : `${STREAM_COLORS[stream]} border`}`}>
                                                {stream} ({recs.length})
                                            </button>
                                        ))}
                                    </div>

                                    <div className="card overflow-hidden">
                                        {filteredStream.length === 0 ? (
                                            <p className="text-center text-gray-500 py-8">No recommendations available. Students need subject marks data.</p>
                                        ) : (
                                            <table className="w-full text-sm">
                                                <thead className="bg-gray-50 border-b-2">
                                                    <tr>
                                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">Student</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-gray-700">Recommended Stream</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-gray-700">Match Score</th>
                                                        <th className="text-center py-3 px-4 font-semibold text-gray-700">Confidence</th>
                                                        <th className="text-left py-3 px-4 font-semibold text-gray-700">All Streams</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {filteredStream.map((r, idx) => (
                                                        <tr key={r.student_id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                                            <td className="py-3 px-4">
                                                                <p className="font-medium text-gray-900">{r.name}</p>
                                                                <p className="text-xs text-gray-500">{r.admission_no} · Overall: {r.overall}%</p>
                                                            </td>
                                                            <td className="text-center py-3 px-4">
                                                                <span className={`px-3 py-1 rounded-full text-xs font-semibold border ${STREAM_COLORS[r.recommended] ?? 'bg-gray-100 text-gray-700'}`}>
                                                                    {r.recommended}
                                                                </span>
                                                            </td>
                                                            <td className="text-center py-3 px-4">
                                                                <div className="flex items-center gap-2 justify-center">
                                                                    <div className="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                                        <div className="h-full bg-blue-500 rounded-full" style={{ width: `${r.score}%` }} />
                                                                    </div>
                                                                    <span className="font-semibold text-gray-800">{r.score}%</span>
                                                                </div>
                                                            </td>
                                                            <td className="text-center py-3 px-4">
                                                                <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${CONFIDENCE_BADGE[r.confidence]}`}>
                                                                    {r.confidence}
                                                                </span>
                                                            </td>
                                                            <td className="py-3 px-4">
                                                                <div className="flex flex-wrap gap-1">
                                                                    {Object.entries(r.all_scores).map(([stream, score]) => (
                                                                        <span key={stream}
                                                                            className={`px-1.5 py-0.5 rounded text-xs ${stream === r.recommended ? 'bg-blue-100 text-blue-800 font-semibold' : 'bg-gray-100 text-gray-600'}`}>
                                                                            {stream.split('/')[0]}: {score}%
                                                                        </span>
                                                                    ))}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {/* SUBJECT AVERAGES */}
                    {tab === 'subjects' && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            {analytics.subject_averages.map(sub => {
                                const pct = sub.avg;
                                const color = pct >= 70 ? 'green' : pct >= 50 ? 'amber' : 'red';
                                return (
                                    <div key={sub.subject_code} className={`card border-l-4 ${pct >= 70 ? 'border-green-400' : pct >= 50 ? 'border-amber-400' : 'border-red-400'}`}>
                                        <div className="flex items-start justify-between mb-3">
                                            <div>
                                                <p className="font-semibold text-gray-900">{sub.subject_name}</p>
                                                <p className="text-xs text-gray-500">{sub.subject_code}</p>
                                            </div>
                                            <p className={`text-2xl font-bold text-${color}-600`}>{pct}%</p>
                                        </div>
                                        <div className="w-full bg-gray-100 rounded-full h-2 mb-3">
                                            <div className={`h-2 rounded-full bg-${color}-500`} style={{ width: `${pct}%` }} />
                                        </div>
                                        <div className="grid grid-cols-3 gap-2 text-center text-xs">
                                            <div><p className="font-semibold text-green-700">{sub.max}%</p><p className="text-gray-500">Highest</p></div>
                                            <div><p className="font-semibold text-red-700">{sub.min}%</p><p className="text-gray-500">Lowest</p></div>
                                            <div><p className={`font-semibold ${sub.below_50 > 0 ? 'text-red-700' : 'text-green-700'}`}>{sub.below_50}</p><p className="text-gray-500">Below 50%</p></div>
                                        </div>
                                    </div>
                                );
                            })}
                            {analytics.subject_averages.length === 0 && (
                                <p className="col-span-3 text-center text-gray-500 py-8">No subject data available.</p>
                            )}
                        </div>
                    )}

                    {/* ALL STUDENTS */}
                    {tab === 'all' && (
                        <div className="card overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead className="bg-gray-50 border-b-2">
                                    <tr>
                                        <th className="text-left py-3 px-4 font-semibold">#</th>
                                        <th className="text-left py-3 px-4 font-semibold">Student</th>
                                        <th className="text-center py-3 px-4 font-semibold">Overall</th>
                                        <th className="text-center py-3 px-4 font-semibold">Status</th>
                                        <th className="text-left py-3 px-4 font-semibold">Weak Subjects</th>
                                        <th className="text-right py-3 px-4 font-semibold">Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {analytics.students.map((s, idx) => (
                                        <>
                                            <tr key={s.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                                <td className="py-3 px-4 text-gray-500">{s.position}</td>
                                                <td className="py-3 px-4">
                                                    <p className="font-medium text-gray-900">{s.name}</p>
                                                    <p className="text-xs text-gray-500">{s.admission_no}</p>
                                                </td>
                                                <td className="text-center py-3 px-4">
                                                    <span className={`font-bold ${s.overall >= 70 ? 'text-green-600' : s.overall >= 50 ? 'text-amber-600' : 'text-red-600'}`}>
                                                        {s.overall}%
                                                    </span>
                                                </td>
                                                <td className="text-center py-3 px-4">
                                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${s.is_weak ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'}`}>
                                                        {s.is_weak ? 'Needs Help' : 'On Track'}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-xs text-red-600">
                                                    {s.weak_subjects.length > 0 ? s.weak_subjects.join(', ') : '—'}
                                                </td>
                                                <td className="py-3 px-4 text-right">
                                                    <button onClick={() => setExpandedStudent(expandedStudent === s.id ? null : s.id)}
                                                        className="text-xs text-blue-600 hover:underline">
                                                        {expandedStudent === s.id ? 'Hide' : 'Show'} Subjects
                                                    </button>
                                                </td>
                                            </tr>
                                            {expandedStudent === s.id && (
                                                <tr key={`${s.id}-detail`} className="bg-blue-50 border-b">
                                                    <td colSpan={6} className="px-4 py-3">
                                                        <div className="flex flex-wrap gap-2">
                                                            {Object.values(s.subjects).map(sub => (
                                                                <div key={sub.subject_code} className="bg-white rounded p-2 text-xs border min-w-28 text-center">
                                                                    <p className="font-semibold text-gray-800">{sub.subject_name}</p>
                                                                    <p className={`text-base font-bold mt-1 ${sub.composite >= 70 ? 'text-green-600' : sub.composite >= 50 ? 'text-amber-600' : 'text-red-600'}`}>{sub.composite}%</p>
                                                                    <p className="text-gray-400 mt-0.5">Exam: {sub.exam_pct}% · Weekly: {sub.weekly_pct}%</p>
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </td>
                                                </tr>
                                            )}
                                        </>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </>
            )}
        </AppLayout>
    );
}
