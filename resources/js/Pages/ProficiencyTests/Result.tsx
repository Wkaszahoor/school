import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Answer {
    id: number; answer: string | null; is_correct: boolean | null;
    marks_awarded: number; feedback: string | null;
    question: {
        id: number; question_text: string; question_type: string;
        options: Array<{ label: string; text: string }> | null;
        correct_answer: string | null; marks: number;
    };
}
interface Props extends PageProps {
    attempt: {
        id: number; total_score: number; max_score: number;
        percentage: number | null; band_score: number | null;
        status: string; completed_at: string | null;
        band_label?: string;
        assignment: {
            test: {
                id: number; title: string; passing_score: number;
                sections: Array<{
                    id: number; name: string; marks: number;
                    questions: Array<{ id: number }>;
                }>;
            };
        };
        answers: Answer[];
    };
}

const canGrade = (role?: string) => role === 'admin' || role === 'principal';

function GradeForm({ answer, attemptId }: { answer: Answer; attemptId: number }) {
    const { data, setData, post, processing } = useForm({
        marks_awarded: answer.marks_awarded ?? 0,
        feedback: answer.feedback ?? '',
    });
    return (
        <form onSubmit={e => { e.preventDefault(); post(route('principal.professional-development.proficiency-tests.grade-essay', answer.id)); }}
            className="mt-3 p-3 bg-amber-50 rounded-lg border border-amber-200 space-y-2">
            <p className="text-xs font-semibold text-amber-800">Grade this essay:</p>
            <div className="flex gap-3 items-end">
                <div>
                    <label className="block text-xs text-gray-600 mb-1">Marks (max {answer.question.marks})</label>
                    <input type="number" min="0" max={answer.question.marks}
                        value={data.marks_awarded}
                        onChange={e => setData('marks_awarded', parseInt(e.target.value) || 0)}
                        className="form-input text-sm w-24" />
                </div>
                <div className="flex-1">
                    <label className="block text-xs text-gray-600 mb-1">Feedback (optional)</label>
                    <input type="text" value={data.feedback}
                        onChange={e => setData('feedback', e.target.value)}
                        className="form-input text-sm" placeholder="Short feedback..." />
                </div>
                <button type="submit" className="btn btn-primary text-sm py-1" disabled={processing}>
                    Save
                </button>
            </div>
        </form>
    );
}

