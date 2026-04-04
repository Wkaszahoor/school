import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { PrinterIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import type { PageProps, StudentReportCard } from '@/types';

interface Props extends PageProps {
    reportData: StudentReportCard[];
    filters: {
        class_id: string | null;
        exam_type: string;
        academic_year: string;
        term: string;
    };
    examTypes: Record<string, string>;
    terms: Record<string, string>;
    classes: Array<{ id: number; class: string; section: string | null }>;
}

const getGradeColor = (percentage: number): string => {
    if (percentage >= 80) return 'text-emerald-600';
    if (percentage >= 70) return 'text-emerald-500';
    if (percentage >= 60) return 'text-blue-600';
    if (percentage >= 50) return 'text-amber-600';
    return 'text-red-600';
};

const getGradeBg = (percentage: number): string => {
    if (percentage >= 80) return 'bg-emerald-50';
    if (percentage >= 70) return 'bg-emerald-50';
    if (percentage >= 60) return 'bg-blue-50';
    if (percentage >= 50) return 'bg-amber-50';
    return 'bg-red-50';
};

const getPassFailBgColor = (passFail: 'PASS' | 'FAIL'): string => {
    return passFail === 'PASS' ? 'bg-gradient-to-r from-emerald-100 to-green-100' : 'bg-gradient-to-r from-red-100 to-rose-100';
};

const getPassFailTextColor = (passFail: 'PASS' | 'FAIL'): string => {
    return passFail === 'PASS' ? 'text-emerald-800' : 'text-red-800';
};

const getInitials = (name: string): string => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

export default function ReportCards({ reportData, filters, examTypes, terms, classes }: Props) {
    const [hoveredCard, setHoveredCard] = useState<number | null>(null);
    const handlePrint = () => window.print();

    const classLabel = filters.class_id
        ? classes.find(c => c.id === Number(filters.class_id))
            ? `${classes.find(c => c.id === Number(filters.class_id))?.class}${classes.find(c => c.id === Number(filters.class_id))?.section ? ` — ${classes.find(c => c.id === Number(filters.class_id))?.section}` : ''}`
            : 'All Classes'
        : 'All Classes';

    return (
        <>
            <Head title="Report Cards">
                <style>{`
                    @media print {
                        * {
                            box-shadow: none !important;
                            margin: 0;
                            padding: 0;
                        }
                        html { margin: 0; padding: 0; }
                        body { margin: 0; padding: 0; background: white !important; }
                        .no-print { display: none !important; }

                        .report-card {
                            page-break-after: always;
                            page-break-inside: avoid;
                            box-shadow: none !important;
                            margin: 0 !important;
                            padding: 0 !important;
                            border-radius: 0 !important;
                            border: none;
                            background: white !important;
                            width: 100%;
                        }
                        .report-card:last-child { page-break-after: avoid; }

                        .report-card > div {
                            page-break-inside: avoid;
                        }

                        /* Header styling for print */
                        .report-card > div:first-child {
                            margin-bottom: 0;
                            padding: 20mm 15mm !important;
                            background: white !important;
                            border: 1px solid #333 !important;
                            color: #000 !important;
                        }

                        /* Student info section */
                        .report-card > div:nth-child(2) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                        }

                        /* Marks table section */
                        .report-card > div:nth-child(3) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                        }

                        table {
                            width: 100% !important;
                            border-collapse: collapse !important;
                        }

                        th, td {
                            border: 1px solid #333 !important;
                            padding: 6px 8px !important;
                            text-align: left;
                        }

                        th {
                            background: #333 !important;
                            color: white !important;
                            font-weight: bold !important;
                        }

                        /* Remarks section */
                        .report-card > div:nth-child(4) {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                            page-break-inside: avoid;
                        }

                        /* Pass/Fail badge section */
                        .report-card > div:nth-child(5) {
                            margin-bottom: 0;
                            padding: 8mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                        }

                        /* Signature section */
                        .report-card > div:last-child {
                            margin-bottom: 0;
                            padding: 10mm 15mm !important;
                            background: white !important;
                            border: 1px solid #ddd !important;
                            page-break-inside: avoid;
                        }

                        img {
                            max-width: 100%;
                            height: auto;
                        }

                        .text-gradient-to-r,
                        .bg-gradient-to-r,
                        .bg-gradient-to-br {
                            background: white !important;
                        }

                        h3 {
                            margin: 0 0 8px 0 !important;
                            font-size: 14px !important;
                        }

                        p {
                            margin: 2px 0 !important;
                            font-size: 11px !important;
                        }
                    }

                    @page {
                        size: A4 portrait;
                        margin: 10mm;
                    }

                    @media screen {
                        body { background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%); }
                        .report-card { background: white; margin: 24px 0; }
                    }
                `}</style>
            </Head>

            {/* Print toolbar — hidden during printing */}
            <div className="no-print sticky top-0 z-50 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white px-8 py-4 flex items-center justify-between shadow-2xl backdrop-blur-sm">
                <div className="flex items-center gap-6">
                    <div>
                        <p className="font-bold text-lg tracking-wide">KORT School — Report Cards</p>
                        <p className="text-slate-300 text-xs mt-2 space-x-2">
                            <span>{examTypes[filters.exam_type]}</span>
                            <span className="text-slate-500">•</span>
                            <span>{terms[filters.term]}</span>
                            <span className="text-slate-500">•</span>
                            <span>{filters.academic_year}</span>
                            <span className="text-slate-500">•</span>
                            <span>{classLabel}</span>
                        </p>
                    </div>
                    <div className="text-slate-300 text-sm border-l border-slate-600 pl-6">
                        <span className="bg-slate-700 px-3 py-1 rounded-full text-xs font-semibold">
                            {reportData.length} student{reportData.length !== 1 ? 's' : ''}
                        </span>
                    </div>
                </div>
                <button
                    onClick={handlePrint}
                    className="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm hover:from-blue-600 hover:to-blue-700 active:scale-95 flex items-center gap-2 transition-all duration-200 shadow-lg hover:shadow-xl"
                >
                    <PrinterIcon className="w-5 h-5" />
                    Print / Save as PDF
                </button>
            </div>

            {/* Report cards */}
            <div className="p-6 min-h-screen print:p-0">
                <div className="max-w-5xl mx-auto print:max-w-full">
                    {reportData.length === 0 ? (
                        <div className="bg-white rounded-2xl p-16 text-center text-gray-500 shadow-lg">
                            <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-6">
                                <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p className="text-xl font-semibold text-gray-700">No approved results found</p>
                            <p className="text-gray-500 mt-2">Check back once results are approved by the principal.</p>
                        </div>
                    ) : (
                        reportData.map((entry) => (
                            <div
                                key={entry.student.id}
                                className="report-card rounded-2xl shadow-xl overflow-hidden mb-8 transition-all duration-300 hover:shadow-2xl"
                                onMouseEnter={() => setHoveredCard(entry.student.id)}
                                onMouseLeave={() => setHoveredCard(null)}
                            >
                            {/* Header */}
                            <div className="bg-gradient-to-br from-indigo-900 via-blue-900 to-blue-800 text-white px-10 py-8 relative overflow-hidden print:bg-white print:text-black print:py-4 print:px-6">
                                <div className="absolute top-0 right-0 w-40 h-40 bg-blue-500 rounded-full opacity-10 transform translate-x-20 -translate-y-20 print:hidden"></div>
                                <div className="absolute bottom-0 left-0 w-32 h-32 bg-indigo-400 rounded-full opacity-10 transform -translate-x-16 translate-y-16 print:hidden"></div>
                                <div className="text-center relative z-10">
                                    {/* Logo */}
                                    <div className="flex justify-center mb-4 print:mb-2">
                                        <div className="h-16 w-16 rounded-full bg-white flex items-center justify-center text-2xl font-black text-blue-900 print:h-12 print:w-12 print:text-lg">
                                            K
                                        </div>
                                    </div>
                                    <p className="font-black text-sm tracking-widest text-blue-100 print:text-gray-900 print:text-xs">KORT SCHOOL MANAGEMENT SYSTEM</p>
                                    <p className="text-xs text-blue-200 mt-1 print:text-gray-600 print:mt-0">Providing quality healthcare and hope</p>
                                    <p className="text-3xl font-black mt-5 tracking-tight print:text-2xl print:mt-2">PROGRESS REPORT CARD</p>
                                    <div className="text-sm mt-4 text-blue-100 space-y-1.5 print:text-xs print:text-gray-700 print:mt-2 print:space-y-0.5">
                                        <p className="font-semibold print:text-gray-900">{examTypes[filters.exam_type]} Examination | {terms[filters.term]} | Session {filters.academic_year}</p>
                                        <p className="text-blue-200 print:text-gray-600">Date: {new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' })}</p>
                                    </div>
                                </div>
                            </div>

                            {/* Student Info */}
                            <div className="border-b-2 border-gray-100 bg-gradient-to-br from-gray-50 to-gray-100 print:bg-white print:border-gray-300 print:py-2 print:px-6">
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-8 p-10 print:gap-4 print:p-0">
                                    {/* Photo */}
                                    <div className="flex justify-center md:justify-start">
                                        {entry.student.photo ? (
                                            <img
                                                src={`/storage/${entry.student.photo}`}
                                                alt={entry.student.full_name}
                                                className="w-28 h-28 rounded-xl border-4 border-white object-cover shadow-lg print:w-20 print:h-20 print:rounded-sm print:border-2"
                                            />
                                        ) : (
                                            <div className="w-28 h-28 rounded-xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white flex items-center justify-center font-black text-3xl border-4 border-white shadow-lg print:w-20 print:h-20 print:rounded-sm print:text-lg print:border-2">
                                                {getInitials(entry.student.full_name)}
                                            </div>
                                        )}
                                    </div>

                                    {/* Student Details */}
                                    <div className="md:col-span-3">
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 print:grid-cols-2 print:gap-2">
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Name</p>
                                                <p className="font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0">{entry.student.full_name}</p>
                                            </div>
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Admission No.</p>
                                                <p className="font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0">{entry.student.admission_no}</p>
                                            </div>
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Class</p>
                                                <p className="font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0">
                                                    {entry.student.class ? `${entry.student.class.class}${entry.student.class.section ? ` — ${entry.student.class.section}` : ''}` : 'N/A'}
                                                </p>
                                            </div>
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Stream/Group</p>
                                                <p className="font-bold text-blue-600 text-lg mt-1 print:text-gray-900 print:text-sm print:mt-0">{entry.student.stream || 'General'}</p>
                                            </div>
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Father's Name</p>
                                                <p className="font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0">{entry.student.father_name || 'N/A'}</p>
                                            </div>
                                            <div className="bg-white rounded-lg p-4 shadow-sm print:bg-white print:rounded-none print:p-1.5 print:border print:border-gray-300">
                                                <p className="text-gray-500 text-xs font-bold uppercase tracking-wide print:text-gray-600 print:text-xs print:tracking-normal">Exam Type</p>
                                                <p className="font-bold text-gray-900 text-lg mt-1 print:text-sm print:mt-0">{examTypes[filters.exam_type]}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Marks Table */}
                            <div className="p-10 print:px-6 print:py-3">
                                <h3 className="text-lg font-bold text-gray-900 mb-6 print:text-base print:mb-2">Academic Performance</h3>
                                <div className="overflow-hidden rounded-xl border border-gray-200 shadow-md print:rounded-none print:border-gray-300 print:shadow-none">
                                    <table className="w-full border-collapse print:text-xs">
                                        <thead>
                                            <tr className="bg-gradient-to-r from-slate-800 to-slate-700 border-b border-slate-600 print:bg-gray-800 print:text-white">
                                                <th className="text-left py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">Subject</th>
                                                <th className="text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">Obtained</th>
                                                <th className="text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">Total</th>
                                                <th className="text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">%</th>
                                                <th className="text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">Grade</th>
                                                <th className="text-center py-4 px-6 font-bold text-white text-sm tracking-wide print:py-2 print:px-3 print:text-xs print:font-bold">GPA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {entry.results.map((result, idx) => (
                                                <tr key={idx} className={`border-b border-gray-200 transition-colors ${getGradeBg(result.percentage)} hover:bg-opacity-75 print:bg-white print:border-gray-300`}>
                                                    <td className="py-4 px-6 text-gray-900 font-semibold print:py-2 print:px-3 print:font-normal print:text-xs">{result.subject_name}</td>
                                                    <td className="py-4 px-6 text-center text-gray-900 font-medium print:py-2 print:px-3 print:text-xs">{result.obtained_marks}</td>
                                                    <td className="py-4 px-6 text-center text-gray-900 font-medium print:py-2 print:px-3 print:text-xs">{result.total_marks}</td>
                                                    <td className={`py-4 px-6 text-center font-bold text-base print:py-2 print:px-3 print:font-normal print:text-xs print:text-gray-900 ${getGradeColor(result.percentage)}`}>
                                                        {result.percentage.toFixed(1)}%
                                                    </td>
                                                    <td className={`py-4 px-6 text-center font-black text-lg print:py-2 print:px-3 print:font-bold print:text-xs print:text-gray-900 ${getGradeColor(result.percentage)}`}>
                                                        {result.grade}
                                                    </td>
                                                    <td className="py-4 px-6 text-center text-gray-900 font-bold print:py-2 print:px-3 print:font-normal print:text-xs">{Number(result.gpa_point).toFixed(2)}</td>
                                                </tr>
                                            ))}
                                            {/* Total Row */}
                                            <tr className="bg-gradient-to-r from-indigo-100 to-blue-100 border-t-2 border-indigo-300 font-black print:bg-gray-100 print:border-gray-400">
                                                <td className="py-5 px-6 text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold">TOTAL</td>
                                                <td className="py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold">{entry.summary.total_obtained}</td>
                                                <td className="py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold">{entry.summary.total_possible}</td>
                                                <td className={`py-5 px-6 text-center text-lg print:py-2 print:px-3 print:text-xs print:font-bold print:text-gray-900 ${getGradeColor(entry.summary.overall_percentage)}`}>
                                                    {entry.summary.overall_percentage.toFixed(1)}%
                                                </td>
                                                <td className={`py-5 px-6 text-center text-2xl print:py-2 print:px-3 print:text-xs print:font-bold print:text-gray-900 ${getGradeColor(entry.summary.overall_percentage)}`}>
                                                    {entry.summary.overall_grade}
                                                </td>
                                                <td className="py-5 px-6 text-center text-gray-900 print:py-2 print:px-3 print:text-xs print:font-bold">{Number(entry.summary.average_gpa).toFixed(2)}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Remarks Section */}
                            {entry.results.some(r => r.class_teacher_remarks || r.principal_remarks) && (
                                <div className="px-10 py-8 border-b-2 border-gray-100 bg-blue-50 print:bg-white print:border-gray-300 print:py-4">
                                    <h3 className="text-lg font-bold text-gray-900 mb-4 print:text-base print:mb-3">Teacher Remarks</h3>
                                    <div className="space-y-4 print:space-y-2">
                                        {entry.results.map((result, idx) => (
                                            (result.class_teacher_remarks || result.principal_remarks) && (
                                                <div key={idx} className="bg-white rounded-lg p-4 border border-gray-200 print:bg-white print:rounded-none print:p-2 print:border-gray-300 print:mb-2">
                                                    <p className="font-semibold text-gray-900 mb-2 print:text-sm print:mb-1">{result.subject_name}</p>
                                                    {result.class_teacher_remarks && (
                                                        <p className="text-sm text-gray-700 mb-2 print:text-xs print:mb-1">
                                                            <span className="font-semibold text-blue-600 print:text-gray-900">Class Teacher: </span>
                                                            <em>{result.class_teacher_remarks}</em>
                                                        </p>
                                                    )}
                                                    {result.principal_remarks && (
                                                        <p className="text-sm text-gray-700 print:text-xs">
                                                            <span className="font-semibold text-indigo-600 print:text-gray-900">Principal: </span>
                                                            <em>{result.principal_remarks}</em>
                                                        </p>
                                                    )}
                                                </div>
                                            )
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Pass/Fail Badge */}
                            <div className="flex justify-center py-10 border-b-2 border-gray-100 bg-gray-50 print:py-3 print:border-gray-300 print:bg-white">
                                <div className={`${getPassFailBgColor(entry.summary.pass_fail)} ${getPassFailTextColor(entry.summary.pass_fail)} px-12 py-4 rounded-full font-black text-2xl shadow-lg transform hover:scale-105 transition-transform print:px-6 print:py-2 print:rounded-none print:text-sm print:shadow-none print:border print:border-gray-400`}>
                                    {entry.summary.pass_fail === 'PASS' ? '✓ PASS' : '✗ FAIL'}
                                </div>
                            </div>

                            {/* Signature Strip */}
                            <div className="px-10 py-8 bg-gradient-to-r from-gray-50 to-gray-100 print:bg-white print:px-6 print:py-4">
                                <p className="text-xs font-bold text-gray-500 uppercase tracking-widest mb-6 print:mb-3 print:text-gray-700">Official Signatures</p>
                                <div className="grid grid-cols-2 gap-12 text-center print:gap-4">
                                    <div className="space-y-4 print:space-y-2">
                                        <div className="h-16 border-b-2 border-gray-800 mx-auto w-4/5 print:h-8 print:border-b print:border-gray-700"></div>
                                        <p className="text-sm font-bold text-gray-900 print:text-xs">Class Teacher</p>
                                        <p className="text-xs text-gray-500 print:text-gray-600">Signature & Date</p>
                                    </div>
                                    <div className="space-y-4 print:space-y-2">
                                        <div className="h-16 border-b-2 border-gray-800 mx-auto w-4/5 print:h-8 print:border-b print:border-gray-700"></div>
                                        <p className="text-sm font-bold text-gray-900 print:text-xs">Principal</p>
                                        <p className="text-xs text-gray-500 print:text-gray-600">Signature & Date</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                )}
                </div>
            </div>
        </>
    );
}
