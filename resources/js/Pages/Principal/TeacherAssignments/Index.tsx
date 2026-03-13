import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { TrashIcon, PlusIcon, PencilSquareIcon, EyeIcon, FunnelIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import type { PageProps, TeacherProfile, SchoolClass, Subject } from '@/types';

interface SubjectGroup {
    id: number;
    name: string;
    stream?: string;
}

interface Teacher {
    id: number;
    user_id: number;
    user?: { id: number; name: string };
}

interface Assignment {
    id: number;
    teacher_id: number;
    class_id: number;
    subject_id: number;
    academic_year: string;
    assignment_type: 'class_teacher' | 'subject_teacher';
    group_id?: number;
    teacherProfile?: { user?: { name: string } };
    class?: SchoolClass;
    subject?: Subject;
    group?: SubjectGroup;
}

interface Props extends PageProps {
    assignments: any;
    teachers: TeacherProfile[];
    classes: SchoolClass[];
    subjects: Subject[];
    groups: SubjectGroup[];
}

const ACADEMIC_YEARS = ['2024-25', '2025-26', '2026-27'];
const ASSIGNMENT_TYPES = [
    { value: 'class_teacher', label: 'Class Teacher (Homeroom)' },
    { value: 'subject_teacher', label: 'Subject Teacher' },
];

export default function TeacherAssignmentsIndex({ assignments, teachers, classes, subjects, groups }: Props) {
    const [isOpen, setIsOpen] = useState(false);
    const [editTarget, setEditTarget] = useState<Assignment | null>(null);
    const [viewTarget, setViewTarget] = useState<Assignment | null>(null);
    const [filters, setFilters] = useState({
        teacher_id: new URLSearchParams(window.location.search).get('teacher_id') || '',
        class_id: new URLSearchParams(window.location.search).get('class_id') || '',
        assignment_type: new URLSearchParams(window.location.search).get('assignment_type') || '',
    });
    const { data, setData, post, errors, processing, reset } = useForm({
        teacher_id: '',
        class_id: '',
        subject_id: '',
        academic_year: ACADEMIC_YEARS[1],
        assignment_type: 'subject_teacher',
        group_id: '',
    });
    const { data: editData, setData: setEditData, put: editPut, errors: editErrors, processing: editProcessing, reset: resetEdit } = useForm({
        teacher_id: '',
        class_id: '',
        subject_id: '',
        academic_year: '',
        assignment_type: 'subject_teacher',
        group_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.teacher-assignments.store'), {
            onSuccess: () => {
                reset();
                setIsOpen(false);
            },
        });
    };

    const openEdit = (assignment: Assignment) => {
        setEditData({
            teacher_id: assignment.teacher_id.toString(),
            class_id: assignment.class_id.toString(),
            subject_id: assignment.subject_id.toString(),
            academic_year: assignment.academic_year,
            assignment_type: assignment.assignment_type,
            group_id: assignment.group_id?.toString() || '',
        });
        setEditTarget(assignment);
    };

    const openView = (assignment: Assignment) => {
        setViewTarget(assignment);
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editTarget) return;
        editPut(route('principal.teacher-assignments.update', editTarget.id), {
            onSuccess: () => {
                setEditTarget(null);
                resetEdit();
            },
        });
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to remove this assignment?')) {
            router.delete(route('principal.teacher-assignments.destroy', id));
        }
    };

    const handleFilter = () => {
        const params = new URLSearchParams();
        if (filters.teacher_id) params.append('teacher_id', filters.teacher_id);
        if (filters.class_id) params.append('class_id', filters.class_id);
        if (filters.assignment_type) params.append('assignment_type', filters.assignment_type);
        router.get(route('principal.teacher-assignments.index'), Object.fromEntries(params));
    };

    const handleClearFilters = () => {
        setFilters({ teacher_id: '', class_id: '', assignment_type: '' });
        router.get(route('principal.teacher-assignments.index'));
    };

    const teacherName = (assignment: Assignment) => {
        // Try multiple approaches to get teacher name
        const name = assignment.teacherProfile?.user?.name
            || (assignment as any).teacher?.name
            || (assignment as any).teacher_name
            || 'Unknown Teacher';
        return name;
    };

    const className = (assignment: Assignment) => {
        return assignment.class?.class + (assignment.class?.section ? ` ${assignment.class.section}` : '');
    };

    const subjectName = (assignment: Assignment) => {
        return assignment.subject?.subject_name || 'Unknown Subject';
    };

    const getClassTeacherClasses = (teacherId: number) => {
        const classTeachers = assignments.data.filter(
            a => a.teacher_id === teacherId && a.assignment_type === 'class_teacher'
        );
        if (classTeachers.length === 0) return '—';
        return classTeachers.map(ct => ct.class?.class + (ct.class?.section ? ` ${ct.class.section}` : '')).join(', ');
    };

    return (
        <AppLayout title="Teacher Assignments">
            <Head title="Teacher Assignments" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Teacher Assignments</h1>
                    <p className="page-subtitle">Assign classes and subjects to teachers</p>
                </div>
                <button onClick={() => setIsOpen(true)} className="btn-primary">
                    <PlusIcon className="w-4 h-4" /> New Assignment
                </button>
            </div>

            {/* Filters */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="flex items-center gap-2 mb-4">
                        <FunnelIcon className="w-5 h-5 text-gray-600" />
                        <p className="font-semibold text-gray-900">Filters</p>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {/* Teacher Filter */}
                        <div>
                            <label className="form-label">Teacher</label>
                            <select
                                value={filters.teacher_id}
                                onChange={(e) => setFilters({ ...filters, teacher_id: e.target.value })}
                                className="form-select"
                            >
                                <option value="">All Teachers</option>
                                {teachers.map(teacher => (
                                    <option key={teacher.id} value={teacher.user_id}>
                                        {teacher.user?.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Class Filter */}
                        <div>
                            <label className="form-label">Class</label>
                            <select
                                value={filters.class_id}
                                onChange={(e) => setFilters({ ...filters, class_id: e.target.value })}
                                className="form-select"
                            >
                                <option value="">All Classes</option>
                                {classes.map(cls => (
                                    <option key={cls.id} value={cls.id}>
                                        {cls.class}{cls.section ? ` — ${cls.section}` : ''}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Assignment Type Filter */}
                        <div>
                            <label className="form-label">Assignment Type</label>
                            <select
                                value={filters.assignment_type}
                                onChange={(e) => setFilters({ ...filters, assignment_type: e.target.value })}
                                className="form-select"
                            >
                                <option value="">All Types</option>
                                <option value="class_teacher">Class Teacher</option>
                                <option value="subject_teacher">Subject Teacher</option>
                            </select>
                        </div>

                        {/* Buttons */}
                        <div className="flex items-end gap-2">
                            <button
                                onClick={handleFilter}
                                className="btn-primary flex-1"
                            >
                                Filter
                            </button>
                            {(filters.teacher_id || filters.class_id || filters.assignment_type) && (
                                <button
                                    onClick={handleClearFilters}
                                    className="btn-ghost"
                                >
                                    Clear
                                </button>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>Class Teacher For</th>
                                <th>Class</th>
                                <th>Subject</th>
                                <th>Group</th>
                                <th>Type</th>
                                <th>Academic Year</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {assignments.data.length > 0 ? (
                                assignments.data.map((assignment: Assignment) => (
                                    <tr key={assignment.id}>
                                        <td className="font-medium">{teacherName(assignment)}</td>
                                        <td>
                                            <span className="text-sm text-blue-600 font-semibold">
                                                {getClassTeacherClasses(assignment.teacher_id)}
                                            </span>
                                        </td>
                                        <td>{className(assignment)}</td>
                                        <td>{subjectName(assignment)}</td>
                                        <td>{assignment.group?.name || '—'}</td>
                                        <td>
                                            <Badge color={assignment.assignment_type === 'class_teacher' ? 'purple' : 'green'}>
                                                {assignment.assignment_type === 'class_teacher' ? 'Class Teacher' : 'Subject Teacher'}
                                            </Badge>
                                        </td>
                                        <td><Badge color="blue">{assignment.academic_year}</Badge></td>
                                        <td className="space-x-1">
                                            <button
                                                onClick={() => openView(assignment)}
                                                className="btn-ghost btn-icon text-indigo-600 hover:text-indigo-700"
                                                title="View assignment"
                                            >
                                                <EyeIcon className="w-4 h-4" />
                                            </button>
                                            <button
                                                onClick={() => openEdit(assignment)}
                                                className="btn-ghost btn-icon text-blue-600 hover:text-blue-700"
                                                title="Edit assignment"
                                            >
                                                <PencilSquareIcon className="w-4 h-4" />
                                            </button>
                                            <button
                                                onClick={() => handleDelete(assignment.id)}
                                                className="btn-ghost btn-icon text-red-500 hover:text-red-700"
                                                title="Delete assignment"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={7} className="text-center py-8 text-gray-500">
                                        No assignments found
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {assignments.data.length > 0 && <Pagination data={assignments} />}
            </div>

            {/* Assignment Modal */}
            <Modal isOpen={isOpen} onClose={() => setIsOpen(false)} title="Assign Teacher to Class & Subject">
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Teacher Selection */}
                    <div>
                        <label className="form-label">Teacher *</label>
                        <select
                            value={data.teacher_id}
                            onChange={(e) => setData('teacher_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Teacher</option>
                            {teachers.map(teacher => (
                                <option key={teacher.id} value={teacher.user_id}>
                                    {teacher.user?.name}
                                </option>
                            ))}
                        </select>
                        {errors.teacher_id && <p className="text-red-500 text-xs mt-1">{errors.teacher_id}</p>}
                    </div>

                    {/* Class Selection */}
                    <div>
                        <label className="form-label">Class *</label>
                        <select
                            value={data.class_id}
                            onChange={(e) => setData('class_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Class</option>
                            {classes.map(cls => (
                                <option key={cls.id} value={cls.id}>
                                    {cls.class}{cls.section ? ` — ${cls.section}` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.class_id && <p className="text-red-500 text-xs mt-1">{errors.class_id}</p>}
                    </div>

                    {/* Subject Selection */}
                    <div>
                        <label className="form-label">Subject *</label>
                        <select
                            value={data.subject_id}
                            onChange={(e) => setData('subject_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Subject</option>
                            {subjects.map(subj => (
                                <option key={subj.id} value={subj.id}>
                                    {subj.subject_name}{subj.subject_code ? ` (${subj.subject_code})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.subject_id && <p className="text-red-500 text-xs mt-1">{errors.subject_id}</p>}
                    </div>

                    {/* Assignment Type Selection */}
                    <div>
                        <label className="form-label">Assignment Type *</label>
                        <select
                            value={data.assignment_type}
                            onChange={(e) => setData('assignment_type', e.target.value)}
                            className="form-select"
                            required
                        >
                            {ASSIGNMENT_TYPES.map(type => (
                                <option key={type.value} value={type.value}>{type.label}</option>
                            ))}
                        </select>
                        {errors.assignment_type && <p className="text-red-500 text-xs mt-1">{errors.assignment_type}</p>}
                        <p className="text-xs text-gray-500 mt-1">
                            <strong>Class Teacher:</strong> Overall responsibility for the entire class (homeroom/form teacher)<br/>
                            <strong>Subject Teacher:</strong> Teaches specific subject to the class
                        </p>
                    </div>

                    {/* Subject Group Selection (Optional) */}
                    <div>
                        <label className="form-label">Subject Group (Classes 9-12)</label>
                        <select
                            value={data.group_id}
                            onChange={(e) => setData('group_id', e.target.value)}
                            className="form-select"
                        >
                            <option value="">— No Group —</option>
                            {groups.map(group => (
                                <option key={group.id} value={group.id}>
                                    {group.name}{group.stream ? ` (${group.stream})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.group_id && <p className="text-red-500 text-xs mt-1">{errors.group_id}</p>}
                        <p className="text-xs text-gray-500 mt-1">
                            Optional: Assign to a specific subject group for classes 9-12
                        </p>
                    </div>

                    {/* Academic Year Selection */}
                    <div>
                        <label className="form-label">Academic Year *</label>
                        <select
                            value={data.academic_year}
                            onChange={(e) => setData('academic_year', e.target.value)}
                            className="form-select"
                            required
                        >
                            {ACADEMIC_YEARS.map(year => (
                                <option key={year} value={year}>{year}</option>
                            ))}
                        </select>
                        {errors.academic_year && <p className="text-red-500 text-xs mt-1">{errors.academic_year}</p>}
                    </div>

                    {/* Error Message */}
                    {errors.error && (
                        <div className="p-3 bg-red-50 text-red-700 rounded text-sm">{errors.error}</div>
                    )}

                    {/* Buttons */}
                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setIsOpen(false)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-primary"
                        >
                            {processing ? 'Assigning...' : 'Assign Teacher'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Edit Assignment Modal */}
            <Modal isOpen={!!editTarget} onClose={() => setEditTarget(null)} title="Edit Teacher Assignment">
                <form onSubmit={handleEditSubmit} className="space-y-4">
                    {/* Teacher Selection */}
                    <div>
                        <label className="form-label">Teacher *</label>
                        <select
                            value={editData.teacher_id}
                            onChange={(e) => setEditData('teacher_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Teacher</option>
                            {teachers.map(teacher => (
                                <option key={teacher.id} value={teacher.user_id}>
                                    {teacher.user?.name}
                                </option>
                            ))}
                        </select>
                        {editErrors.teacher_id && <p className="text-red-500 text-xs mt-1">{editErrors.teacher_id}</p>}
                    </div>

                    {/* Class Selection */}
                    <div>
                        <label className="form-label">Class *</label>
                        <select
                            value={editData.class_id}
                            onChange={(e) => setEditData('class_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Class</option>
                            {classes.map(cls => (
                                <option key={cls.id} value={cls.id}>
                                    {cls.class}{cls.section ? ` — ${cls.section}` : ''}
                                </option>
                            ))}
                        </select>
                        {editErrors.class_id && <p className="text-red-500 text-xs mt-1">{editErrors.class_id}</p>}
                    </div>

                    {/* Subject Selection */}
                    <div>
                        <label className="form-label">Subject *</label>
                        <select
                            value={editData.subject_id}
                            onChange={(e) => setEditData('subject_id', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Select Subject</option>
                            {subjects.map(subj => (
                                <option key={subj.id} value={subj.id}>
                                    {subj.subject_name}{subj.subject_code ? ` (${subj.subject_code})` : ''}
                                </option>
                            ))}
                        </select>
                        {editErrors.subject_id && <p className="text-red-500 text-xs mt-1">{editErrors.subject_id}</p>}
                    </div>

                    {/* Assignment Type Selection */}
                    <div>
                        <label className="form-label">Assignment Type *</label>
                        <select
                            value={editData.assignment_type}
                            onChange={(e) => setEditData('assignment_type', e.target.value)}
                            className="form-select"
                            required
                        >
                            {ASSIGNMENT_TYPES.map(type => (
                                <option key={type.value} value={type.value}>{type.label}</option>
                            ))}
                        </select>
                        {editErrors.assignment_type && <p className="text-red-500 text-xs mt-1">{editErrors.assignment_type}</p>}
                    </div>

                    {/* Subject Group Selection (Optional) */}
                    <div>
                        <label className="form-label">Subject Group (Classes 9-12)</label>
                        <select
                            value={editData.group_id}
                            onChange={(e) => setEditData('group_id', e.target.value)}
                            className="form-select"
                        >
                            <option value="">— No Group —</option>
                            {groups.map(group => (
                                <option key={group.id} value={group.id}>
                                    {group.name}{group.stream ? ` (${group.stream})` : ''}
                                </option>
                            ))}
                        </select>
                        {editErrors.group_id && <p className="text-red-500 text-xs mt-1">{editErrors.group_id}</p>}
                    </div>

                    {/* Academic Year Selection */}
                    <div>
                        <label className="form-label">Academic Year *</label>
                        <select
                            value={editData.academic_year}
                            onChange={(e) => setEditData('academic_year', e.target.value)}
                            className="form-select"
                            required
                        >
                            {ACADEMIC_YEARS.map(year => (
                                <option key={year} value={year}>{year}</option>
                            ))}
                        </select>
                        {editErrors.academic_year && <p className="text-red-500 text-xs mt-1">{editErrors.academic_year}</p>}
                    </div>

                    {/* Error Message */}
                    {editErrors.error && (
                        <div className="p-3 bg-red-50 text-red-700 rounded text-sm">{editErrors.error}</div>
                    )}

                    {/* Buttons */}
                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setEditTarget(null)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={editProcessing}
                            className="btn-primary"
                        >
                            {editProcessing ? 'Updating...' : 'Update Assignment'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* View Assignment Modal */}
            <Modal isOpen={!!viewTarget} onClose={() => setViewTarget(null)} title="Assignment Details">
                {viewTarget && (
                    <div className="space-y-4">
                        {/* Teacher */}
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Teacher</p>
                            <p className="text-lg font-bold text-gray-900 mt-1">{teacherName(viewTarget)}</p>
                            {((viewTarget as any).teacher?.email || viewTarget.teacherProfile?.user?.email) && (
                                <p className="text-sm text-gray-600 mt-2">
                                    <span className="text-gray-500">Email: </span>
                                    {(viewTarget as any).teacher?.email || viewTarget.teacherProfile?.user?.email}
                                </p>
                            )}
                        </div>

                        {/* Class */}
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Class</p>
                            <p className="text-lg font-bold text-gray-900 mt-1">{className(viewTarget)}</p>
                        </div>

                        {/* Subject */}
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject</p>
                            <p className="text-lg font-bold text-gray-900 mt-1">{subjectName(viewTarget)}</p>
                        </div>

                        {/* Assignment Type */}
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Assignment Type</p>
                            <div className="mt-1">
                                <Badge color={viewTarget.assignment_type === 'class_teacher' ? 'purple' : 'green'}>
                                    {viewTarget.assignment_type === 'class_teacher' ? 'Class Teacher (Homeroom)' : 'Subject Teacher'}
                                </Badge>
                            </div>
                        </div>

                        {/* Subject Group */}
                        {viewTarget.group && (
                            <div className="p-4 bg-gray-50 rounded-lg">
                                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Subject Group</p>
                                <p className="text-lg font-bold text-gray-900 mt-1">
                                    {viewTarget.group.name}
                                    {viewTarget.group.stream && ` (${viewTarget.group.stream})`}
                                </p>
                            </div>
                        )}

                        {/* Academic Year */}
                        <div className="p-4 bg-gray-50 rounded-lg">
                            <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Academic Year</p>
                            <div className="mt-1">
                                <Badge color="blue">{viewTarget.academic_year}</Badge>
                            </div>
                        </div>

                        {/* Close Button */}
                        <div className="flex gap-2 justify-end pt-4 border-t">
                            <button
                                type="button"
                                onClick={() => setViewTarget(null)}
                                className="btn-primary"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </AppLayout>
    );
}
