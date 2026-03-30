import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { PlusIcon, TrashIcon, ChevronDownIcon, ChevronUpIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

type QuestionType = 'mcq' | 'true_false' | 'fill_blank' | 'essay';

interface Option { label: string; text: string; }
interface Question {
    question_text: string;
    question_type: QuestionType;
    options: Option[];
    correct_answer: string;
    marks: number;
}
interface Section {
    name: string;
    instructions: string;
    passage: string;
    questions: Question[];
    open: boolean;
}

interface Props extends PageProps {
    test?: {
        id: number;
        title: string;
        description: string;
        duration_minutes: number;
        passing_score: number;
        status: string;
        sections: Array<{
            name: string; instructions: string; passage: string;
            questions: Array<{
                question_text: string; question_type: string;
                options: Option[] | null; correct_answer: string | null; marks: number;
            }>;
        }>;
    };
}

const SECTION_PRESETS = ['Reading', 'Listening', 'Writing', 'Grammar', 'Vocabulary'];

const emptyQuestion = (): Question => ({
    question_text: '',
    question_type: 'mcq',
    options: [
        { label: 'A', text: '' },
        { label: 'B', text: '' },
        { label: 'C', text: '' },
        { label: 'D', text: '' },
    ],
    correct_answer: '',
    marks: 1,
});

const emptySection = (name = ''): Section => ({
    name,
    instructions: '',
    passage: '',
    questions: [emptyQuestion()],
    open: true,
});

export default function ProficiencyTestForm({ test }: Props) {
    const isEditing = !!test;

    const [sections, setSections] = useState<Section[]>(() => {
        if (test?.sections?.length) {
            return test.sections.map(s => ({
                name: s.name,
                instructions: s.instructions ?? '',
                passage: s.passage ?? '',
                open: true,
                questions: s.questions.map(q => ({
                    question_text: q.question_text,
                    question_type: q.question_type as QuestionType,
                    options: q.options ?? [
                        { label: 'A', text: '' }, { label: 'B', text: '' },
                        { label: 'C', text: '' }, { label: 'D', text: '' },
                    ],
                    correct_answer: q.correct_answer ?? '',
                    marks: q.marks,
                })),
            }));
        }
        return [emptySection('Reading')];
    });

    const { data, setData, post, put, processing, errors } = useForm({
        title:            test?.title ?? '',
        description:      test?.description ?? '',
        duration_minutes: test?.duration_minutes ?? 60,
        passing_score:    test?.passing_score ?? 60,
        status:           test?.status ?? 'draft',
        sections:         [] as any[],
    });

    // Section helpers
    const addSection = (preset = '') => setSections(s => [...s, emptySection(preset)]);
    const removeSection = (i: number) => setSections(s => s.filter((_, idx) => idx !== i));
    const updateSection = (i: number, field: keyof Section, value: any) =>
        setSections(s => s.map((sec, idx) => idx === i ? { ...sec, [field]: value } : sec));
    const toggleSection = (i: number) => updateSection(i, 'open', !sections[i].open);

    // Question helpers
    const addQuestion = (si: number) =>
        setSections(s => s.map((sec, idx) => idx === si ? { ...sec, questions: [...sec.questions, emptyQuestion()] } : sec));
    const removeQuestion = (si: number, qi: number) =>
        setSections(s => s.map((sec, idx) => idx === si
            ? { ...sec, questions: sec.questions.filter((_, qIdx) => qIdx !== qi) }
            : sec));
    const updateQuestion = (si: number, qi: number, field: keyof Question, value: any) =>
        setSections(s => s.map((sec, idx) => idx === si
            ? {
                ...sec,
                questions: sec.questions.map((q, qIdx) => {
                    if (qIdx !== qi) return q;
                    const updated = { ...q, [field]: value };
                    // Reset options/answer when type changes
                    if (field === 'question_type') {
                        updated.correct_answer = '';
                        if (value === 'mcq') {
                            updated.options = [
                                { label: 'A', text: '' }, { label: 'B', text: '' },
                                { label: 'C', text: '' }, { label: 'D', text: '' },
                            ];
                        } else {
                            updated.options = [];
                        }
                    }
                    return updated;
                }),
              }
            : sec));
    const updateOption = (si: number, qi: number, oi: number, text: string) =>
        setSections(s => s.map((sec, idx) => idx === si
            ? {
                ...sec,
                questions: sec.questions.map((q, qIdx) => qIdx === qi
                    ? { ...q, options: q.options.map((o, oIdx) => oIdx === oi ? { ...o, text } : o) }
                    : q),
              }
            : sec));

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const payload = { ...data, sections };
        if (isEditing) {
            put(route('principal.professional-development.proficiency-tests.update', test.id), { data: payload } as any);
        } else {
            post(route('principal.professional-development.proficiency-tests.store'), { data: payload } as any);
        }
    };

    const totalMarks = sections.reduce((sum, s) => sum + s.questions.reduce((qs, q) => qs + (q.marks || 0), 0), 0);
    const totalQuestions = sections.reduce((sum, s) => sum + s.questions.length, 0);

    return (
        <AppLayout title={isEditing ? 'Edit Proficiency Test' : 'Create Proficiency Test'}>
            <Head title={isEditing ? 'Edit Proficiency Test' : 'Create Proficiency Test'} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">{isEditing ? 'Edit Test' : 'Create English Proficiency Test'}</h1>
                    <p className="page-subtitle">Build sections with reading passages, grammar questions, and essay prompts</p>
                </div>
                <div className="flex gap-3 text-sm">
                    <span className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-medium">{sections.length} sections</span>
                    <span className="px-3 py-1 bg-purple-100 text-purple-800 rounded-full font-medium">{totalQuestions} questions</span>
                    <span className="px-3 py-1 bg-green-100 text-green-800 rounded-full font-medium">{totalMarks} marks</span>
                </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Basic Info */}
                <div className="card space-y-4">
                    <h2 className="text-lg font-semibold text-gray-900">Test Details</h2>
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-900 mb-1">Test Title <span className="text-red-600">*</span></label>
                            <input type="text" value={data.title} onChange={e => setData('title', e.target.value)}
                                className="form-input" placeholder="e.g., English Proficiency Test – Level B2" required />
                            {errors.title && <p className="text-sm text-red-600 mt-1">{errors.title}</p>}
                        </div>
                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-900 mb-1">Description</label>
                            <textarea value={data.description} onChange={e => setData('description', e.target.value)}
                                className="form-textarea" rows={2} placeholder="Optional description for test-takers..." />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Duration (minutes) <span className="text-red-600">*</span></label>
                            <input type="number" min="5" value={data.duration_minutes}
                                onChange={e => setData('duration_minutes', parseInt(e.target.value) || 60)}
                                className="form-input" required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Passing Score (%) <span className="text-red-600">*</span></label>
                            <input type="number" min="1" max="100" value={data.passing_score}
                                onChange={e => setData('passing_score', parseInt(e.target.value) || 60)}
                                className="form-input" required />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Status</label>
                            <select value={data.status} onChange={e => setData('status', e.target.value)} className="form-select">
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>
                </div>

                {/* Sections */}
                {sections.map((section, si) => (
                    <div key={si} className="card border-2 border-blue-100">
                        {/* Section Header */}
                        <div className="flex items-center gap-3 mb-4">
                            <button type="button" onClick={() => toggleSection(si)} className="text-gray-500 hover:text-gray-700">
                                {section.open ? <ChevronUpIcon className="w-5 h-5" /> : <ChevronDownIcon className="w-5 h-5" />}
                            </button>
                            <input
                                type="text"
                                value={section.name}
                                onChange={e => updateSection(si, 'name', e.target.value)}
                                className="flex-1 text-lg font-semibold bg-transparent border-b border-transparent focus:border-blue-400 outline-none py-1"
                                placeholder="Section name (e.g. Reading)"
                                required
                            />
                            <span className="text-sm text-gray-500">{section.questions.length} Q</span>
                            {sections.length > 1 && (
                                <button type="button" onClick={() => removeSection(si)}
                                    className="text-red-500 hover:text-red-700 p-1">
                                    <TrashIcon className="w-4 h-4" />
                                </button>
                            )}
                        </div>

                        {section.open && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                                        <textarea value={section.instructions}
                                            onChange={e => updateSection(si, 'instructions', e.target.value)}
                                            className="form-textarea" rows={2}
                                            placeholder="Instructions shown to test-taker before this section..." />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Reading Passage / Transcript</label>
                                        <textarea value={section.passage}
                                            onChange={e => updateSection(si, 'passage', e.target.value)}
                                            className="form-textarea" rows={2}
                                            placeholder="Paste the reading passage or audio script here..." />
                                    </div>
                                </div>

                                {/* Questions */}
                                <div className="space-y-4">
                                    {section.questions.map((q, qi) => (
                                        <div key={qi} className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                            <div className="flex items-start gap-3 mb-3">
                                                <span className="flex-shrink-0 w-7 h-7 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center mt-1">
                                                    {qi + 1}
                                                </span>
                                                <div className="flex-1 space-y-3">
                                                    <textarea
                                                        value={q.question_text}
                                                        onChange={e => updateQuestion(si, qi, 'question_text', e.target.value)}
                                                        className="form-textarea w-full"
                                                        rows={2}
                                                        placeholder="Enter question text..."
                                                        required
                                                    />
                                                    <div className="flex flex-wrap gap-3">
                                                        <div>
                                                            <label className="block text-xs text-gray-600 mb-1">Type</label>
                                                            <select value={q.question_type}
                                                                onChange={e => updateQuestion(si, qi, 'question_type', e.target.value)}
                                                                className="form-select text-sm py-1">
                                                                <option value="mcq">Multiple Choice</option>
                                                                <option value="true_false">True / False / Not Given</option>
                                                                <option value="fill_blank">Fill in the Blank</option>
                                                                <option value="essay">Essay / Written</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label className="block text-xs text-gray-600 mb-1">Marks</label>
                                                            <input type="number" min="1" value={q.marks}
                                                                onChange={e => updateQuestion(si, qi, 'marks', parseInt(e.target.value) || 1)}
                                                                className="form-input text-sm py-1 w-20" />
                                                        </div>
                                                    </div>

                                                    {/* MCQ Options */}
                                                    {q.question_type === 'mcq' && (
                                                        <div className="space-y-2">
                                                            <p className="text-xs font-medium text-gray-700">Options (click radio to mark correct)</p>
                                                            {q.options.map((opt, oi) => (
                                                                <div key={oi} className="flex items-center gap-2">
                                                                    <input type="radio" name={`correct-${si}-${qi}`}
                                                                        checked={q.correct_answer === opt.label}
                                                                        onChange={() => updateQuestion(si, qi, 'correct_answer', opt.label)}
                                                                        className="flex-shrink-0" />
                                                                    <span className="w-6 text-sm font-bold text-gray-600">{opt.label}.</span>
                                                                    <input type="text" value={opt.text}
                                                                        onChange={e => updateOption(si, qi, oi, e.target.value)}
                                                                        className="form-input text-sm py-1 flex-1"
                                                                        placeholder={`Option ${opt.label}`} />
                                                                </div>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {/* True/False */}
                                                    {q.question_type === 'true_false' && (
                                                        <div>
                                                            <p className="text-xs font-medium text-gray-700 mb-2">Correct Answer</p>
                                                            <div className="flex gap-4">
                                                                {['True', 'False', 'Not Given'].map(v => (
                                                                    <label key={v} className="flex items-center gap-1.5 cursor-pointer">
                                                                        <input type="radio" name={`tf-${si}-${qi}`}
                                                                            checked={q.correct_answer === v}
                                                                            onChange={() => updateQuestion(si, qi, 'correct_answer', v)} />
                                                                        <span className="text-sm">{v}</span>
                                                                    </label>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    {/* Fill Blank */}
                                                    {q.question_type === 'fill_blank' && (
                                                        <div>
                                                            <label className="block text-xs font-medium text-gray-700 mb-1">Correct Answer (exact match)</label>
                                                            <input type="text" value={q.correct_answer}
                                                                onChange={e => updateQuestion(si, qi, 'correct_answer', e.target.value)}
                                                                className="form-input text-sm"
                                                                placeholder="e.g., photosynthesis" />
                                                        </div>
                                                    )}

                                                    {q.question_type === 'essay' && (
                                                        <p className="text-xs text-amber-600 bg-amber-50 px-3 py-2 rounded">
                                                            ✍️ Essay questions are manually graded by the principal after submission.
                                                        </p>
                                                    )}
                                                </div>
                                                {section.questions.length > 1 && (
                                                    <button type="button" onClick={() => removeQuestion(si, qi)}
                                                        className="flex-shrink-0 text-red-400 hover:text-red-600 p-1 mt-1">
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <button type="button" onClick={() => addQuestion(si)}
                                    className="w-full py-2 border-2 border-dashed border-blue-300 text-blue-600 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition text-sm font-medium flex items-center justify-center gap-2">
                                    <PlusIcon className="w-4 h-4" />
                                    Add Question to {section.name || 'Section'}
                                </button>
                            </div>
                        )}
                    </div>
                ))}

                {/* Add Section */}
                <div className="card">
                    <p className="text-sm font-medium text-gray-700 mb-3">Add Section</p>
                    <div className="flex flex-wrap gap-2">
                        {SECTION_PRESETS.map(preset => (
                            <button key={preset} type="button" onClick={() => addSection(preset)}
                                className="px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-sm font-medium hover:bg-blue-100 transition">
                                + {preset}
                            </button>
                        ))}
                        <button type="button" onClick={() => addSection()}
                            className="px-4 py-2 bg-gray-100 text-gray-700 border border-gray-200 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                            + Custom Section
                        </button>
                    </div>
                </div>

                {/* Submit */}
                <div className="flex gap-3 justify-end">
                    <a href={route('principal.professional-development.proficiency-tests.index')} className="btn btn-secondary">
                        Cancel
                    </a>
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        {processing ? 'Saving…' : isEditing ? 'Update Test' : 'Create Test'}
                    </button>
                </div>
            </form>
        </AppLayout>
    );
}
