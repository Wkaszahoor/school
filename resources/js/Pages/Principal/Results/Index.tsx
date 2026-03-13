import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { CheckIcon, XMarkIcon, AcademicCapIcon, FunnelIcon, DocumentTextIcon, EyeIcon, ArrowDownTrayIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, Result, SchoolClass, Subject } from '@/types';

interface Props extends PageProps {
    results: PaginatedData<Result>;
    classes: SchoolClass[];
    subjects: Subject[];
    examTypes: Record<string, string>;
    terms: Record<string, string>;
    academicYears: string[];
    streams: string[];
}

type ApprovalStatus = 'pending' | 'class_teacher_approved' | 'approved' | 'rejected';

const getStatusBadge = (status: ApprovalStatus, classTeacherId?: number | null) => {
    const isPending = status === 'pending';
    const isCtApproved = status === 'class_teacher_approved';
    const isApproved = status === 'approved';
    const isRejected = status === 'rejected';

    let color = 'gray';
    let label = 'Unknown';

    if (isPending) {
        color = 'yellow';
        label = classTeacherId ? 'Pending (CT)' : 'Pending';
    } else if (isCtApproved) {
        color = 'blue';
        label = 'CT Approved';
    } else if (isApproved) {
        color = 'green';
        label = 'Approved';
    } else if (isRejected) {
        color = 'red';
        label = 'Rejected';
    }

    return <Badge color={color}>{label}</Badge>;
};

const canPrincipalApprove = (result: Result): boolean => {
    const status = result.approval_status as ApprovalStatus;
    const classTeacherId = result.class?.class_teacher_id;

    // Can approve if: class_teacher_approved OR (pending AND no class teacher)
    return status === 'class_teacher_approved' || (status === 'pending' && !classTeacherId);
};

