import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import { TrashIcon, PencilIcon } from '@heroicons/react/24/outline';
import { AttendanceCriteria, SchoolClass, Subject, PaginatedData } from '@/types';

interface PageProps {
    criteria: PaginatedData<AttendanceCriteria>;
    classes: SchoolClass[];
    subjects: Subject[];
    academicYear: string;
}

export default function Index({ criteria, classes, subjects, academicYear }: PageProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingCriterion, setEditingCriterion] = useState<AttendanceCriteria | null>(null);
    const { data, setData, post, put, processing, errors, reset } = useForm({
        class_id: '',
        subject_id: '',
        criteria_type: 'class' as 'class' | 'subject',
        min_attendance_percent: 75,
        max_allowed_absences: '',
        academic_year: academicYear,
    });

    const handleCreate = () => {
        setEditingCriterion(null);
        reset();
        setIsModalOpen(true);
    };

    const handleEdit = (item: AttendanceCriteria) => {
        setEditingCriterion(item);
        setData({
            class_id: item.class_id?.toString() || '',
            subject_id: item.subject_id?.toString() || '',
            criteria_type: item.criteria_type,
            min_attendance_percent: item.min_attendance_percent,
            max_allowed_absences: item.max_allowed_absences?.toString() || '',
            academic_year: item.academic_year || academicYear,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingCriterion) {
            put(route('principal.attendance-criteria.update', editingCriterion.id), {
                onSuccess: () => {
                    reset();
                    setIsModalOpen(false);
                    setEditingCriterion(null);
                },
            });
        } else {
            post(route('principal.attendance-criteria.store'), {
                onSuccess: () => {
                    reset();
                    setIsModalOpen(false);
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this criterion?')) {
            router.delete(route('principal.attendance-criteria.destroy', id));
        }
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
        setEditingCriterion(null);
    };

    return (
        <AppLayout title="Attendance Criteria Management">
            <Head title="Attendance Criteria Management" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Attendance Criteria Management</h1>
                    <p className="page-subtitle">Define minimum attendance requirements for classes and subjects</p>
                </div>
                <button onClick={handleCreate} className="btn-primary">
                    Add New Criterion
                </button>
            </div>

            <div className="card">
                {criteria.data.length === 0 ? (
                    <div className="text-center py-12">
                        <p className="text-gray-500">No criteria defined yet</p>
                        <button onClick={handleCreate} className="btn-primary mt-4">
                            Create First Criterion
                        </button>
                    </div>
                ) : (
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Type</th>
                                    <th>Min Attendance</th>
                                    <th>Max Absences</th>
                                    <th>Set By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {criteria.data.map((item) => (
                                    <tr key={item.id}>
                                        <td className="font-medium">{item.class?.class}</td>
                                        <td>
                                            <Badge color={item.criteria_type === 'class' ? 'blue' : 'purple'}>
                                                {item.criteria_type === 'class' ? 'Class-wide' : item.subject?.subject_name || 'Subject'}
                                            </Badge>
                                        </td>
                                        <td>
                                            <span className="font-semibold">{item.min_attendance_percent}%</span>
                                        </td>
                                        <td>{item.max_allowed_absences ? `${item.max_allowed_absences} days` : '—'}</td>
                                        <td className="text-sm text-gray-600">{item.createdBy?.name || '—'}</td>
                                        <td>
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => handleEdit(item)}
                                                    className="btn-ghost btn-icon text-blue-500 hover:text-blue-700"
                                                    title="Edit"
                                                >
                                                    <PencilIcon className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(item.id)}
                                                    className="btn-ghost btn-icon text-red-500 hover:text-red-700"
                                                    title="Delete"
                                                >
                                                    <TrashIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {criteria.data.length > 0 && <Pagination data={criteria} />}
            </div>

            <Modal isOpen={isModalOpen} onClose={closeModal} title={editingCriterion ? 'Edit Attendance Criterion' : 'Add Attendance Criterion'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="form-label">Class *</label>
                        <select
                            value={data.class_id}
                            onChange={(e) => setData('class_id', e.target.value)}
                            className="form-select"
                        >
                            <option value="">Select a class</option>
                            {classes.map((cls) => (
                                <option key={cls.id} value={cls.id}>
                                    {cls.class} {cls.section ? `(${cls.section})` : ''}
                                </option>
                            ))}
                        </select>
                        {errors.class_id && <p className="text-red-500 text-xs mt-1">{errors.class_id}</p>}
                    </div>

                    <div>
                        <label className="form-label">Criteria Type *</label>
                        <select
                            value={data.criteria_type}
                            onChange={(e) => setData('criteria_type', e.target.value as 'class' | 'subject')}
                            className="form-select"
                        >
                            <option value="class">Class-wide</option>
                            <option value="subject">Subject-specific</option>
                        </select>
                    </div>

                    {data.criteria_type === 'subject' && (
                        <div>
                            <label className="form-label">Subject (Optional)</label>
                            <select
                                value={data.subject_id}
                                onChange={(e) => setData('subject_id', e.target.value)}
                                className="form-select"
                            >
                                <option value="">Select a subject</option>
                                {subjects.map((subj) => (
                                    <option key={subj.id} value={subj.id}>
                                        {subj.subject_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div>
                        <label className="form-label">Minimum Attendance Percentage *</label>
                        <div className="flex gap-2 items-center">
                            <input
                                type="range"
                                min="0"
                                max="100"
                                value={data.min_attendance_percent}
                                onChange={(e) => setData('min_attendance_percent', parseInt(e.target.value))}
                                className="flex-1"
                            />
                            <input
                                type="number"
                                min="0"
                                max="100"
                                value={data.min_attendance_percent}
                                onChange={(e) => setData('min_attendance_percent', parseInt(e.target.value) || 0)}
                                className="form-input w-20"
                            />
                            <span>%</span>
                        </div>
                        {errors.min_attendance_percent && (
                            <p className="text-red-500 text-xs mt-1">{errors.min_attendance_percent}</p>
                        )}
                    </div>

                    <div>
                        <label className="form-label">Maximum Allowed Absences (Optional)</label>
                        <input
                            type="number"
                            min="0"
                            value={data.max_allowed_absences}
                            onChange={(e) => setData('max_allowed_absences', e.target.value)}
                            placeholder="Leave empty if not applicable"
                            className="form-input"
                        />
                        {errors.max_allowed_absences && (
                            <p className="text-red-500 text-xs mt-1">{errors.max_allowed_absences}</p>
                        )}
                    </div>

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button type="button" onClick={closeModal} className="btn-ghost">
                            Cancel
                        </button>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving...' : editingCriterion ? 'Update' : 'Create'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
