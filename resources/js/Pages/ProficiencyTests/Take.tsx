import { Head, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Option { label: string; text: string; }
interface Question {
    id: number; question_text: string; question_type: string;
    options: Option[] | null; marks: number; order: number;
}
interface Section {
    id: number; name: string; instructions: string | null; passage: string | null;
    questions: Question[];
}
interface Props extends PageProps {
    assignment: { id: number; due_date: string | null };
    test: { id: number; title: string; duration_minutes: number; sections: Section[] };
    attempt: { id: number; started_at: string; status: string };
    existingAnswers: Record<number, string>;
}

export default function TakeTest({ assignment, test, attempt, existingAnswers }: Props) {
    const allQuestions = test.sections.flatMap(s => s.questions.map(q => ({ ...q, sectionName: s.name, sectionPassage: s.passage, sectionInstructions: s.instructions })));
    const totalQ = allQuestions.length;

    const [answers, setAnswers] = useState<Record<number, string>>(existingAnswers ?? {});
    const [currentSection, setCurrentSection] = useState(0);
    const [submitting, setSubmitting] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    // Timer
    const endTime = useRef(new Date(attempt.started_at).getTime() + test.duration_minutes * 60 * 1000);
    const [timeLeft, setTimeLeft] = useState(Math.max(0, Math.floor((endTime.current - Date.now()) / 1000)));

    useEffect(() => {
        const interval = setInterval(() => {
            const left = Math.max(0, Math.floor((endTime.current - Date.now()) / 1000));
            setTimeLeft(left);
            if (left === 0) { clearInterval(interval); handleSubmit(true); }
        }, 1000);
        return () => clearInterval(interval);
    }, []);

    const formatTime = (s: number) => {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`;
    };

    const answeredCount = Object.keys(answers).length;
    const section = test.sections[currentSection];

    const handleSubmit = (auto = false) => {
        if (!auto && !showConfirm) { setShowConfirm(true); return; }
        setSubmitting(true);
        const payload = Object.entries(answers).map(([qId, answer]) => ({
            question_id: parseInt(qId),
            answer,
        }));
        router.post(route('proficiency-tests.submit', attempt.id), { answers: payload });
    };

    return (
        <AppLayout title={test.title}>
            <Head title={`Test: ${test.title}`} />

            {/* Fixed Timer Bar */}
            <div className={`sticky top-0 z-50 flex items-center justify-between px-6 py-3 shadow-md ${timeLeft < 300 ? 'bg-red-600' : 'bg-gray-900'} text-white`}>
                <h2 className="font-semibold truncate max-w-sm">{test.title}</h2>
                <div className="flex items-center gap-6">
                    <span className="text-sm">{answeredCount}/{totalQ} answered</span>
                    <div className={`font-mono font-bold text-xl ${timeLeft < 300 ? 'animate-pulse' : ''}`}>
                        ⏱ {formatTime(timeLeft)}
                    </div>
                </div>
            </div>

            <div className="mt-6 grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Section Navigation */}
                <div className="lg:col-span-1 space-y-2">
                    <h3 className="text-sm font-semibold text-gray-700 mb-3">Sections</h3>
                    {test.sections.map((s, idx) => {
                        const answered = s.questions.filter(q => answers[q.id] !== undefined && answers[q.id] !== '').length;
                        return (
                            <button key={s.id} onClick={() => setCurrentSection(idx)}
                                className={`w-full text-left p-3 rounded-lg border transition ${
                                    idx === currentSection ? 'bg-blue-50 border-blue-400 text-blue-800' : 'bg-white border-gray-200 hover:border-blue-200'
                                }`}>
                                <p className="font-medium text-sm">{s.name}</p>
                                <p className="text-xs text-gray-500 mt-0.5">{answered}/{s.questions.length} answered</p>
                            </button>
                        );
                    })}

                    <div className="pt-4">
                        <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div className="bg-blue-500 h-2 rounded-full transition-all"
                                style={{ width: `${(answeredCount / totalQ) * 100}%` }} />
                        </div>
                        <p className="text-xs text-gray-500 text-center">{Math.round((answeredCount / totalQ) * 100)}% complete</p>
                    </div>
                </div>

                {/* Questions */}
                <div className="lg:col-span-3 space-y-4">
                    {/* Section Instructions & Passage */}
                    {(section.instructions || section.passage) && (
                        <div className="card bg-blue-50 border border-blue-200">
                            {section.instructions && (
                                <p className="text-sm text-blue-800 font-medium mb-2">{section.instructions}</p>
                            )}
                            {section.passage && (
                                <div className="prose prose-sm max-w-none text-gray-800 bg-white rounded p-4 border border-blue-100 max-h-48 overflow-y-auto">
                                    <pre className="whitespace-pre-wrap font-sans text-sm">{section.passage}</pre>
                                </div>
                            )}
                        </div>
                    )}

                    {section.questions.map((q, qi) => (
                        <div key={q.id} className={`card border-2 transition ${answers[q.id] ? 'border-green-200' : 'border-gray-100'}`}>
                            <div className="flex gap-3">
                                <span className={`flex-shrink-0 w-8 h-8 rounded-full text-sm font-bold flex items-center justify-center ${
                                    answers[q.id] ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700'
                                }`}>{qi + 1}</span>
                                <div className="flex-1">
                                    <p className="text-gray-900 font-medium mb-3 leading-relaxed">{q.question_text}</p>
                                    <p className="text-xs text-gray-400 mb-3">{q.marks} mark{q.marks !== 1 ? 's' : ''}</p>

                                    {/* MCQ */}
                                    {q.question_type === 'mcq' && q.options && (
                                        <div className="space-y-2">
                                            {q.options.map(opt => (
                                                <label key={opt.label}
                                                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition ${
                                                        answers[q.id] === opt.label
                                                            ? 'bg-blue-50 border-blue-400'
                                                            : 'border-gray-200 hover:border-blue-200 hover:bg-gray-50'
                                                    }`}>
                                                    <input type="radio"
                                                        name={`q-${q.id}`}
                                                        checked={answers[q.id] === opt.label}
                                                        onChange={() => setAnswers(a => ({ ...a, [q.id]: opt.label }))} />
                                                    <span className="font-semibold text-gray-600">{opt.label}.</span>
                                                    <span className="text-gray-800">{opt.text}</span>
                                                </label>
                                            ))}
                                        </div>
                                    )}

                                    {/* True/False/Not Given */}
                                    {q.question_type === 'true_false' && (
                                        <div className="flex gap-3 flex-wrap">
                                            {['True', 'False', 'Not Given'].map(v => (
                                                <label key={v}
                                                    className={`flex items-center gap-2 px-4 py-2 rounded-lg border cursor-pointer transition ${
                                                        answers[q.id] === v ? 'bg-blue-50 border-blue-400' : 'border-gray-200 hover:border-blue-200'
                                                    }`}>
                                                    <input type="radio" name={`q-${q.id}`}
                                                        checked={answers[q.id] === v}
                                                        onChange={() => setAnswers(a => ({ ...a, [q.id]: v }))} />
                                                    <span className="text-sm font-medium">{v}</span>
                                                </label>
                                            ))}
                                        </div>
                                    )}

                                    {/* Fill Blank */}
                                    {q.question_type === 'fill_blank' && (
                                        <input type="text"
                                            value={answers[q.id] ?? ''}
                                            onChange={e => setAnswers(a => ({ ...a, [q.id]: e.target.value }))}
                                            className="form-input max-w-sm"
                                            placeholder="Type your answer here..." />
                                    )}

                                    {/* Essay */}
                                    {q.question_type === 'essay' && (
                                        <div>
                                            <textarea
                                                value={answers[q.id] ?? ''}
                                                onChange={e => setAnswers(a => ({ ...a, [q.id]: e.target.value }))}
                                                className="form-textarea w-full"
                                                rows={6}
                                                placeholder="Write your essay response here..." />
                                            <p className="text-xs text-gray-400 mt-1">
                                                {(answers[q.id] ?? '').split(/\s+/).filter(Boolean).length} words
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}

                    {/* Navigation + Submit */}
                    <div className="flex items-center justify-between pt-4">
                        <button type="button"
                            disabled={currentSection === 0}
                            onClick={() => setCurrentSection(i => i - 1)}
                            className="btn btn-secondary disabled:opacity-40">
                            ← Previous Section
                        </button>

                        {currentSection < test.sections.length - 1 ? (
                            <button type="button" onClick={() => setCurrentSection(i => i + 1)} className="btn btn-primary">
                                Next Section →
                            </button>
                        ) : (
                            <button type="button" onClick={() => handleSubmit(false)}
                                disabled={submitting}
                                className="btn bg-green-600 hover:bg-green-700 text-white">
                                {submitting ? 'Submitting…' : 'Submit Test'}
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {/* Confirm Modal */}
            {showConfirm && (
                <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full mx-4">
                        <h2 className="text-xl font-bold text-gray-900 mb-2">Submit Test?</h2>
                        <p className="text-gray-600 mb-2">
                            You have answered <strong>{answeredCount}</strong> of <strong>{totalQ}</strong> questions.
                        </p>
                        {answeredCount < totalQ && (
                            <p className="text-amber-600 text-sm mb-4">
                                ⚠️ {totalQ - answeredCount} question(s) unanswered. You cannot return after submitting.
                            </p>
                        )}
                        <div className="flex gap-3 justify-end mt-6">
                            <button onClick={() => setShowConfirm(false)} className="btn btn-secondary">
                                Continue Test
                            </button>
                            <button onClick={() => handleSubmit(true)} className="btn bg-green-600 hover:bg-green-700 text-white" disabled={submitting}>
                                {submitting ? 'Submitting…' : 'Yes, Submit'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