export default function TestResult({ attempt, auth }: Props & { auth: { user: { role: string } } }) {
    const { assignment: { test } } = attempt;
    const isPrincipalOrAdmin = canGrade(auth?.user?.role);
    const passed = attempt.percentage != null && attempt.percentage >= test.passing_score;
    const isGrading = attempt.status === 'grading';

    const answersMap: Record<number, Answer> = {};
    attempt.answers.forEach(a => { answersMap[a.question.id] = a; });

    const bandScore = attempt.band_score ?? 0;
    const bandLabel = attempt.band_label ?? (
        bandScore >= 9 ? 'Expert' : bandScore >= 8 ? 'Very Good' :
        bandScore >= 7 ? 'Good' : bandScore >= 6 ? 'Competent' :
        bandScore >= 5 ? 'Modest' : bandScore >= 4 ? 'Limited' :
        bandScore >= 3 ? 'Extremely Limited' : bandScore >= 2 ? 'Intermittent' : 'Non-User'
    );

    return (
        <AppLayout title={`Result – ${test.title}`}>
            <Head title={`Result: ${test.title}`} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Test Result</h1>
                    <p className="page-subtitle">{test.title}</p>
                </div>
                <a href={route('proficiency-tests.my')} className="btn btn-secondary">My Tests</a>
            </div>

            {/* Score Card */}
            {isGrading ? (
                <div className="card text-center py-10 mb-6 bg-amber-50 border border-amber-200">
                    <p className="text-5xl mb-3">✍️</p>
                    <h2 className="text-2xl font-bold text-amber-800 mb-2">Awaiting Essay Grading</h2>
                    <p className="text-amber-600">Your essay answers are being reviewed. Your final result will appear here once grading is complete.</p>
                    <p className="text-sm text-gray-500 mt-4">Auto-graded score so far: <strong>{attempt.total_score}/{attempt.max_score}</strong></p>
                </div>
            ) : (
                <div className="card mb-6 text-center py-8">
                    <div className="flex items-center justify-center gap-8 flex-wrap">
                        <div>
                            <p className={`text-6xl font-bold ${passed ? 'text-green-600' : 'text-red-600'}`}>
                                {attempt.percentage?.toFixed(1)}%
                            </p>
                            <p className="text-gray-500 mt-1">{attempt.total_score} / {attempt.max_score} marks</p>
                        </div>
                        <div className="h-20 w-px bg-gray-200 hidden sm:block" />
                        <div>
                            <p className="text-5xl font-bold text-blue-600">{bandScore}</p>
                            <p className="text-gray-500 mt-1">Band Score</p>
                            <p className="text-sm font-medium text-blue-700">{bandLabel}</p>
                        </div>
                        <div className="h-20 w-px bg-gray-200 hidden sm:block" />
                        <div>
                            <p className={`text-4xl font-bold ${passed ? 'text-green-600' : 'text-red-600'}`}>
                                {passed ? '✅ Pass' : '❌ Fail'}
                            </p>
                            <p className="text-gray-500 mt-1">Pass mark: {test.passing_score}%</p>
                        </div>
                    </div>

                    {/* Band Scale */}
                    <div className="mt-6 max-w-lg mx-auto">
                        <div className="flex rounded-full overflow-hidden h-4">
                            {[1,2,3,4,5,6,7,8,9].map(b => (
                                <div key={b}
                                    className={`flex-1 ${b <= bandScore ? 'bg-blue-500' : 'bg-gray-200'}`}
                                    title={`Band ${b}`} />
                            ))}
                        </div>
                        <div className="flex justify-between text-xs text-gray-400 mt-1 px-1">
                            <span>1</span><span>5</span><span>9</span>
                        </div>
                        <p className="text-xs text-gray-400 mt-1">IELTS-style Band Score (1–9)</p>
                    </div>
                </div>
            )}

            {/* Detailed Answers */}
            {test.sections.map(section => {
                const sectionAnswers = section.questions.map(q => answersMap[q.id]).filter(Boolean);
                if (sectionAnswers.length === 0) return null;
                const sectionScore = sectionAnswers.reduce((sum, a) => sum + (a?.marks_awarded ?? 0), 0);
                return (
                    <div key={section.id} className="card mb-4">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-semibold text-gray-900">{section.name}</h2>
                            <span className="text-sm text-gray-500">{sectionScore}/{section.marks} marks</span>
                        </div>
                        <div className="space-y-4">
                            {section.questions.map((q, qi) => {
                                const ans = answersMap[q.id];
                                if (!ans) return null;
                                const isEssay = ans.question.question_type === 'essay';
                                const isCorrect = ans.is_correct;
                                return (
                                    <div key={q.id} className={`p-4 rounded-lg border ${
                                        isEssay ? 'border-purple-200 bg-purple-50' :
                                        isCorrect === true ? 'border-green-200 bg-green-50' :
                                        isCorrect === false ? 'border-red-200 bg-red-50' :
                                        'border-gray-200 bg-gray-50'
                                    }`}>
                                        <div className="flex gap-3">
                                            <span className={`flex-shrink-0 w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center ${
                                                isEssay ? 'bg-purple-200 text-purple-800' :
                                                isCorrect === true ? 'bg-green-500 text-white' :
                                                isCorrect === false ? 'bg-red-500 text-white' :
                                                'bg-gray-200 text-gray-700'
                                            }`}>{qi + 1}</span>
                                            <div className="flex-1">
                                                <p className="font-medium text-gray-900 mb-2">{ans.question.question_text}</p>

                                                <div className="text-sm space-y-1">
                                                    <p className="text-gray-600">
                                                        <span className="font-medium">Your answer: </span>
                                                        <span className={isCorrect === false ? 'text-red-700 line-through' : 'text-gray-800'}>
                                                            {ans.answer || <em>No answer</em>}
                                                        </span>
                                                    </p>
                                                    {!isEssay && ans.question.correct_answer && (
                                                        <p className="text-green-700">
                                                            <span className="font-medium">Correct: </span>{ans.question.correct_answer}
                                                        </p>
                                                    )}
                                                    {isEssay && ans.feedback && (
                                                        <p className="text-purple-700 italic">
                                                            <span className="font-medium not-italic">Feedback: </span>{ans.feedback}
                                                        </p>
                                                    )}
                                                </div>

                                                <div className="mt-2 text-xs font-medium">
                                                    {isEssay ? (
                                                        <span className="text-purple-700">
                                                            {ans.is_correct === null ? '⏳ Awaiting grading' : `${ans.marks_awarded}/${ans.question.marks} marks`}
                                                        </span>
                                                    ) : (
                                                        <span className={isCorrect ? 'text-green-700' : 'text-red-700'}>
                                                            {isCorrect ? `✓ +${ans.marks_awarded}` : '✗ 0'} / {ans.question.marks} marks
                                                        </span>
                                                    )}
                                                </div>

                                                {isPrincipalOrAdmin && isEssay && ans.is_correct === null && (
                                                    <GradeForm answer={ans} attemptId={attempt.id} />
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </AppLayout>
    );
}
