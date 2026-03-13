import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { PlusIcon, FunnelIcon, ArrowDownTrayIcon, EnvelopeIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Pagination from '@/Components/Pagination';
import type { PageProps, Result, TeacherAssignment, PaginatedData } from '@/types';

declare const route: (name: string, params?: any) => string;

interface Props extends PageProps {
    results: PaginatedData<Result>;
    myAssignments: TeacherAssignment[];
    examTypes: string[];
    filters: { class_id?: string; exam_type?: string };
    myClassTeacherAssignment?: { id: string; class: string; section?: string };
    viewingOwnClass?: boolean;
    classTeacherStudents?: any[];
    classTeacherSubjects?: { id: string; subject_name: string }[];
    classTeacherResults?: Record<number, Record<string, any>>;
    classTeacherStudentsByGroup?: Record<string, number>;
    allClassSubjects?: { id: string; subject_name: string }[];
    subjectGroupsWithSubjects?: Record<string, { name: string; stream: string; subjects: string[] }>;
    studentToGroupMap?: Record<number, string>;
}

export default function TeacherResultsIndex({ results, myAssignments, examTypes, filters, myClassTeacherAssignment, viewingOwnClass, classTeacherStudents, classTeacherSubjects, classTeacherResults, classTeacherStudentsByGroup, allClassSubjects, subjectGroupsWithSubjects, studentToGroupMap }: Props) {
    const calculateGrade = (percentage: number): { grade: string; color: string } => {
        if (percentage >= 90) return { grade: 'A', color: 'text-emerald-700' };
        if (percentage >= 80) return { grade: 'B', color: 'text-emerald-600' };
        if (percentage >= 70) return { grade: 'C', color: 'text-blue-600' };
        if (percentage >= 60) return { grade: 'D', color: 'text-yellow-600' };
        return { grade: 'F', color: 'text-red-600' };
    };
    const uniqueClasses = React.useMemo(() => {
        if (!Array.isArray(myAssignments) || myAssignments.length === 0) {
            return [];
        }

        const classMap = new Map();
        for (const assignment of myAssignments) {
            if (assignment?.class_id && assignment?.class) {
                classMap.set(assignment.class_id, assignment.class);
            }
        }

        return Array.from(classMap.values());
    }, [myAssignments]);

    const [filterGroup, setFilterGroup] = React.useState(filters?.group || '');

    // Export to CSV
    const exportToCSV = () => {
        if (!filteredStudents || filteredStudents.length === 0 || !filteredSubjects) {
            alert('No data to export');
            return;
        }

        let csv = 'Student Name,Admission No,Subject Group,' + filteredSubjects.map(s => s.subject_name).join(',') + '\n';

        filteredStudents.forEach(student => {
            const row = [
                `"${student.full_name}"`,
                student.admission_no,
                student.subjectGroup?.group_name || student.stream || '-'
            ];

            filteredSubjects.forEach(subject => {
                const result = classTeacherResults?.[student.id]?.[subject.id];
                const obtained = result?.obtained_marks || 0;
                const total = result?.total_marks || 100;
                const percentage = total > 0 ? Math.round((obtained / total) * 100) : 0;
                row.push(`"${obtained}/${total} (${percentage}%)"`);
            });

            csv += row.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `results-${myClassTeacherAssignment?.class || 'all'}-${filterGroup || 'all'}-${new Date().toISOString().split('T')[0]}.csv`);
        link.click();
    };

    // Export to PDF
    const exportToPDF = async () => {
        if (!filteredStudents || filteredStudents.length === 0 || !filteredSubjects) {
            alert('No data to export');
            return;
        }

        try {
            const { jsPDF } = await import('jspdf');
            const { autoTable } = await import('jspdf-autotable');

            const doc = new jsPDF();
            const pageWidth = doc.internal.pageSize.getWidth();

            // Title
            doc.setFontSize(16);
            doc.text('Results Report', pageWidth / 2, 10, { align: 'center' });

            // Subtitle
            doc.setFontSize(10);
            doc.text(`Class: ${myClassTeacherAssignment?.class || 'All'} | Group: ${filterGroup || 'All'} | Date: ${new Date().toLocaleDateString()}`, pageWidth / 2, 16, { align: 'center' });

            // Table
            const tableColumns = ['Student Name', 'Admission No', 'Subject Group', ...filteredSubjects.map(s => s.subject_name)];
            const tableRows = filteredStudents.map(student => {
                const row = [
                    student.full_name,
                    student.admission_no,
                    student.subjectGroup?.group_name || student.stream || '-'
                ];

                filteredSubjects.forEach(subject => {
                    const result = classTeacherResults?.[student.id]?.[subject.id];
                    const obtained = result?.obtained_marks || 0;
                    const total = result?.total_marks || 100;
                    const percentage = total > 0 ? Math.round((obtained / total) * 100) : 0;
                    row.push(`${obtained}/${total}\n(${percentage}%)`);
                });

                return row;
            });

            autoTable(doc, {
                head: [tableColumns],
                body: tableRows,
                startY: 22,
                theme: 'grid',
                columnStyles: {
                    0: { cellWidth: 30 },
                    1: { cellWidth: 20 },
                    2: { cellWidth: 25 }
                },
                fontSize: 8,
                margin: { top: 22, right: 10, bottom: 10, left: 10 }
            });

            doc.save(`results-${myClassTeacherAssignment?.class || 'all'}-${filterGroup || 'all'}-${new Date().toISOString().split('T')[0]}.pdf`);
        } catch (error) {
            console.error('PDF export error:', error);
            alert('Error exporting PDF. Please ensure jsPDF and jspdf-autotable are installed.');
        }
    };

    // Share via Email
    const shareViaEmail = () => {
        if (!filteredStudents || filteredStudents.length === 0) {
            alert('No data to share');
            return;
        }

        const subject = `Results - Class ${myClassTeacherAssignment?.class || 'All'} ${filterGroup ? `(${filterGroup})` : ''}`;
        const body = `Dear Principal,\n\nPlease find the results report attached.\n\nClass: ${myClassTeacherAssignment?.class || 'All'}\nGroup: ${filterGroup || 'All'}\nStudents: ${filteredStudents.length}\nSubjects: ${filteredSubjects?.length || 0}\n\nBest regards,\nTeacher`;

        const mailtoLink = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.location.href = mailtoLink;
    };

    // Share via WhatsApp
    const shareViaWhatsApp = () => {
        if (!filteredStudents || filteredStudents.length === 0) {
            alert('No data to share');
            return;
        }

        const message = `📊 *Results Report*\n\nClass: ${myClassTeacherAssignment?.class || 'All'}\nGroup: ${filterGroup || 'All'}\nStudents: ${filteredStudents.length}\nSubjects: ${filteredSubjects?.length || 0}\nDate: ${new Date().toLocaleDateString()}\n\nFor detailed results, please export as CSV or PDF from the system.`;

        const whatsappLink = `https://wa.me/?text=${encodeURIComponent(message)}`;
        window.open(whatsappLink, '_blank');
    };

    const handleFilter = (key: string, value: string) => {
        router.get(route('teacher.results.index'), { ...filters, [key]: value }, {
            preserveState: true, replace: true,
        });
    };

    // Filter students by subject group using backend-generated map
    const filteredStudents = React.useMemo(() => {
        if (!filterGroup || !classTeacherStudents) return classTeacherStudents;

        return classTeacherStudents.filter(s => {
            // Use studentToGroupMap for reliable filtering
            if (studentToGroupMap && studentToGroupMap[s.id] !== undefined) {
                return studentToGroupMap[s.id] === filterGroup;
            }
            // Fallback: try matching by group name
            if (s.subjectGroup?.group_name === filterGroup) return true;
            if (s.stream === filterGroup) return true;
            const groupName = (s.subjectGroup?.group_name || s.stream || 'No Group').trim();
            return groupName === filterGroup.trim();
        });
    }, [classTeacherStudents, filterGroup, studentToGroupMap]);

    // Filter subjects by selected group
    const filteredSubjects = React.useMemo(() => {
        if (!filterGroup || !subjectGroupsWithSubjects || !allClassSubjects) {
            return allClassSubjects || [];
        }

        const groupData = subjectGroupsWithSubjects[filterGroup];
        if (!groupData || !groupData.subjects || groupData.subjects.length === 0) {
            return allClassSubjects || [];
        }

        // Return only subjects that belong to this group
        return (allClassSubjects || []).filter(s => groupData.subjects.includes(s.id));
    }, [filterGroup, subjectGroupsWithSubjects, allClassSubjects]);

    return (
        <AppLayout title="Results">
            <Head title="Results" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Results</h1>
                    <p className="page-subtitle">Grades you have entered</p>
                </div>
                <Link href={route('teacher.results.create')} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> Enter Results
                </Link>
            </div>

            {/* Class Teacher Section */}
            {myClassTeacherAssignment && (
                <div className="card bg-blue-50 border border-blue-200">
                    <div className="card-body">
                        <div className="flex items-center justify-between mb-4">
                            <div>
                                <h2 className="text-lg font-bold text-blue-900">✓ Class Teacher</h2>
                                <p className="text-sm text-blue-700 mt-2">
                                    You are the class teacher of <span className="font-semibold">{myClassTeacherAssignment.class}{myClassTeacherAssignment.section ? ` — ${myClassTeacherAssignment.section}` : ''}</span>
                                </p>
                            </div>
                            <div className="flex gap-3">
                                <Link
                                    href={route('teacher.results.index', { class_id: myClassTeacherAssignment.id })}
                                    className="btn-secondary text-sm"
                                >
                                    View Class Results
                                </Link>
                                <Link
                                    href={route('teacher.results.create', { class_id: myClassTeacherAssignment.id })}
                                    className="btn-primary text-sm"
                                >
                                    <PlusIcon className="w-4 h-4" /> Enter Subject Marks
                                </Link>
                            </div>
                        </div>

                        {viewingOwnClass && classTeacherStudents && classTeacherStudents.length > 0 && (
                            <div className="pt-4 border-t border-blue-200">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="bg-white rounded p-3">
                                        <p className="text-xs text-gray-500 font-semibold">TOTAL STUDENTS</p>
                                        <p className="text-2xl font-bold text-blue-900">{classTeacherStudents.length}</p>
                                    </div>
                                    <div className="bg-white rounded p-3">
                                        <p className="text-xs text-gray-500 font-semibold">CLASS SUBJECTS</p>
                                        <p className="text-2xl font-bold text-blue-900">{filterGroup ? filteredSubjects.length : allClassSubjects?.length || 0}</p>
                                    </div>
                                </div>

                                {classTeacherStudentsByGroup && Object.keys(classTeacherStudentsByGroup).length > 0 && (
                                    <div className="mt-3 pt-3 border-t border-blue-200">
                                        <p className="text-xs text-gray-500 font-semibold mb-2">SUBJECT GROUPS BREAKDOWN (Click to filter)</p>
                                        <div className="flex flex-wrap gap-2">
                                            <button
                                                onClick={() => setFilterGroup('')}
                                                className={`px-3 py-1 rounded text-xs font-semibold transition cursor-pointer ${
                                                    !filterGroup
                                                        ? 'bg-blue-500 text-white ring-2 ring-blue-300'
                                                        : 'bg-blue-100 text-blue-900 hover:bg-blue-200'
                                                }`}
                                            >
                                                All ({classTeacherStudents?.length || 0})
                                            </button>
                                            {Object.entries(classTeacherStudentsByGroup)
                                                .sort((a, b) => a[0].localeCompare(b[0]))
                                                .map(([groupName, count]) => (
                                                    <button
                                                        key={groupName}
                                                        onClick={() => setFilterGroup(groupName)}
                                                        className={`px-3 py-1 rounded text-xs font-semibold transition cursor-pointer ${
                                                            filterGroup === groupName
                                                                ? 'ring-2 ring-offset-1 brightness-110'
                                                                : 'hover:opacity-80'
                                                        } ${
                                                            groupName.includes('ICS') ? 'bg-purple-100 text-purple-900' :
                                                            groupName.includes('Medical') || groupName.includes('Premedical') ? 'bg-red-100 text-red-900' :
                                                            'bg-gray-100 text-gray-900'
                                                        }`}
                                                    >
                                                        {groupName}: <strong>{count}</strong>
                                                    </button>
                                                ))}
                                        </div>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* Class Teacher Comprehensive Results Matrix */}
            {viewingOwnClass && classTeacherStudents && classTeacherStudents.length > 0 && filteredStudents && filteredStudents.length > 0 && filteredSubjects && filteredSubjects.length > 0 && (
                <div className="card">
                    <div className="card-header">
                        <div className="flex items-center justify-between">
                            <p className="card-title">
                                All Students Results - {filteredStudents.length} Students {filterGroup && `(${filterGroup})`} × {filteredSubjects.length} Subjects
                            </p>
                            <div className="flex gap-2">
                                <button
                                    onClick={exportToCSV}
                                    className="px-3 py-2 bg-green-500 text-white rounded text-sm font-semibold hover:bg-green-600 transition flex items-center gap-2"
                                    title="Export as CSV"
                                >
                                    <ArrowDownTrayIcon className="w-4 h-4" /> CSV
                                </button>
                                <button
                                    onClick={exportToPDF}
                                    className="px-3 py-2 bg-red-500 text-white rounded text-sm font-semibold hover:bg-red-600 transition flex items-center gap-2"
                                    title="Export as PDF"
                                >
                                    <ArrowDownTrayIcon className="w-4 h-4" /> PDF
                                </button>
                                <button
                                    onClick={shareViaEmail}
                                    className="px-3 py-2 bg-blue-500 text-white rounded text-sm font-semibold hover:bg-blue-600 transition flex items-center gap-2"
                                    title="Share via Email"
                                >
                                    <EnvelopeIcon className="w-4 h-4" /> Email
                                </button>
                                <button
                                    onClick={shareViaWhatsApp}
                                    className="px-3 py-2 bg-green-600 text-white rounded text-sm font-semibold hover:bg-green-700 transition flex items-center gap-2"
                                    title="Share via WhatsApp"
                                >
                                    💬 WhatsApp
                                </button>
                            </div>
                        </div>
                    </div>
                    <div className="table-wrapper overflow-x-auto">
                        <table className="table table-sm">
                            <thead>
                                <tr>
                                    <th className="sticky left-0 z-10 bg-gray-50">Student</th>
                                    <th className="sticky left-24 z-10 bg-gray-50">Admission</th>
                                    <th className="sticky left-48 z-10 bg-gray-50">Subject Group</th>
                                    {filteredSubjects.map(s => (
                                        <th key={s.id} className="text-center min-w-32 bg-gray-50">
                                            <div className="text-xs font-semibold text-gray-900">{s.subject_name}</div>
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {filteredStudents.map(student => (
                                    <tr key={student.id}>
                                        <td className="font-semibold text-gray-900 sticky left-0 z-10 bg-white">{student.full_name}</td>
                                        <td className="font-mono text-xs text-gray-500 sticky left-24 z-10 bg-white">{student.admission_no}</td>
                                        <td className="sticky left-48 z-10 bg-white">
                                            {student.subjectGroup?.group_name ? (
                                                <span className={`inline-block px-2 py-1 rounded text-xs font-semibold ${
                                                    student.subjectGroup.group_name.includes('ICS') ? 'bg-purple-100 text-purple-900' :
                                                    student.subjectGroup.group_name.includes('Medical') || student.subjectGroup.group_name.includes('Premedical') ? 'bg-red-100 text-red-900' :
                                                    'bg-gray-100 text-gray-900'
                                                }`}>
                                                    {student.subjectGroup.group_name}
                                                </span>
                                            ) : student.stream ? (
                                                <span className={`inline-block px-2 py-1 rounded text-xs font-semibold ${
                                                    student.stream.includes('ICS') ? 'bg-purple-100 text-purple-900' :
                                                    student.stream.includes('Medical') || student.stream.includes('Premedical') ? 'bg-red-100 text-red-900' :
                                                    'bg-gray-100 text-gray-900'
                                                }`}>
                                                    {student.stream}
                                                </span>
                                            ) : (
                                                <span className="text-xs text-gray-400">—</span>
                                            )}
                                        </td>
                                        {filteredSubjects.map(subject => {
                                            const result = classTeacherResults?.[student.id]?.[subject.id];
                                            const obtained = result?.obtained_marks || 0;
                                            const total = result?.total_marks || 100;
                                            const percentage = total > 0 ? Math.round((obtained / total) * 100) : null;
                                            const gradeInfo = percentage !== null && percentage > 0 ? calculateGrade(percentage) : null;

                                            return (
                                                <td key={`${student.id}-${subject.id}`} className="text-center p-2">
                                                    {result ? (
                                                        <div className="flex flex-col gap-1 bg-gray-50 p-2 rounded">
                                                            <div className="text-xs text-gray-600">
                                                                {obtained}/{total}
                                                            </div>
                                                            {percentage !== null && (
                                                                <>
                                                                    <div className={`text-xs font-bold ${percentage >= 50 ? 'text-emerald-600' : 'text-red-600'}`}>
                                                                        {percentage}%
                                                                    </div>
                                                                    {gradeInfo && (
                                                                        <div className={`text-sm font-bold ${gradeInfo.color}`}>
                                                                            {gradeInfo.grade}
                                                                        </div>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    ) : (
                                                        <div className="text-xs text-gray-400">—</div>
                                                    )}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}

            {/* No Results Message */}
            {viewingOwnClass && classTeacherStudents && classTeacherStudents.length > 0 && (!filteredStudents || filteredStudents.length === 0) && (
                <div className="card bg-amber-50 border border-amber-200 mb-5">
                    <div className="card-body">
                        <p className="text-sm text-amber-900 font-semibold">
                            ⚠️ No students found for the selected subject group.
                        </p>
                        <p className="text-xs text-amber-700 mt-1">
                            Try selecting a different group or click "All" to see all students.
                        </p>
                    </div>
                </div>
            )}

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label className="form-label">Class</label>
                            <select className="form-select" value={filters.class_id ?? ''}
                                    onChange={e => handleFilter('class_id', e.target.value)}>
                                <option value="">All Classes</option>
                                {uniqueClasses.map(c => (
                                    <option key={c.id} value={c.id}>{c.class}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Subject Group</label>
                            <select
                                className="form-select"
                                value={filterGroup}
                                onChange={e => setFilterGroup(e.target.value)}
                            >
                                <option value="">All Groups ({classTeacherStudents?.length || 0})</option>
                                {classTeacherStudentsByGroup && Object.entries(classTeacherStudentsByGroup)
                                    .sort((a, b) => a[0].localeCompare(b[0]))
                                    .map(([groupName, count]) => (
                                        <option key={groupName} value={groupName}>{groupName} ({count})</option>
                                    ))}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Exam Type</label>
                            <select className="form-select" value={filters.exam_type ?? ''}
                                    onChange={e => handleFilter('exam_type', e.target.value)}>
                                <option value="">All Exam Types</option>
                                {Array.isArray(examTypes) && examTypes.map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {/* Results Table */}
            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Marks</th>
                                <th>%</th>
                                <th>Grade</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {!Array.isArray(results?.data) || results.data.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="text-center py-12 text-gray-400">
                                        No results yet. Click "Enter Results" to add grades.
                                    </td>
                                </tr>
                            ) : results.data.map(r => (
                                <tr key={r.id}>
                                    <td className="font-semibold text-gray-900">{r.student?.full_name}</td>
                                    <td>{r.class?.name || r.class?.class}</td>
                                    <td>{r.subject?.name || r.subject?.subject_name}</td>
                                    <td>{r.exam_type}</td>
                                    <td>{r.obtained_marks}/{r.total_marks}</td>
                                    <td>{r.percentage}%</td>
                                    <td>
                                        <Badge color={
                                            r.grade === 'A*' || r.grade === 'A' ? 'green' :
                                            r.grade === 'F' ? 'red' : 'blue'
                                        }>{r.grade}</Badge>
                                    </td>
                                    <td>
                                        <Badge color={r.approval_status === 'approved' ? 'green' : 'yellow'}>
                                            {r.approval_status === 'approved' ? 'Approved' : r.approval_status === 'rejected' ? 'Rejected' : 'Pending'}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={results} />
                </div>
            </div>
        </AppLayout>
    );
}
