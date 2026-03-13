import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Student, TeacherAssignment } from '@/types';

interface Props extends PageProps {
    assignments: TeacherAssignment[];
    students: Student[];
    examTypes: string[];
    filters: { class_id?: string };
    myClassTeacherAssignment?: { id: string };
    allSubjectsInClass?: { id: string; subject_name: string }[];
}

interface ResultRow {
    student_id: number;
    subject_id?: string;
    total_marks: string;
    obtained_marks: string;
}

export default function TeacherResultsCreate({ assignments, students, examTypes, filters, myClassTeacherAssignment, allSubjectsInClass }: Props) {
    const [showStudentsModal, setShowStudentsModal] = useState(false);
    const [defaultTotalMarks, setDefaultTotalMarks] = useState('100');

    const uniqueClasses = Array.from(
        new Map(assignments.map(a => [a.class_id, a.class])).entries()
    ).map(([, cls]) => cls!).filter(Boolean);

    const getClassTeacher = (classId: string) => {
        return assignments.find(a => String(a.class_id) === classId)?.class_teacher_name ?? 'Not assigned';
    };

    const subjectsForClass = (classId: string) => {
        // If viewing own class as class teacher, show all subjects in class
        const isOwnClass = String(classId) === String(myClassTeacherAssignment?.id);
        if (isOwnClass && allSubjectsInClass && allSubjectsInClass.length > 0) {
            console.log('Loading subjects for class teacher:', allSubjectsInClass);
            return allSubjectsInClass;
        }
        // Otherwise show only teacher's assigned subjects
        return assignments.filter(a => String(a.class_id) === classId).map(a => a.subject!).filter(Boolean);
    };

    const getSubjectTeacher = (classId: string, subjectId: string) => {
        const assignment = assignments.find(a =>
            String(a.class_id) === classId && String(a.subject_id) === subjectId
        );
        return assignment?.teacher?.name ?? 'Not assigned';
    };

    const { data, setData, post, processing, errors } = useForm<{
        class_id: string;
        subject_id: string;
        exam_type: string;
        academic_year: string;
        term: string;
        results: ResultRow[];
    }>({
        class_id: filters.class_id ?? myClassTeacherAssignment?.id ?? '',
        subject_id: '',
        exam_type: '',
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        term: '',
        results: [],
    });

    // Load students when class changes
    const handleClassChange = (classId: string) => {
        setData('class_id', classId);
        router.get(route('teacher.results.create'), { class_id: classId }, {
            preserveState: true, replace: true, only: ['students'],
        });
    };

    // For subject-by-subject view only (legacy support)
    const resultRows: ResultRow[] = data.subject_id ? students.map(s => ({
        student_id: s.id,
        subject_id: data.subject_id,
        total_marks: data.results.find(r => r.student_id === s.id && r.subject_id === data.subject_id)?.total_marks ?? '100',
        obtained_marks: data.results.find(r => r.student_id === s.id && r.subject_id === data.subject_id)?.obtained_marks ?? '',
    })) : [];

    const updateRow = (studentId: number, field: 'total_marks' | 'obtained_marks', value: string, subjectId?: string) => {
        const existingIndex = data.results.findIndex(r =>
            r.student_id === studentId && (!subjectId || r.subject_id === subjectId)
        );

        let updatedResults = [...data.results];

        if (existingIndex >= 0) {
            // Update existing result
            updatedResults[existingIndex] = {
                ...updatedResults[existingIndex],
                [field]: value
            };
        } else if (subjectId) {
            // Create new result for this student-subject combo
            updatedResults.push({
                student_id: studentId,
                subject_id: subjectId,
                total_marks: field === 'total_marks' ? value : '100',
                obtained_marks: field === 'obtained_marks' ? value : ''
            });
        }

        setData('results', updatedResults);
    };

    interface SubjectMarks {
        [studentId: number]: {
            [subjectId: string]: { obtained_marks: string; total_marks: string };
        };
    }

    const buildSubjectMarksMatrix = (): SubjectMarks => {
        const matrix: SubjectMarks = {};
        students.forEach(s => {
            matrix[s.id] = {};
            subjectsForClass(data.class_id).forEach(subject => {
                const result = data.results.find(
                    r => r.student_id === s.id
                );
                matrix[s.id][subject.id] = {
                    obtained_marks: result?.obtained_marks ?? '',
                    total_marks: result?.total_marks ?? '100',
                };
            });
        });
        return matrix;
    };

    const calculateGrade = (percentage: number): { grade: string; color: string } => {
        if (percentage >= 90) return { grade: 'A', color: 'text-emerald-700' };
        if (percentage >= 80) return { grade: 'B', color: 'text-emerald-600' };
        if (percentage >= 70) return { grade: 'C', color: 'text-blue-600' };
        if (percentage >= 60) return { grade: 'D', color: 'text-yellow-600' };
        return { grade: 'F', color: 'text-red-600' };
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // For subject-by-subject entry (subject_id selected)
        if (data.subject_id) {
            setData('results', resultRows);
            post(route('teacher.results.store'));
        }
        // For comprehensive matrix (class teacher, no subject selected)
        else if (!data.subject_id && data.results.length > 0) {
            // Submit all results at once - backend will handle per-subject submission
            post(route('teacher.results.store'));
        }
        else {
            alert('Please select a subject or enter marks in the table');
        }
    };

    const applyTotalMarksToAll = () => {
        if (!data.subject_id) {
            alert('Please select a subject first');
            return;
        }
        const updatedResults = data.results.map(r => {
            if (r.subject_id === data.subject_id) {
                return { ...r, total_marks: defaultTotalMarks };
            }
            return r;
        });
        // Also set default for any students without an entry
        const resultMap = new Map(updatedResults.map(r => [r.student_id, r]));
        students.forEach(s => {
            if (!resultMap.has(s.id)) {
                updatedResults.push({
                    student_id: s.id,
                    subject_id: data.subject_id,
                    total_marks: defaultTotalMarks,
                    obtained_marks: ''
                });
            }
        });
        setData('results', updatedResults);
    };

    const terms = ['Term 1', 'Term 2', 'Term 3'];

    return (
        <AppLayout title="Enter Results">
            <Head title="Enter Results" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('teacher.results.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Enter Results</h1>
                        <p className="page-subtitle">Grade entry for principal approval</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-5">
                    {/* Config panel */}
                    <div className="space-y-5">
                        <div className="card">
                            <div className="card-header"><p className="card-title">Exam Details</p></div>
                            <div className="card-body space-y-4">
                                <div className="form-group">
                                    <label className="form-label">Class <span className="text-red-500">*</span></label>
                                    <select className="form-select" value={data.class_id}
                                            onChange={e => { handleClassChange(e.target.value); setData('subject_id', ''); }}>
                                        <option value="">Select class…</option>
                                        {uniqueClasses.map(c => (
                                            <option key={c.id} value={c.id}>
                                                {c.class}{c.section ? ` — ${c.section}` : ''}
                                            </option>
                                        ))}
                                    </select>
                                    {data.class_id && (
                                        <p className="text-xs text-gray-500 mt-2">
                                            <span className="font-semibold">Class Teacher:</span> {getClassTeacher(data.class_id)}
                                        </p>
                                    )}
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Subject <span className="text-red-500">*</span></label>
                                    <select className="form-select" value={data.subject_id}
                                            onChange={e => setData('subject_id', e.target.value)}
                                            disabled={!data.class_id}>
                                        <option value="">Select subject…</option>
                                        {subjectsForClass(data.class_id).map(s => (
                                            <option key={s.id} value={s.id}>{s.subject_name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Exam Type <span className="text-red-500">*</span></label>
                                    <select className="form-select" value={data.exam_type}
                                            onChange={e => setData('exam_type', e.target.value)}>
                                        <option value="">Select…</option>
                                        {examTypes.map(t => <option key={t} value={t}>{t}</option>)}
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Term <span className="text-red-500">*</span></label>
                                    <select className="form-select" value={data.term}
                                            onChange={e => setData('term', e.target.value)}>
                                        <option value="">Select…</option>
                                        {terms.map(t => <option key={t} value={t}>{t}</option>)}
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Academic Year <span className="text-red-500">*</span></label>
                                    <input className="form-input" value={data.academic_year}
                                           onChange={e => setData('academic_year', e.target.value)}
                                           placeholder="e.g. 2025-2026" />
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Default Total Marks</label>
                                    <div className="flex gap-2">
                                        <input type="number" min="0"
                                               className="form-input"
                                               value={defaultTotalMarks}
                                               onChange={e => setDefaultTotalMarks(e.target.value)}
                                               placeholder="e.g., 100" />
                                        <button type="button" onClick={applyTotalMarksToAll}
                                                className="btn-secondary whitespace-nowrap"
                                                disabled={!data.subject_id}>
                                            Apply
                                        </button>
                                    </div>
                                    <p className="text-xs text-gray-500 mt-1">Set default total marks for all students</p>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body space-y-3">
                                <button type="submit" disabled={processing || students.length === 0 || !data.exam_type || !data.academic_year || !data.term || (data.subject_id ? false : data.results.length === 0)}
                                        className="btn-primary w-full">
                                    {processing ? (
                                        <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Saving…</>
                                    ) : (
                                        <><CheckIcon className="w-4 h-4" /> Save Results</>
                                    )}
                                </button>
                                {students.length > 0 && (
                                    <button type="button" onClick={() => setShowStudentsModal(true)}
                                            className="btn-secondary w-full">
                                        View Students ({students.length})
                                    </button>
                                )}
                                <Link href={route('teacher.results.index')} className="btn-secondary w-full text-center">
                                    Cancel
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Grade sheet */}
                    <div className="lg:col-span-3">
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title">Grade Sheet</p>
                                <span className="text-xs text-gray-400">{students.length} students</span>
                            </div>
                            {students.length === 0 ? (
                                <div className="card-body">
                                    <div className="empty-state">
                                        <p className="empty-state-text">Select a class to load students</p>
                                    </div>
                                </div>
                            ) : !data.subject_id ? (
                                <div className="card-body">
                                    <div className="empty-state">
                                        <p className="empty-state-text">Select a subject above to enter marks</p>
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    {/* Subject-wise view */}
                                    {data.subject_id && (
                                        <>
                                            <div className="table-wrapper">
                                                <table className="table">
                                                <thead>
                                                    <tr>
                                                        <th>Student</th>
                                                        <th>Admission No.</th>
                                                        <th>Total Marks</th>
                                                        <th>Obtained Marks</th>
                                                        <th>%</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {students.map(s => {
                                                        const row = resultRows.find(r => r.student_id === s.id) ?? {
                                                            student_id: s.id, total_marks: '100', obtained_marks: ''
                                                        };
                                                        const total = parseFloat(row.total_marks);
                                                        const obtained = parseFloat(row.obtained_marks);
                                                        const pct = total > 0 && !isNaN(obtained)
                                                            ? Math.round((obtained / total) * 100) : null;
                                                        return (
                                                            <tr key={s.id}>
                                                                <td className="font-semibold text-gray-900">{s.full_name}</td>
                                                                <td className="font-mono text-sm text-gray-500">{s.admission_no}</td>
                                                                <td>
                                                                    <input type="number" min="0"
                                                                           className="form-input !py-1.5 !text-sm w-24"
                                                                           value={row.total_marks}
                                                                           onChange={e => updateRow(s.id, 'total_marks', e.target.value, String(data.subject_id))} />
                                                                </td>
                                                                <td>
                                                                    <input type="number" min="0"
                                                                           className="form-input !py-1.5 !text-sm w-24"
                                                                           value={row.obtained_marks}
                                                                           placeholder="—"
                                                                           onChange={e => updateRow(s.id, 'obtained_marks', e.target.value, String(data.subject_id))} />
                                                                </td>
                                                                <td>
                                                                    {pct !== null ? (
                                                                        <span className={`font-semibold ${pct >= 50 ? 'text-emerald-600' : 'text-red-600'}`}>
                                                                            {pct}%
                                                                        </span>
                                                                    ) : '—'}
                                                                </td>
                                                            </tr>
                                                        );
                                                    })}
                                                </tbody>
                                            </table>
                                            </div>
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </form>

            {/* Students Modal */}
            {showStudentsModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                    <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                        <div className="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
                            <h2 className="text-lg font-bold text-gray-900">
                                Class Students ({students.length})
                            </h2>
                            <button
                                onClick={() => setShowStudentsModal(false)}
                                className="text-gray-500 hover:text-gray-700 text-2xl leading-none"
                            >
                                ×
                            </button>
                        </div>

                        <div className="p-6">
                            {students.length > 0 ? (
                                <div className="table-wrapper overflow-x-auto">
                                    <table className="table">
                                        <thead>
                                            <tr>
                                                <th>Admission No.</th>
                                                <th>Student Name</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {students.map((student, idx) => (
                                                <tr key={student.id}>
                                                    <td className="font-mono text-sm font-semibold text-gray-900">{student.admission_no}</td>
                                                    <td className="text-gray-700">{student.full_name}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-6 text-gray-500">
                                    No students found in this class
                                </div>
                            )}
                        </div>

                        <div className="border-t px-6 py-4 flex justify-end">
                            <button
                                onClick={() => setShowStudentsModal(false)}
                                className="btn-secondary"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
