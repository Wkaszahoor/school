import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, router } from '@inertiajs/react';
import { useState } from 'react';
import Modal from '@/Components/Modal';
import Pagination from '@/Components/Pagination';
import { TrashIcon, PencilIcon, CalendarIcon, FunnelIcon } from '@heroicons/react/24/outline';
import { StudentDatesheet, PaginatedData } from '@/types';

interface PageProps {
    datasheets?: PaginatedData<StudentDatesheet>;
    examPeriods?: string[];
    classNames?: string[];
    academicYear?: string;
    selectedExamPeriod?: string;
    selectedClassName?: string;
}

const defaultPaginatedData: PaginatedData<StudentDatesheet> = {
    data: [],
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: null,
    to: null,
    links: [],
    next_page_url: null,
    prev_page_url: null,
};

export default function Index({
    datasheets = defaultPaginatedData,
    examPeriods = [],
    classNames = [],
    academicYear = '2025-26',
    selectedExamPeriod = '',
    selectedClassName = '',
}: PageProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [editingDatesheet, setEditingDatesheet] = useState<StudentDatesheet | null>(null);
    const [filterExamPeriod, setFilterExamPeriod] = useState(selectedExamPeriod);
    const [filterClassName, setFilterClassName] = useState(selectedClassName);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        class_name: '',
        subject_name: '',
        exam_date: '',
        exam_time: '',
        room_no: '',
        total_marks: '100',
        exam_period: '',
        academic_year: academicYear,
    });

    const handleCreate = () => {
        setEditingDatesheet(null);
        reset();
        setIsModalOpen(true);
    };

    const handleEdit = (sheet: StudentDatesheet) => {
        setEditingDatesheet(sheet);
        setData({
            class_name: sheet.class_name,
            subject_name: sheet.subject_name,
            exam_date: sheet.exam_date,
            exam_time: sheet.exam_time || '',
            room_no: sheet.room_no || '',
            total_marks: sheet.total_marks.toString(),
            exam_period: sheet.exam_period || '',
            academic_year: sheet.academic_year || academicYear,
        });
        setIsModalOpen(true);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (editingDatesheet) {
            put(route('principal.datesheets.update', editingDatesheet.id), {
                onSuccess: () => {
                    reset();
                    setIsModalOpen(false);
                    setEditingDatesheet(null);
                },
            });
        } else {
            post(route('principal.datesheets.store'), {
                onSuccess: () => {
                    reset();
                    setIsModalOpen(false);
                },
            });
        }
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this datesheet entry?')) {
            router.delete(route('principal.datesheets.destroy', id));
        }
    };

    const handleFilter = () => {
        const params: Record<string, any> = { academic_year: academicYear };
        if (filterExamPeriod) params.exam_period = filterExamPeriod;
        if (filterClassName) params.class_name = filterClassName;
        router.get(route('principal.datesheets.index'), params);
    };

    const closeModal = () => {
        setIsModalOpen(false);
        reset();
        setEditingDatesheet(null);
    };

    return (
        <AppLayout title="Exam Datasheets">
            <Head title="Exam Datasheets" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Exam Datasheets</h1>
                    <p className="page-subtitle">Manage exam schedule and dates for your classes</p>
                </div>
                <button onClick={handleCreate} className="btn-primary">
                    Add New Entry
                </button>
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
                            <label className="form-label">Exam Period</label>
                            <select
                                value={filterExamPeriod}
                                onChange={(e) => setFilterExamPeriod(e.target.value)}
                                className="form-select"
                            >
                                <option value="">All Periods</option>
                                {examPeriods.map((period) => (
                                    <option key={period} value={period}>
                                        {period}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <label className="form-label">Class Name</label>
                            <select
                                value={filterClassName}
                                onChange={(e) => setFilterClassName(e.target.value)}
                                className="form-select"
                            >
                                <option value="">All Classes</option>
                                {classNames.map((name) => (
                                    <option key={name} value={name}>
                                        {name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="flex items-end">
                            <button onClick={handleFilter} className="btn-secondary w-full">
                                Apply Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {/* Table */}
            <div className="card">
                {datasheets.data.length === 0 ? (
                    <div className="text-center py-12">
                        <CalendarIcon className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                        <p className="text-gray-500 mb-4">No datasheets found</p>
                        <button onClick={handleCreate} className="btn-primary">
                            Create First Entry
                        </button>
                    </div>
                ) : (
                    <div className="table-wrapper">
                        <table className="table">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Subject</th>
                                    <th>Exam Date</th>
                                    <th>Time</th>
                                    <th>Marks</th>
                                    <th>Room</th>
                                    <th>Period</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {datasheets.data.map((sheet) => (
                                    <tr key={sheet.id}>
                                        <td className="font-medium">{sheet.class_name}</td>
                                        <td>{sheet.subject_name}</td>
                                        <td>{new Date(sheet.exam_date).toLocaleDateString('en-GB')}</td>
                                        <td>{sheet.exam_time || '—'}</td>
                                        <td className="text-center">{sheet.total_marks}</td>
                                        <td>{sheet.room_no || '—'}</td>
                                        <td className="text-sm">{sheet.exam_period || '—'}</td>
                                        <td>
                                            <div className="flex gap-2">
                                                <button
                                                    onClick={() => handleEdit(sheet)}
                                                    className="btn-ghost btn-icon text-blue-500 hover:text-blue-700"
                                                    title="Edit"
                                                >
                                                    <PencilIcon className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={() => handleDelete(sheet.id)}
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

                {datasheets.data.length > 0 && <Pagination data={datasheets} />}
            </div>

            {/* Modal */}
            <Modal isOpen={isModalOpen} onClose={closeModal} title={editingDatesheet ? 'Edit Datesheet Entry' : 'Add Datesheet Entry'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Class Name *</label>
                            <input
                                type="text"
                                value={data.class_name}
                                onChange={(e) => setData('class_name', e.target.value)}
                                placeholder="e.g., Class 10A"
                                className="form-input"
                            />
                            {errors.class_name && <p className="text-red-500 text-xs mt-1">{errors.class_name}</p>}
                        </div>

                        <div>
                            <label className="form-label">Subject Name *</label>
                            <input
                                type="text"
                                value={data.subject_name}
                                onChange={(e) => setData('subject_name', e.target.value)}
                                placeholder="e.g., Mathematics"
                                className="form-input"
                            />
                            {errors.subject_name && <p className="text-red-500 text-xs mt-1">{errors.subject_name}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Exam Date *</label>
                            <input
                                type="date"
                                value={data.exam_date}
                                onChange={(e) => setData('exam_date', e.target.value)}
                                className="form-input"
                            />
                            {errors.exam_date && <p className="text-red-500 text-xs mt-1">{errors.exam_date}</p>}
                        </div>

                        <div>
                            <label className="form-label">Exam Time</label>
                            <input
                                type="time"
                                value={data.exam_time}
                                onChange={(e) => setData('exam_time', e.target.value)}
                                className="form-input"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Total Marks *</label>
                            <input
                                type="number"
                                min="1"
                                value={data.total_marks}
                                onChange={(e) => setData('total_marks', e.target.value)}
                                className="form-input"
                            />
                            {errors.total_marks && <p className="text-red-500 text-xs mt-1">{errors.total_marks}</p>}
                        </div>

                        <div>
                            <label className="form-label">Room No.</label>
                            <input
                                type="text"
                                value={data.room_no}
                                onChange={(e) => setData('room_no', e.target.value)}
                                placeholder="e.g., A-101"
                                className="form-input"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="form-label">Exam Period *</label>
                            <input
                                type="text"
                                value={data.exam_period}
                                onChange={(e) => setData('exam_period', e.target.value)}
                                placeholder="e.g., Midterm 2026"
                                className="form-input"
                            />
                            {errors.exam_period && <p className="text-red-500 text-xs mt-1">{errors.exam_period}</p>}
                        </div>

                        <div>
                            <label className="form-label">Academic Year</label>
                            <input type="text" value={data.academic_year} disabled className="form-input bg-gray-100" />
                        </div>
                    </div>

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button type="button" onClick={closeModal} className="btn-ghost">
                            Cancel
                        </button>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving...' : editingDatesheet ? 'Update' : 'Create'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
