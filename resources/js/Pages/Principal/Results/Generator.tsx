import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { PrinterIcon, ArrowDownTrayIcon, ArrowTopRightOnSquareIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

declare const route: (name: string, params?: any) => string;

interface SchoolClass { id: number; class: string; section?: string; }
interface Student     { id: number; full_name: string; admission_no: string; }
interface SubjectRow  { subject_name: string; obtained_marks: number; total_marks: number; percentage: number; grade: string; }
interface StudentResult {
    subjects: SubjectRow[];
    total_marks: number;
    obtained_marks: number;
    overall_percentage: number;
    overall_grade: string;
}

interface Props extends PageProps {
    classes: SchoolClass[];
    examTypes: Record<string, string>;
    terms: Record<string, string>;
    academicYears: string[];
    students: Student[];
    studentResult: StudentResult | null;
    classStats: { total_students: number; total_entries: number } | null;
    filters: Record<string, string>;
}

export default function Generator({ classes, examTypes, terms, academicYears, students, studentResult, classStats, filters }: Props) {
    const [form, setForm] = useState({
        academic_year: filters.academic_year || (academicYears[0] ?? ''),
        exam_type:     filters.exam_type     || '',
        class_id:      filters.class_id      || '',
        student_id:    filters.student_id    || '',
        term:          filters.term          || '',
    });

    const set = (key: string, value: string) => {
        const next = { ...form, [key]: value };
        if (key === 'class_id') next.student_id = '';
        setForm(next);

        // reload page with new class to update students list
        if (key === 'class_id') {
            router.get(route('principal.results.generator'), { ...next }, { preserveState: false, replace: true });
        }
    };

    const handlePreview = () => {
        router.get(route('principal.results.generator'), form, { preserveState: false });
    };

    const reportCardsUrl = () => {
        const p = new URLSearchParams();
        if (form.exam_type)     p.set('exam_type', form.exam_type);
        if (form.academic_year) p.set('academic_year', form.academic_year);
        if (form.term)          p.set('term', form.term);
        if (form.class_id)      p.set('class_id', form.class_id);
        return route('principal.results.report-cards') + '?' + p.toString();
    };

    const singleCardUrl = () => {
        const p = new URLSearchParams();
        if (form.exam_type)     p.set('exam_type', form.exam_type);
        if (form.academic_year) p.set('academic_year', form.academic_year);
        if (form.term)          p.set('term', form.term);
        if (form.student_id)    p.set('student_id', form.student_id);
        return route('principal.results.report-cards') + '?' + p.toString();
    };

    const gradeColor = (g: string) => {
        if (['A+', 'A'].includes(g)) return 'text-emerald-600';
        if (['B+', 'B'].includes(g)) return 'text-blue-600';
        if (['C', 'D'].includes(g))  return 'text-amber-600';
        return 'text-red-600';
    };

    return (
        <AppLayout title="Result Card Generator">
            <Head title="Result Card Generator" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Result Card Generator</h1>
                    <p className="page-subtitle">Generate, preview, print, and download student result cards.</p>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-[320px_1fr_340px] gap-5">

                {/* ── LEFT: Filters ── */}
                <div className="card">
                    <div className="card-body space-y-4">
                        <div>
                            <p className="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Filters</p>
                            <p className="text-xs text-gray-400">Choose exam context and student.</p>
                        </div>

                        {/* Session */}
                        <div>
                            <label className="form-label">Session</label>
                            <select className="form-select" value={form.academic_year} onChange={e => set('academic_year', e.target.value)}>
                                <option value="">Select session</option>
                                {academicYears.map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>

                        {/* Exam Type */}
                        <div>
                            <label className="form-label">Exam Type</label>
                            <select className="form-select" value={form.exam_type} onChange={e => set('exam_type', e.target.value)}>
                                <option value="">Select exam type</option>
                                {Object.entries(examTypes).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                        </div>

                        {/* Term */}
                        <div>
                            <label className="form-label">Term</label>
                            <select className="form-select" value={form.term} onChange={e => set('term', e.target.value)}>
                                <option value="">Select term</option>
                                {Object.entries(terms).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                        </div>

                        {/* Class */}
                        <div>
                            <label className="form-label">Class</label>
                            <select className="form-select" value={form.class_id} onChange={e => set('class_id', e.target.value)}>
                                <option value="">Select class</option>
                                {classes.map(c => (
                                    <option key={c.id} value={c.id}>
                                        {c.class}{c.section ? `-${c.section}` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Student */}
                        <div>
                            <label className="form-label">Student</label>
                            <select className="form-select" value={form.student_id} onChange={e => set('student_id', e.target.value)}>
                                <option value="">Select student</option>
                                {students.map(s => (
                                    <option key={s.id} value={s.id}>{s.full_name} ({s.admission_no})</option>
                                ))}
                            </select>
                            <p className="text-xs text-gray-400 mt-1">{students.length} student(s)</p>
                        </div>

                        <div className="pt-2 space-y-2">
                            <button onClick={handlePreview} className="btn-primary w-full">
                                Preview Result
                            </button>
                            <button
                                disabled={!form.student_id}
                                onClick={() => window.open(singleCardUrl(), '_blank')}
                                className="btn-secondary w-full disabled:opacity-50"
                            >
                                <ArrowDownTrayIcon className="w-4 h-4 inline mr-1" />
                                Download PDF
                            </button>
                            <button
                                disabled={!form.student_id}
                                onClick={() => { window.open(singleCardUrl(), '_blank'); }}
                                className="btn-secondary w-full disabled:opacity-50"
                            >
                                <PrinterIcon className="w-4 h-4 inline mr-1" />
                                Print
                            </button>
                        </div>
                    </div>
                </div>

                {/* ── MIDDLE: Stats + Summary ── */}
                <div className="space-y-4">
                    {/* Class Actions */}
                    <div className="card">
                        <div className="card-body">
                            <div className="flex items-start justify-between gap-4 flex-wrap">
                                <div>
                                    <p className="font-semibold text-gray-800">Class Actions</p>
                                    <p className="text-xs text-gray-500 mt-0.5">Generate cards for class and publish results.</p>
                                </div>
                                <div className="flex gap-2 flex-wrap">
                                    <button
                                        disabled={!form.class_id || !form.exam_type || !form.academic_year || !form.term}
                                        onClick={() => window.open(reportCardsUrl(), '_blank')}
                                        className="btn-secondary text-sm disabled:opacity-50"
                                    >
                                        Generate All Class Result Cards
                                    </button>
                                    <button
                                        disabled={!form.class_id || !form.exam_type}
                                        onClick={() => router.get(route('principal.results.index'), { class_id: form.class_id, exam_type: form.exam_type })}
                                        className="px-4 py-2 text-sm bg-amber-500 text-white rounded-lg hover:bg-amber-600 disabled:opacity-50"
                                    >
                                        Publish Results
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Stat Cards */}
                    <div className="grid grid-cols-2 gap-4">
                        {[
                            { label: 'TOTAL MARKS',       value: studentResult?.total_marks        ?? '–' },
                            { label: 'OBTAINED MARKS',    value: studentResult?.obtained_marks     ?? '–' },
                            { label: 'OVERALL PERCENTAGE',value: studentResult ? `${studentResult.overall_percentage}%` : '–' },
                            { label: 'OVERALL GRADE',     value: studentResult?.overall_grade      ?? '–' },
                        ].map(({ label, value }) => (
                            <div key={label} className="card">
                                <div className="card-body">
                                    <p className="text-xs font-semibold text-gray-400 uppercase tracking-wide">{label}</p>
                                    <p className={`text-2xl font-bold mt-1 ${label === 'OVERALL GRADE' && studentResult ? gradeColor(studentResult.overall_grade) : 'text-gray-900'}`}>
                                        {value}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Result Summary Table */}
                    <div className="card">
                        <div className="card-body">
                            <div className="flex items-center justify-between mb-3">
                                <p className="font-semibold text-gray-800">Result Summary</p>
                                {studentResult && (
                                    <span className={`badge ${studentResult.overall_percentage >= 33 ? 'badge-green' : 'badge-red'}`}>
                                        {studentResult.overall_percentage >= 33 ? 'PASS' : 'FAIL'}
                                    </span>
                                )}
                            </div>

                            {!studentResult ? (
                                <p className="text-sm text-gray-400 text-center py-6">
                                    {form.student_id ? 'No approved results found for this student.' : 'No student selected'}
                                </p>
                            ) : (
                                <div className="table-wrapper">
                                    <table className="table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th className="text-right">Total Marks</th>
                                                <th className="text-right">Obtained</th>
                                                <th className="text-right">Percentage</th>
                                                <th className="text-center">Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {studentResult.subjects.map((row, i) => (
                                                <tr key={i}>
                                                    <td className="font-medium">{row.subject_name}</td>
                                                    <td className="text-right">{row.total_marks}</td>
                                                    <td className="text-right">{row.obtained_marks}</td>
                                                    <td className="text-right">{row.percentage}%</td>
                                                    <td className={`text-center font-semibold ${gradeColor(row.grade)}`}>{row.grade}</td>
                                                </tr>
                                            ))}
                                            <tr className="border-t-2 border-gray-300 font-bold bg-gray-50">
                                                <td>Total</td>
                                                <td className="text-right">{studentResult.total_marks}</td>
                                                <td className="text-right">{studentResult.obtained_marks}</td>
                                                <td className={`text-right ${gradeColor(studentResult.overall_grade)}`}>{studentResult.overall_percentage}%</td>
                                                <td className={`text-center ${gradeColor(studentResult.overall_grade)}`}>{studentResult.overall_grade}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* ── RIGHT: Preview ── */}
                <div className="card">
                    <div className="card-body h-full flex flex-col">
                        <div className="flex items-center justify-between mb-3">
                            <div>
                                <p className="font-semibold text-gray-800">Result Card Preview</p>
                                <p className="text-xs text-gray-400 mt-0.5">Live card preview for selected student.</p>
                            </div>
                            {form.student_id && form.exam_type && form.academic_year && form.term && (
                                <a href={singleCardUrl()} target="_blank" rel="noreferrer"
                                    className="btn-secondary text-sm flex items-center gap-1">
                                    <ArrowTopRightOnSquareIcon className="w-4 h-4" /> Open
                                </a>
                            )}
                        </div>

                        {form.student_id && form.exam_type && form.academic_year && form.term && studentResult ? (
                            <iframe
                                src={singleCardUrl()}
                                className="flex-1 w-full rounded border border-gray-200 min-h-[500px]"
                                title="Result Card Preview"
                            />
                        ) : (
                            <div className="flex-1 flex items-center justify-center text-center p-6 min-h-[300px]">
                                <p className="text-sm text-gray-400">
                                    Select filters and click <span className="font-semibold text-gray-700">Preview Result</span> to load result card preview.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

            </div>
        </AppLayout>
    );
}
