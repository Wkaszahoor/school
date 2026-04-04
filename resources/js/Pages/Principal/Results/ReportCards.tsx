import React from 'react';
import { Head } from '@inertiajs/react';
import { PrinterIcon } from '@heroicons/react/24/outline';
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

const gradeScale = [
    { range: '91–100', grade: 'A+' },
    { range: '81–90',  grade: 'A'  },
    { range: '71–80',  grade: 'B+' },
    { range: '61–70',  grade: 'B'  },
    { range: '51–60',  grade: 'C+' },
    { range: '41–50',  grade: 'C'  },
    { range: '33–40',  grade: 'D'  },
    { range: '0–32',   grade: 'F'  },
];

export default function ReportCards({ reportData, filters, examTypes, terms, classes }: Props) {
    const handlePrint = () => window.print();

    const classLabel = filters.class_id
        ? (() => {
            const cls = classes.find(c => c.id === Number(filters.class_id));
            return cls ? `${cls.class}${cls.section ? ` — ${cls.section}` : ''}` : 'All Classes';
          })()
        : 'All Classes';

    return (
        <>
            <Head title="Report Cards">
                <style>{`
                    * { box-sizing: border-box; }

                    @media screen {
                        body { background: #e5e7eb; margin: 0; }
                        .page-wrapper { max-width: 900px; margin: 0 auto; padding: 24px 16px; }
                        .report-card { background: #fff; margin-bottom: 40px; border: 1px solid #ccc; }
                    }

                    @media print {
                        html, body { margin: 0; padding: 0; background: white !important; }
                        .no-print { display: none !important; }
                        .page-wrapper { padding: 0; margin: 0; }
                        .report-card {
                            page-break-after: always;
                            page-break-inside: avoid;
                            margin: 0 !important;
                            border: none !important;
                            box-shadow: none !important;
                        }
                        .report-card:last-child { page-break-after: avoid; }
                    }

                    @page { size: A4 portrait; margin: 8mm; }

                    /* Card internals */
                    .rc-header { border-bottom: 2px solid #1e3a5f; padding: 10px 16px 8px; }
                    .rc-school-name { font-size: 20px; font-weight: 900; text-transform: uppercase; color: #1e3a5f; letter-spacing: 0.5px; text-align: center; }
                    .rc-affil { font-size: 10px; text-align: center; color: #333; margin-top: 2px; }
                    .rc-contact { font-size: 9px; text-align: center; color: #555; margin-top: 1px; }
                    .rc-report-title { text-align: center; margin-top: 6px; }
                    .rc-report-title p { font-size: 13px; font-weight: 700; color: #1e3a5f; }
                    .rc-report-title small { font-size: 11px; color: #333; }

                    /* Student info */
                    .rc-student-info { display: grid; grid-template-columns: 1fr 1fr; gap: 0; border-bottom: 1px solid #555; font-size: 11px; }
                    .rc-info-left, .rc-info-right { padding: 8px 14px; }
                    .rc-info-left { border-right: 1px solid #555; }
                    .rc-info-row { display: flex; gap: 4px; margin-bottom: 4px; }
                    .rc-info-label { font-weight: 600; min-width: 110px; color: #111; }
                    .rc-info-value { color: #222; }

                    /* Marks table */
                    .rc-table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    .rc-table th, .rc-table td { border: 1px solid #555; padding: 4px 8px; text-align: center; }
                    .rc-table th { background: #f0f0f0; font-weight: 700; color: #111; }
                    .rc-table td.subject-col { text-align: left; font-weight: 600; }
                    .rc-table tr.total-row td { background: #e8eaf6; font-weight: 700; }
                    .rc-section-title { font-size: 11px; font-weight: 700; background: #d0d8e8; padding: 4px 8px; border: 1px solid #555; border-bottom: none; }

                    /* Co-scholastic */
                    .rc-coscholastic table { width: 100%; border-collapse: collapse; font-size: 11px; }
                    .rc-coscholastic th, .rc-coscholastic td { border: 1px solid #555; padding: 4px 8px; }
                    .rc-coscholastic th { background: #f0f0f0; font-weight: 700; text-align: center; }

                    /* Footer */
                    .rc-footer { border-top: 1px solid #555; padding: 8px 14px 4px; font-size: 10px; }
                    .rc-signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0; margin-bottom: 8px; }
                    .rc-sig-box { text-align: center; padding: 4px; }
                    .rc-sig-line { border-top: 1px solid #333; margin: 20px 10px 4px; }
                    .rc-grade-scale { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 6px; }
                    .rc-grade-scale th, .rc-grade-scale td { border: 1px solid #777; padding: 2px 6px; text-align: center; }
                    .rc-grade-scale th { background: #f0f0f0; font-weight: 700; }

                    /* Pass/Fail strip */
                    .rc-result-strip { background: #1e3a5f; color: white; text-align: center; font-size: 13px; font-weight: 700; padding: 6px; letter-spacing: 1px; }
                    .rc-result-strip.fail { background: #b91c1c; }
                `}</style>
            </Head>

            {/* Toolbar — screen only */}
            <div className="no-print" style={{ background: '#1e293b', color: '#fff', padding: '12px 24px', display: 'flex', alignItems: 'center', justifyContent: 'space-between', position: 'sticky', top: 0, zIndex: 50 }}>
                <div>
                    <p style={{ fontWeight: 700, fontSize: 15 }}>KORT School — Report Cards</p>
                    <p style={{ fontSize: 11, color: '#94a3b8', marginTop: 2 }}>
                        {examTypes[filters.exam_type]} &nbsp;•&nbsp; {terms[filters.term]} &nbsp;•&nbsp; {filters.academic_year} &nbsp;•&nbsp; {classLabel}
                        &nbsp;&nbsp;<span style={{ background: '#334155', padding: '2px 10px', borderRadius: 12, fontSize: 10 }}>{reportData.length} student{reportData.length !== 1 ? 's' : ''}</span>
                    </p>
                </div>
                <button onClick={handlePrint} style={{ background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '8px 20px', fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', gap: 6, fontSize: 13 }}>
                    <PrinterIcon style={{ width: 18, height: 18 }} /> Print / Save PDF
                </button>
            </div>

            <div className="page-wrapper">
                {reportData.length === 0 ? (
                    <div style={{ background: '#fff', borderRadius: 12, padding: 60, textAlign: 'center', color: '#6b7280' }}>
                        <p style={{ fontSize: 18, fontWeight: 600 }}>No results found</p>
                        <p style={{ fontSize: 13, marginTop: 8 }}>Select filters and a student, then click Preview Result.</p>
                    </div>
                ) : (
                    reportData.map((entry) => {
                        const totalObtained = entry.summary.total_obtained;
                        const totalPossible = entry.summary.total_possible;
                        const pct          = entry.summary.overall_percentage;
                        const pass         = entry.summary.pass_fail === 'PASS';

                        return (
                            <div key={entry.student.id} className="report-card">

                                {/* ── HEADER ── */}
                                <div className="rc-header">
                                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                                        {/* Logo placeholder */}
                                        <div style={{ width: 64, height: 64, borderRadius: '50%', border: '2px solid #1e3a5f', display: 'flex', alignItems: 'center', justifyContent: 'center', flexShrink: 0, background: '#1e3a5f' }}>
                                            <span style={{ color: '#fff', fontWeight: 900, fontSize: 26 }}>K</span>
                                        </div>

                                        {/* School info */}
                                        <div style={{ flex: 1 }}>
                                            <div className="rc-school-name">KORT SCHOOL</div>
                                            <div className="rc-affil">Affiliated To: UK Board of Education &nbsp;|&nbsp; Affiliation No: KORT-2024-001</div>
                                            <div className="rc-contact">
                                                Ph: +44 (0) 000 000 0000 &nbsp;&nbsp; Email: info@kort.org.uk &nbsp;&nbsp; Visit us: www.kort.org.uk
                                            </div>
                                            <div className="rc-report-title" style={{ marginTop: 8 }}>
                                                <p>Academic Report</p>
                                                <small>Academic Session : {filters.academic_year} &nbsp;&nbsp;|&nbsp;&nbsp; {examTypes[filters.exam_type]} — {terms[filters.term]}</small>
                                            </div>
                                        </div>

                                        {/* Student photo */}
                                        <div style={{ flexShrink: 0, width: 72 }}>
                                            {entry.student.photo ? (
                                                <img src={`/storage/${entry.student.photo}`} alt="" style={{ width: 72, height: 88, objectFit: 'cover', border: '1px solid #555' }} />
                                            ) : (
                                                <div style={{ width: 72, height: 88, background: '#e5e7eb', border: '1px solid #555', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: 11, color: '#9ca3af' }}>
                                                    Photo
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Class strip */}
                                    <div style={{ textAlign: 'center', marginTop: 6, fontSize: 12, fontWeight: 700, color: '#1e3a5f' }}>
                                        Class : {entry.student.class ? `${entry.student.class.class}${entry.student.class.section ? ` — ${entry.student.class.section}` : ''}` : '—'}
                                        {entry.student.stream ? ` &nbsp;|&nbsp; Stream: ${entry.student.stream}` : ''}
                                    </div>
                                </div>

                                {/* ── STUDENT INFO ── */}
                                <div className="rc-student-info" style={{ borderTop: '1px solid #555' }}>
                                    <div className="rc-info-left">
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Name of Student</span>
                                            <span>: </span>
                                            <span className="rc-info-value" style={{ fontWeight: 700 }}>{entry.student.full_name}</span>
                                        </div>
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Father's Name</span>
                                            <span>: </span>
                                            <span className="rc-info-value">{entry.student.father_name || '—'}</span>
                                        </div>
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Stream / Group</span>
                                            <span>: </span>
                                            <span className="rc-info-value">{entry.student.stream || 'General'}</span>
                                        </div>
                                    </div>
                                    <div className="rc-info-right">
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Admission No.</span>
                                            <span>: </span>
                                            <span className="rc-info-value" style={{ fontWeight: 700 }}>{entry.student.admission_no}</span>
                                        </div>
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Exam Type</span>
                                            <span>: </span>
                                            <span className="rc-info-value">{examTypes[filters.exam_type]}</span>
                                        </div>
                                        <div className="rc-info-row">
                                            <span className="rc-info-label">Term</span>
                                            <span>: </span>
                                            <span className="rc-info-value">{terms[filters.term]}</span>
                                        </div>
                                    </div>
                                </div>

                                {/* ── SCHOLASTIC MARKS TABLE ── */}
                                <div style={{ padding: '0 14px 10px' }}>
                                    <div className="rc-section-title" style={{ marginTop: 10 }}>Scholastic Areas</div>
                                    <table className="rc-table">
                                        <thead>
                                            <tr>
                                                <th rowSpan={2} className="subject-col" style={{ textAlign: 'left', width: '30%' }}>Subject</th>
                                                <th colSpan={2}>{terms[filters.term]}</th>
                                                <th colSpan={2}>Overall</th>
                                            </tr>
                                            <tr>
                                                <th>Obtained</th>
                                                <th>Total</th>
                                                <th>Grand Total</th>
                                                <th>Grade</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {entry.results.map((result, idx) => (
                                                <tr key={idx}>
                                                    <td className="subject-col">{result.subject_name}</td>
                                                    <td>{result.obtained_marks}</td>
                                                    <td>{result.total_marks}</td>
                                                    <td>{result.obtained_marks}</td>
                                                    <td style={{ fontWeight: 700 }}>{result.grade}</td>
                                                </tr>
                                            ))}
                                            <tr className="total-row">
                                                <td style={{ textAlign: 'left' }}>Attendance: —</td>
                                                <td colSpan={1} style={{ fontWeight: 700 }}>Total Marks: {totalObtained} / {totalPossible}</td>
                                                <td></td>
                                                <td style={{ fontWeight: 700 }}>Percentage: {pct.toFixed(1)}%</td>
                                                <td style={{ fontWeight: 700 }}>{entry.summary.overall_grade}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {/* ── CO-SCHOLASTIC ── */}
                                <div className="rc-coscholastic" style={{ padding: '0 14px 10px' }}>
                                    <div className="rc-section-title">CO-SCHOLASTIC &nbsp;(3 POINT GRADING SCALE: A, B, C)</div>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style={{ textAlign: 'left', width: '50%' }}>Activity</th>
                                                <th>Term-I</th>
                                                <th>Term-II</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {['Uniform & Discipline', 'Activities & Sports', 'Digital Literacy', 'Written Skills', 'Speaking Skills'].map(activity => (
                                                <tr key={activity}>
                                                    <td>{activity}</td>
                                                    <td style={{ textAlign: 'center' }}>—</td>
                                                    <td style={{ textAlign: 'center' }}>—</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* ── RESULT STRIP ── */}
                                <div className={`rc-result-strip${pass ? '' : ' fail'}`}>
                                    RESULT : {pass ? 'PASS' : 'FAIL'} &nbsp;&nbsp;|&nbsp;&nbsp; Overall Grade : {entry.summary.overall_grade} &nbsp;&nbsp;|&nbsp;&nbsp; GPA : {Number(entry.summary.average_gpa).toFixed(2)}
                                </div>

                                {/* ── FOOTER ── */}
                                <div className="rc-footer">
                                    {/* Signatures */}
                                    <div className="rc-signatures">
                                        {['Sign. of Class Teacher', 'Sign. of Principal', 'Sign. of Manager'].map(label => (
                                            <div key={label} className="rc-sig-box">
                                                <div className="rc-sig-line"></div>
                                                <p style={{ fontWeight: 600, fontSize: 10 }}>{label}</p>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Instructions */}
                                    <div style={{ borderTop: '1px solid #ccc', paddingTop: 6 }}>
                                        <p style={{ fontWeight: 700, marginBottom: 4, fontSize: 10 }}>Instructions &amp; Grading Scale for Scholastic Areas:</p>
                                        <table className="rc-grade-scale">
                                            <thead>
                                                <tr>
                                                    <th>Marks Range (%)</th>
                                                    {gradeScale.map(g => <th key={g.grade}>{g.range}</th>)}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td style={{ fontWeight: 700, textAlign: 'left', paddingLeft: 8 }}>Grade</td>
                                                    {gradeScale.map(g => <td key={g.grade} style={{ fontWeight: 700 }}>{g.grade}</td>)}
                                                </tr>
                                            </tbody>
                                        </table>
                                        <p style={{ marginTop: 5, fontSize: 9, color: '#555' }}>
                                            Grades are awarded on an 8-point grading scale as shown above. Students scoring below 33% are considered FAIL.
                                        </p>
                                    </div>
                                </div>

                            </div>
                        );
                    })
                )}
            </div>
        </>
    );
}
