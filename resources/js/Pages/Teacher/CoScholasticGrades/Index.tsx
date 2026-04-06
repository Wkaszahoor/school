import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

declare const route: (name: string, params?: any) => string;

interface Student { id: number; full_name: string; admission_no: string; }
interface MyClass { id: number; class: string; section: string | null; }

interface Props extends PageProps {
    myClass: MyClass | null;
    students: Student[];
    grades: Record<number, Record<string, { term1_grade: string | null; term2_grade: string | null }>>;
    activities: Record<string, string>;
    academicYears: string[];
    examTypes: Record<string, string>;
    terms: Record<string, string>;
    filters: { academic_year: string; exam_type: string; term: string };
}

const GRADE_OPTIONS = ['', 'A', 'B', 'C'];

export default function CoScholasticGradesIndex({
    myClass, students, grades, activities, academicYears, examTypes, terms, filters,
}: Props) {
    const [filter, setFilter] = useState(filters);

    // Build editable grid: gradeData[studentId][activity] = { term1, term2 }
    const buildInitial = () => {
        const data: Record<string, Record<string, { term1: string; term2: string }>> = {};
        students.forEach(s => {
            data[s.id] = {};
            Object.keys(activities).forEach(act => {
                const existing = grades[s.id]?.[act];
                data[s.id][act] = {
                    term1: existing?.term1_grade ?? '',
                    term2: existing?.term2_grade ?? '',
                };
            });
        });
        return data;
    };

    const [gradeData, setGradeData] = useState<Record<string, Record<string, { term1: string; term2: string }>>>(buildInitial);
    const [saving, setSaving] = useState(false);

    const applyFilters = () => {
        router.get(route('teacher.co-scholastic.index'), filter, { preserveState: false });
    };

    const setGrade = (studentId: number, activity: string, term: 'term1' | 'term2', value: string) => {
        setGradeData(prev => ({
            ...prev,
            [studentId]: {
                ...prev[studentId],
                [activity]: { ...prev[studentId]?.[activity], [term]: value },
            },
        }));
    };

    const handleSave = () => {
        if (!filter.academic_year || !filter.exam_type || !filter.term) {
            alert('Please select all filters before saving.');
            return;
        }
        setSaving(true);
        router.post(route('teacher.co-scholastic.store'), {
            academic_year: filter.academic_year,
            exam_type: filter.exam_type,
            term: filter.term,
            grades: gradeData,
        }, {
            onFinish: () => setSaving(false),
        });
    };

    const filtersReady = filter.academic_year && filter.exam_type && filter.term;

    return (
        <AppLayout title="Co-Scholastic Grades">
            <Head title="Co-Scholastic Grades" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Co-Scholastic Grades</h1>
                    <p className="page-subtitle">
                        Enter 3-point grading (A / B / C) for your class students.
                        {myClass && <span className="ml-2 font-semibold text-indigo-600">Class: {myClass.class}{myClass.section ? `-${myClass.section}` : ''}</span>}
                    </p>
                </div>
            </div>

            {!myClass ? (
                <div className="card">
                    <div className="card-body text-center py-16 text-gray-500">
                        <p className="text-lg font-semibold">Not Assigned as Class Teacher</p>
                        <p className="text-sm mt-2">Only class teachers can enter co-scholastic grades.</p>
                    </div>
                </div>
            ) : (
                <>
                    {/* Filters */}
                    <div className="card mb-5">
                        <div className="card-body">
                            <div className="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div>
                                    <label className="form-label">Session</label>
                                    <select className="form-select" value={filter.academic_year} onChange={e => setFilter(f => ({ ...f, academic_year: e.target.value }))}>
                                        <option value="">Select session</option>
                                        {academicYears.map(y => <option key={y} value={y}>{y}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="form-label">Exam Type</label>
                                    <select className="form-select" value={filter.exam_type} onChange={e => setFilter(f => ({ ...f, exam_type: e.target.value }))}>
                                        <option value="">Select exam type</option>
                                        {Object.entries(examTypes).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </div>
                                <div>
                                    <label className="form-label">Term</label>
                                    <select className="form-select" value={filter.term} onChange={e => setFilter(f => ({ ...f, term: e.target.value }))}>
                                        <option value="">Select term</option>
                                        {Object.entries(terms).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </div>
                                <div className="flex items-end">
                                    <button onClick={applyFilters} className="btn-primary w-full">Load Students</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Grade Table */}
                    {filtersReady && students.length > 0 ? (
                        <div className="card">
                            <div className="card-body p-0">
                                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                                    <div>
                                        <p className="font-semibold text-gray-800">Co-Scholastic Grading</p>
                                        <p className="text-xs text-gray-500 mt-0.5">3-Point Scale: A = Excellent &nbsp;·&nbsp; B = Good &nbsp;·&nbsp; C = Satisfactory</p>
                                    </div>
                                    <button
                                        onClick={handleSave}
                                        disabled={saving}
                                        className="btn-primary disabled:opacity-60"
                                    >
                                        {saving ? 'Saving…' : 'Save All Grades'}
                                    </button>
                                </div>

                                <div className="overflow-x-auto">
                                    <table className="w-full text-xs border-collapse">
                                        <thead>
                                            <tr className="bg-gray-50 border-b border-gray-200">
                                                <th className="text-left px-4 py-3 font-semibold text-gray-700 sticky left-0 bg-gray-50 min-w-[180px]">#&nbsp; Student</th>
                                                {Object.entries(activities).map(([key, label]) => (
                                                    <th key={key} className="px-2 py-3 font-semibold text-gray-700 text-center min-w-[120px]" colSpan={2}>
                                                        {label}
                                                    </th>
                                                ))}
                                            </tr>
                                            <tr className="bg-gray-50 border-b border-gray-200 text-gray-500">
                                                <th className="sticky left-0 bg-gray-50 px-4 py-1"></th>
                                                {Object.keys(activities).map(key => (
                                                    <React.Fragment key={key}>
                                                        <th className="px-2 py-1 text-center font-normal">Term-I</th>
                                                        <th className="px-2 py-1 text-center font-normal border-r border-gray-200">Term-II</th>
                                                    </React.Fragment>
                                                ))}
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {students.map((student, idx) => (
                                                <tr key={student.id} className={idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'}>
                                                    <td className="px-4 py-2 sticky left-0 bg-inherit border-r border-gray-100">
                                                        <p className="font-medium text-gray-900">{student.full_name}</p>
                                                        <p className="text-gray-400 text-xs">{student.admission_no}</p>
                                                    </td>
                                                    {Object.keys(activities).map(act => (
                                                        <React.Fragment key={act}>
                                                            <td className="px-1 py-2 text-center">
                                                                <select
                                                                    value={gradeData[student.id]?.[act]?.term1 ?? ''}
                                                                    onChange={e => setGrade(student.id, act, 'term1', e.target.value)}
                                                                    className={`w-16 text-center text-xs rounded border px-1 py-1 font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-400 ${
                                                                        gradeData[student.id]?.[act]?.term1 === 'A' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' :
                                                                        gradeData[student.id]?.[act]?.term1 === 'B' ? 'border-blue-400 bg-blue-50 text-blue-700' :
                                                                        gradeData[student.id]?.[act]?.term1 === 'C' ? 'border-amber-400 bg-amber-50 text-amber-700' :
                                                                        'border-gray-200 text-gray-400'
                                                                    }`}
                                                                >
                                                                    {GRADE_OPTIONS.map(g => <option key={g} value={g}>{g || '—'}</option>)}
                                                                </select>
                                                            </td>
                                                            <td className="px-1 py-2 text-center border-r border-gray-100">
                                                                <select
                                                                    value={gradeData[student.id]?.[act]?.term2 ?? ''}
                                                                    onChange={e => setGrade(student.id, act, 'term2', e.target.value)}
                                                                    className={`w-16 text-center text-xs rounded border px-1 py-1 font-semibold focus:outline-none focus:ring-1 focus:ring-indigo-400 ${
                                                                        gradeData[student.id]?.[act]?.term2 === 'A' ? 'border-emerald-400 bg-emerald-50 text-emerald-700' :
                                                                        gradeData[student.id]?.[act]?.term2 === 'B' ? 'border-blue-400 bg-blue-50 text-blue-700' :
                                                                        gradeData[student.id]?.[act]?.term2 === 'C' ? 'border-amber-400 bg-amber-50 text-amber-700' :
                                                                        'border-gray-200 text-gray-400'
                                                                    }`}
                                                                >
                                                                    {GRADE_OPTIONS.map(g => <option key={g} value={g}>{g || '—'}</option>)}
                                                                </select>
                                                            </td>
                                                        </React.Fragment>
                                                    ))}
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="px-6 py-4 border-t border-gray-100 flex justify-end">
                                    <button onClick={handleSave} disabled={saving} className="btn-primary disabled:opacity-60">
                                        {saving ? 'Saving…' : 'Save All Grades'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    ) : filtersReady && students.length === 0 ? (
                        <div className="card"><div className="card-body text-center py-12 text-gray-500">No active students in your class.</div></div>
                    ) : (
                        <div className="card"><div className="card-body text-center py-12 text-gray-400">Select session, exam type, and term, then click <strong>Load Students</strong>.</div></div>
                    )}
                </>
            )}
        </AppLayout>
    );
}
