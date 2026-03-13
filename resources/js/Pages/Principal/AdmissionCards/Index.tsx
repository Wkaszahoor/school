import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import Badge from '@/Components/Badge';
import { IdentificationIcon, CheckIcon, XMarkIcon, FunnelIcon } from '@heroicons/react/24/outline';
import { Student, SchoolClass, StudentDatesheet } from '@/types';

interface StudentWithAttendance extends Student {
    attendance_percent: number;
}

interface AdmissionCardData {
    [key: number]: string;
}

interface PageProps {
    classes?: SchoolClass[];
    examPeriods?: string[];
    students?: StudentWithAttendance[];
    datesheets?: StudentDatesheet[];
    existingCards?: AdmissionCardData;
    academicYear?: string;
    selectedClassId?: number | null;
    selectedExamPeriod?: string;
}

export default function Index({
    classes = [],
    examPeriods = [],
    students = [],
    datesheets = [],
    existingCards = {},
    academicYear = '2025-26',
    selectedClassId = null,
    selectedExamPeriod = '',
}: PageProps) {
    const [classId, setClassId] = useState<string>(selectedClassId?.toString() || '');
    const [examPeriod, setExamPeriod] = useState(selectedExamPeriod);
    const [selectedStudents, setSelectedStudents] = useState<Set<number>>(new Set());
    const { post, processing } = useForm();

    const minAttendance = 75; // Default from config

    const handleFilter = () => {
        const params: Record<string, any> = { academic_year: academicYear };
        if (classId) params.class_id = classId;
        if (examPeriod) params.exam_period = examPeriod;
        router.get(route('principal.admission-cards.index'), params);
    };

    const handleSelectStudent = (studentId: number) => {
        const newSelection = new Set(selectedStudents);
        if (newSelection.has(studentId)) {
            newSelection.delete(studentId);
        } else {
            newSelection.add(studentId);
        }
        setSelectedStudents(newSelection);
    };

    const handleSelectAll = () => {
        if (selectedStudents.size === students.length) {
            setSelectedStudents(new Set());
        } else {
            setSelectedStudents(new Set(students.map((s) => s.id)));
        }
    };

    const handleGenerate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!classId || !examPeriod) {
            alert('Please select both class and exam period');
            return;
        }
        if (selectedStudents.size === 0) {
            alert('Please select at least one student');
            return;
        }

        post(route('principal.admission-cards.generate'), {
            class_id: parseInt(classId),
            exam_period: examPeriod,
            academic_year: academicYear,
            student_ids: Array.from(selectedStudents),
        });
    };

    const handleDownload = (admission_card_id?: number) => {
        if (selectedStudents.size === 0 && !admission_card_id) {
            alert('Please select at least one card');
            return;
        }

        if (admission_card_id) {
            // Single download would be handled by direct link
            window.location.href = route('principal.admission-cards.download', admission_card_id);
        } else {
            // Bulk download - would need implementation
            alert('Bulk download coming soon');
        }
    };

    const eligibleCount = useMemo(() => students.filter((s) => s.attendance_percent >= minAttendance).length, [students]);
    const notEligibleCount = useMemo(() => students.length - eligibleCount, [students]);

    return (
        <AppLayout title="Admission Cards">
            <Head title="Admission Cards" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Admission Cards</h1>
                    <p className="page-subtitle">Generate and manage student admission cards</p>
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="form-label">Select Class *</label>
                            <select value={classId} onChange={(e) => setClassId(e.target.value)} className="form-select">
                                <option value="">Choose a class</option>
                                {classes.map((cls) => (
                                    <option key={cls.id} value={cls.id}>
                                        {cls.class} {cls.section ? `(${cls.section})` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="form-label">Select Exam Period *</label>
                            <select value={examPeriod} onChange={(e) => setExamPeriod(e.target.value)} className="form-select">
                                <option value="">Choose exam period</option>
                                {examPeriods.map((period) => (
                                    <option key={period} value={period}>
                                        {period}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex items-end">
                            <button onClick={handleFilter} className="btn-secondary w-full">
                                Show Students
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Stats & Date Sheet */}
            {classId && examPeriod && students.length > 0 ? (
                <>
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div className="card">
                            <div className="card-body">
                                <div className="text-center">
                                    <p className="text-gray-600 text-sm">Total Students</p>
                                    <p className="text-4xl font-bold text-blue-600">{students.length}</p>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body">
                                <div className="text-center">
                                    <p className="text-gray-600 text-sm">Eligible for Exam</p>
                                    <p className="text-4xl font-bold text-green-600">{eligibleCount}</p>
                                </div>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body">
                                <div className="text-center">
                                    <p className="text-gray-600 text-sm">Not Eligible</p>
                                    <p className="text-4xl font-bold text-red-600">{notEligibleCount}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Date Sheet */}
                    {datasheets.length > 0 && (
                        <div className="card mb-6">
                            <div className="card-body">
                                <h3 className="card-title mb-4">Exam Date Sheet</h3>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead className="bg-gray-100">
                                            <tr>
                                                <th className="px-4 py-2 text-left">Subject</th>
                                                <th className="px-4 py-2 text-left">Date</th>
                                                <th className="px-4 py-2 text-left">Time</th>
                                                <th className="px-4 py-2 text-center">Marks</th>
                                                <th className="px-4 py-2 text-left">Room</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {datasheets.map((sheet, idx) => (
                                                <tr key={idx} className="border-b hover:bg-gray-50">
                                                    <td className="px-4 py-2">{sheet.subject_name}</td>
                                                    <td className="px-4 py-2">{new Date(sheet.exam_date).toLocaleDateString('en-GB')}</td>
                                                    <td className="px-4 py-2">{sheet.exam_time || '—'}</td>
                                                    <td className="px-4 py-2 text-center font-semibold">{sheet.total_marks}</td>
                                                    <td className="px-4 py-2">{sheet.room_no || '—'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Students Table */}
                    <div className="card">
                        <div className="card-body pb-0">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="card-title">Students & Eligibility</h3>
                                <button
                                    onClick={handleGenerate}
                                    disabled={processing || selectedStudents.size === 0}
                                    className="btn-primary"
                                >
                                    {processing ? 'Generating...' : `Generate Cards (${selectedStudents.size})`}
                                </button>
                            </div>
                        </div>

                        <div className="table-wrapper">
                            <table className="table">
                                <thead>
                                    <tr>
                                        <th style={{ width: '40px' }}>
                                            <input
                                                type="checkbox"
                                                checked={selectedStudents.size === students.length && students.length > 0}
                                                onChange={handleSelectAll}
                                                className="form-checkbox"
                                            />
                                        </th>
                                        <th>Name</th>
                                        <th>Admission No</th>
                                        <th>Attendance %</th>
                                        <th>Eligibility</th>
                                        <th>Card Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map((student) => {
                                        const isEligible = student.attendance_percent >= minAttendance;
                                        const cardStatus = existingCards[student.id] || 'Not Generated';

                                        return (
                                            <tr key={student.id}>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        checked={selectedStudents.has(student.id)}
                                                        onChange={() => handleSelectStudent(student.id)}
                                                        className="form-checkbox"
                                                    />
                                                </td>
                                                <td className="font-medium">{student.full_name}</td>
                                                <td>{student.admission_no}</td>
                                                <td>
                                                    <span className="font-semibold text-blue-600">{student.attendance_percent}%</span>
                                                </td>
                                                <td>
                                                    <Badge color={isEligible ? 'green' : 'red'}>
                                                        {isEligible ? (
                                                            <div className="flex items-center gap-1">
                                                                <CheckIcon className="w-4 h-4" /> Eligible
                                                            </div>
                                                        ) : (
                                                            <div className="flex items-center gap-1">
                                                                <XMarkIcon className="w-4 h-4" /> Not Eligible
                                                            </div>
                                                        )}
                                                    </Badge>
                                                </td>
                                                <td>
                                                    <Badge color={cardStatus === 'issued' ? 'green' : cardStatus === 'draft' ? 'yellow' : 'gray'}>
                                                        {cardStatus}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </>
            ) : !classId || !examPeriod ? (
                <div className="card">
                    <div className="text-center py-12">
                        <IdentificationIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                        <p className="text-gray-500">Select a class and exam period to get started</p>
                    </div>
                </div>
            ) : (
                <div className="card">
                    <div className="text-center py-12">
                        <p className="text-gray-500">No students found for the selected class</p>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