export default function ResultsIndex({ results, classes, subjects, examTypes, terms, academicYears, streams }: Props) {
    const [selected, setSelected] = useState<number[]>([]);
    const [reportModalOpen, setReportModalOpen] = useState(false);
    const [approveModalOpen, setApproveModalOpen] = useState(false);
    const [rejectModalOpen, setRejectModalOpen] = useState(false);
    const [currentResult, setCurrentResult] = useState<Result | null>(null);
    const [approveRemarks, setApproveRemarks] = useState('');
    const [rejectReason, setRejectReason] = useState('');
    const [rejectRemarks, setRejectRemarks] = useState('');

    const [rcClassId, setRcClassId] = useState('');
    const [rcExamType, setRcExamType] = useState('');
    const [rcAcademicYear, setRcAcademicYear] = useState('');
    const [rcTerm, setRcTerm] = useState('');

    const toggleSelect = (id: number) => {
        setSelected(prev => prev.includes(id) ? prev.filter(s => s !== id) : [...prev, id]);
    };

    const handleBulkApprove = () => {
        if (selected.length === 0) return;
        router.post(route('principal.results.bulk-approve'), { result_ids: selected });
        setSelected([]);
    };

    const handleApproveClick = (result: Result) => {
        setCurrentResult(result);
        setApproveRemarks('');
        setApproveModalOpen(true);
    };

    const handleApproveSubmit = () => {
        if (!currentResult) return;
        router.post(route('principal.results.approve', currentResult.id), {
            remarks: approveRemarks,
        });
        setApproveModalOpen(false);
        setCurrentResult(null);
        setApproveRemarks('');
    };

    const handleRejectClick = (result: Result) => {
        setCurrentResult(result);
        setRejectReason('');
        setRejectRemarks('');
        setRejectModalOpen(true);
    };

    const handleRejectSubmit = () => {
        if (!currentResult || !rejectReason) return;
        router.post(route('principal.results.reject', currentResult.id), {
            rejection_reason: rejectReason,
            remarks: rejectRemarks,
        });
        setRejectModalOpen(false);
        setCurrentResult(null);
        setRejectReason('');
        setRejectRemarks('');
    };

    const handlePreviewReportCards = () => {
        if (!rcExamType || !rcAcademicYear || !rcTerm) return;
        const params = new URLSearchParams({
            exam_type: rcExamType,
            academic_year: rcAcademicYear,
            term: rcTerm,
            ...(rcClassId ? { class_id: rcClassId } : {}),
        });
        window.open(`/principal/results/report-cards?${params.toString()}`, '_blank');
    };

    const getFilterParams = () => {
        const params = new URLSearchParams(window.location.search);
        return params.toString();
    };

    return (
        <AppLayout title="Results">
            <Head title="Results" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Results</h1>
                    <p className="page-subtitle">{results.total} results total</p>
                </div>
                <div className="flex gap-2 flex-wrap">
                    <button onClick={() => setReportModalOpen(true)} className="btn-primary">
                        <DocumentTextIcon className="w-4 h-4" />
                        Report Cards
                    </button>
                    <a href={`${route('principal.results.export')}?${getFilterParams()}`} download className="btn-secondary">
                        <ArrowDownTrayIcon className="w-4 h-4" />
                        Export CSV
                    </a>
                    {selected.length > 0 && (
                        <button onClick={handleBulkApprove} className="btn-success">
                            <CheckIcon className="w-4 h-4" />
                            Approve Selected ({selected.length})
                        </button>
                    )}
                </div>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label className="form-label">Class</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { class_id: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Classes</option>
                                {classes.map(c => <option key={c.id} value={c.id}>{c.class}{c.section ? ` — ${c.section}` : ''}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Exam Type</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { exam_type: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Exams</option>
                                {Object.entries(examTypes).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Status</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { approval_status: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="class_teacher_approved">CT Approved</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Subject</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { subject_id: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Subjects</option>
                                {subjects.map(s => <option key={s.id} value={s.id}>{s.subject_name}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Academic Year</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { academic_year: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Years</option>
                                {academicYears.map(y => <option key={y} value={y}>{y}</option>)}
                            </select>
                        </div>
                        <div>
                            <label className="form-label">Stream</label>
                            <select className="form-select"
                                    onChange={e => router.get(route('principal.results.index'), { stream: e.target.value }, { preserveState: true, replace: true })}>
                                <option value="">All Streams</option>
                                {streams.map(s => <option key={s} value={s}>{s}</option>)}
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" className="rounded" onChange={e => {
                                    setSelected(e.target.checked ? results.data.filter(r => canPrincipalApprove(r)).map(r => r.id) : []);
                                }} /></th>
                                <th>Student</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Exam</th>
                                <th>Marks</th>
                                <th>%</th>
                                <th>Grade</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {results.data.length === 0 ? (
                                <tr>
                                    <td colSpan={10}>
                                        <div className="empty-state py-12">
                                            <AcademicCapIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No results found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : results.data.map(result => (
                                <tr key={result.id}>
                                    <td>
                                        {canPrincipalApprove(result) && (
                                            <input type="checkbox"
                                                   checked={selected.includes(result.id)}
                                                   onChange={() => toggleSelect(result.id)}
                                                   className="rounded" />
                                        )}
                                    </td>
                                    <td className="font-medium text-gray-900">{result.student?.full_name}</td>
                                    <td>{result.class?.class}{result.class?.section ? ` — ${result.class.section}` : ''}</td>
                                    <td>{result.subject?.subject_name}</td>
                                    <td className="capitalize text-gray-500">{result.exam_type}</td>
                                    <td>{result.obtained_marks}/{result.total_marks}</td>
                                    <td>
                                        <span className={`font-semibold ${
                                            result.percentage >= 70 ? 'text-emerald-600' :
                                            result.percentage >= 50 ? 'text-amber-600' : 'text-red-600'
                                        }`}>
                                            {Number(result.percentage).toFixed(1)}%
                                        </span>
                                    </td>
                                    <td>
                                        <span className={`font-bold text-sm ${
                                            result.percentage >= 70 ? 'text-emerald-600' :
                                            result.percentage >= 50 ? 'text-amber-600' : 'text-red-600'
                                        }`}>
                                            {result.grade}
                                        </span>
                                    </td>
                                    <td>
                                        {getStatusBadge(result.approval_status as ApprovalStatus, result.class?.class_teacher_id)}
                                    </td>
                                    <td>
                                        {canPrincipalApprove(result) && (
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => handleApproveClick(result)}
                                                    className="btn-success btn-sm"
                                                >
                                                    <CheckIcon className="w-3.5 h-3.5" />
                                                    Approve
                                                </button>
                                                <button
                                                    onClick={() => handleRejectClick(result)}
                                                    className="btn-danger btn-sm"
                                                >
                                                    <XMarkIcon className="w-3.5 h-3.5" />
                                                    Decline
                                                </button>
                                            </div>
                                        )}
                                        {!canPrincipalApprove(result) && result.approval_status !== 'approved' && (
                                            <span className="text-xs text-gray-500">Awaiting CT review</span>
                                        )}
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

            {/* Approve Modal */}
            <Modal isOpen={approveModalOpen} onClose={() => setApproveModalOpen(false)}
                   title="Approve Result" size="md">
                <div className="space-y-4">
                    <div>
                        <p className="text-sm text-gray-600 mb-2">
                            <strong>{currentResult?.student?.full_name}</strong> — {currentResult?.subject?.subject_name}
                        </p>
                    </div>
                    <div>
                        <label className="form-label">Principal Remarks (Optional)</label>
                        <textarea
                            className="form-textarea"
                            rows={3}
                            placeholder="Add any remarks or comments..."
                            value={approveRemarks}
                            onChange={e => setApproveRemarks(e.target.value)}
                        />
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-6">
                    <button className="btn-ghost" onClick={() => setApproveModalOpen(false)}>Cancel</button>
                    <button className="btn-success" onClick={handleApproveSubmit}>
                        <CheckIcon className="w-4 h-4" />
                        Approve
                    </button>
                </div>
            </Modal>

            {/* Reject Modal */}
            <Modal isOpen={rejectModalOpen} onClose={() => setRejectModalOpen(false)}
                   title="Decline Result" size="md">
                <div className="space-y-4">
                    <div>
                        <p className="text-sm text-gray-600 mb-2">
                            <strong>{currentResult?.student?.full_name}</strong> — {currentResult?.subject?.subject_name}
                        </p>
                    </div>
                    <div>
                        <label className="form-label">Rejection Reason <span className="text-red-500">*</span></label>
                        <textarea
                            className="form-textarea"
                            rows={2}
                            placeholder="Explain why this result is being declined..."
                            value={rejectReason}
                            onChange={e => setRejectReason(e.target.value)}
                            required
                        />
                    </div>
                    <div>
                        <label className="form-label">Additional Remarks (Optional)</label>
                        <textarea
                            className="form-textarea"
                            rows={2}
                            placeholder="Add any additional notes..."
                            value={rejectRemarks}
                            onChange={e => setRejectRemarks(e.target.value)}
                        />
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-6">
                    <button className="btn-ghost" onClick={() => setRejectModalOpen(false)}>Cancel</button>
                    <button className="btn-danger" onClick={handleRejectSubmit} disabled={!rejectReason}>
                        <XMarkIcon className="w-4 h-4" />
                        Decline
                    </button>
                </div>
            </Modal>

            {/* Report Cards Modal */}
            <Modal isOpen={reportModalOpen} onClose={() => setReportModalOpen(false)}
                   title="Generate Report Cards" size="lg">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label className="form-label">Class <span className="text-gray-400 text-xs">(optional)</span></label>
                        <select className="form-select" value={rcClassId} onChange={e => setRcClassId(e.target.value)}>
                            <option value="">All Classes</option>
                            {classes.map(c => (
                                <option key={c.id} value={c.id}>{c.class}{c.section ? ` — ${c.section}` : ''}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="form-label">Exam Type <span className="text-red-500">*</span></label>
                        <select className="form-select" value={rcExamType} onChange={e => setRcExamType(e.target.value)}>
                            <option value="">Select Exam Type</option>
                            {Object.entries(examTypes).map(([k, v]) => (
                                <option key={k} value={k}>{v}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="form-label">Academic Year <span className="text-red-500">*</span></label>
                        <select className="form-select" value={rcAcademicYear} onChange={e => setRcAcademicYear(e.target.value)}>
                            <option value="">Select Academic Year</option>
                            {academicYears.map(y => (
                                <option key={y} value={y}>{y}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="form-label">Term <span className="text-red-500">*</span></label>
                        <select className="form-select" value={rcTerm} onChange={e => setRcTerm(e.target.value)}>
                            <option value="">Select Term</option>
                            {Object.entries(terms).map(([k, v]) => (
                                <option key={k} value={k}>{v}</option>
                            ))}
                        </select>
                    </div>
                </div>
                <div className="flex justify-end gap-3 mt-6">
                    <button className="btn-ghost" onClick={() => setReportModalOpen(false)}>Cancel</button>
                    <button className="btn-primary" onClick={handlePreviewReportCards}
                            disabled={!rcExamType || !rcAcademicYear || !rcTerm}>
                        <EyeIcon className="w-4 h-4" />
                        Preview Report Cards
                    </button>
                </div>
            </Modal>
        </AppLayout>
    );
}
