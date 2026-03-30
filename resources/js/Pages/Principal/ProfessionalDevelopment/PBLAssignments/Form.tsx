import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Teacher { id: number; name: string; }
interface SchoolClass { id: number; class: string; section: string; }
interface Subject { id: number; subject_name: string; }
interface Rubric { id: number; rubric_name: string; }

interface Assignment {
    id: number;
    project_title: string;
    description: string;
    teacher_id: number | '';
    class_id: number | '';
    subject_id: number | '';
    rubric_id: number | '';
    project_type: string;
    learning_objectives: string;
    requirements: string;
    group_size: number | '';
    start_date: string;
    due_date: string;
    presentation_date: string;
    total_marks: number;
    status: string;
}

interface Props extends PageProps {
    assignment?: Assignment;
    teachers: Teacher[];
    classes: SchoolClass[];
    subjects: Subject[];
    rubrics: Rubric[];
}

export default function PBLAssignmentForm({ assignment, teachers, classes, subjects, rubrics }: Props) {
    const isEditing = !!assignment;

    const { data, setData, post, put, processing, errors } = useForm({
        project_title: assignment?.project_title || '',
        description: assignment?.description || '',
        teacher_id: assignment?.teacher_id || '',
        class_id: assignment?.class_id || '',
        subject_id: assignment?.subject_id || '',
        rubric_id: assignment?.rubric_id || '',
        project_type: assignment?.project_type || 'group',
        learning_objectives: assignment?.learning_objectives || '',
        requirements: assignment?.requirements || '',
        group_size: assignment?.group_size || '',
        start_date: assignment?.start_date || '',
        due_date: assignment?.due_date || '',
        presentation_date: assignment?.presentation_date || '',
        total_marks: assignment?.total_marks || 100,
        status: assignment?.status || 'draft',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEditing) {
            put(route('principal.professional-development.pbl-assignments.update', assignment.id));
        } else {
            post(route('principal.professional-development.pbl-assignments.store'));
        }
    };

    return (
        <AppLayout title={isEditing ? 'Edit PBL Assignment' : 'Create PBL Assignment'}>
            <Head title={isEditing ? 'Edit PBL Assignment' : 'Create PBL Assignment'} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">{isEditing ? 'Edit Assignment' : 'Create PBL Assignment'}</h1>
                    <p className="page-subtitle">{isEditing ? 'Update assignment details' : 'Add a new project-based learning assignment'}</p>
                </div>
            </div>

            <div className="card">
                <form onSubmit={handleSubmit} className="space-y-6">

                    {/* Title & Teacher */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Project Title <span className="text-red-600">*</span>
                            </label>
                            <input
                                type="text"
                                value={data.project_title}
                                onChange={(e) => setData('project_title', e.target.value)}
                                className="form-input"
                                placeholder="e.g., Climate Change Research Project"
                                required
                            />
                            {errors.project_title && <p className="text-sm text-red-600 mt-1">{errors.project_title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Assigned Teacher <span className="text-red-600">*</span>
                            </label>
                            <select
                                value={data.teacher_id}
                                onChange={(e) => setData('teacher_id', e.target.value)}
                                className="form-select"
                                required
                            >
                                <option value="">-- Select Teacher --</option>
                                {teachers.map(t => (
                                    <option key={t.id} value={t.id}>{t.name}</option>
                                ))}
                            </select>
                            {errors.teacher_id && <p className="text-sm text-red-600 mt-1">{errors.teacher_id}</p>}
                        </div>
                    </div>

                    {/* Description */}
                    <div>
                        <label className="block text-sm font-medium text-gray-900 mb-1">
                            Description <span className="text-red-600">*</span>
                        </label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="form-textarea"
                            rows={4}
                            placeholder="Describe the project goals and overview..."
                            required
                        />
                        {errors.description && <p className="text-sm text-red-600 mt-1">{errors.description}</p>}
                    </div>

                    {/* Class, Subject, Rubric */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Class</label>
                            <select
                                value={data.class_id}
                                onChange={(e) => setData('class_id', e.target.value)}
                                className="form-select"
                            >
                                <option value="">-- Select Class --</option>
                                {classes.map(c => (
                                    <option key={c.id} value={c.id}>{c.class} {c.section}</option>
                                ))}
                            </select>
                            {errors.class_id && <p className="text-sm text-red-600 mt-1">{errors.class_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Subject</label>
                            <select
                                value={data.subject_id}
                                onChange={(e) => setData('subject_id', e.target.value)}
                                className="form-select"
                            >
                                <option value="">-- Select Subject --</option>
                                {subjects.map(s => (
                                    <option key={s.id} value={s.id}>{s.subject_name}</option>
                                ))}
                            </select>
                            {errors.subject_id && <p className="text-sm text-red-600 mt-1">{errors.subject_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Rubric</label>
                            <select
                                value={data.rubric_id}
                                onChange={(e) => setData('rubric_id', e.target.value)}
                                className="form-select"
                            >
                                <option value="">-- No Rubric --</option>
                                {rubrics.map(r => (
                                    <option key={r.id} value={r.id}>{r.rubric_name}</option>
                                ))}
                            </select>
                            {errors.rubric_id && <p className="text-sm text-red-600 mt-1">{errors.rubric_id}</p>}
                        </div>
                    </div>

                    {/* Project Type, Group Size, Total Marks */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Project Type <span className="text-red-600">*</span>
                            </label>
                            <select
                                value={data.project_type}
                                onChange={(e) => setData('project_type', e.target.value)}
                                className="form-select"
                                required
                            >
                                <option value="individual">Individual</option>
                                <option value="group">Group</option>
                            </select>
                            {errors.project_type && <p className="text-sm text-red-600 mt-1">{errors.project_type}</p>}
                        </div>

                        {data.project_type === 'group' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-900 mb-1">Group Size</label>
                                <input
                                    type="number"
                                    min="2"
                                    value={data.group_size}
                                    onChange={(e) => setData('group_size', e.target.value ? parseInt(e.target.value) : '')}
                                    className="form-input"
                                    placeholder="e.g., 4"
                                />
                                {errors.group_size && <p className="text-sm text-red-600 mt-1">{errors.group_size}</p>}
                            </div>
                        )}

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Total Marks <span className="text-red-600">*</span>
                            </label>
                            <input
                                type="number"
                                min="1"
                                value={data.total_marks}
                                onChange={(e) => setData('total_marks', parseInt(e.target.value) || 100)}
                                className="form-input"
                                required
                            />
                            {errors.total_marks && <p className="text-sm text-red-600 mt-1">{errors.total_marks}</p>}
                        </div>
                    </div>

                    {/* Dates */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Start Date <span className="text-red-600">*</span>
                            </label>
                            <input
                                type="date"
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                                className="form-input"
                                required
                            />
                            {errors.start_date && <p className="text-sm text-red-600 mt-1">{errors.start_date}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">
                                Due Date <span className="text-red-600">*</span>
                            </label>
                            <input
                                type="date"
                                value={data.due_date}
                                onChange={(e) => setData('due_date', e.target.value)}
                                className="form-input"
                                required
                            />
                            {errors.due_date && <p className="text-sm text-red-600 mt-1">{errors.due_date}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-900 mb-1">Presentation Date</label>
                            <input
                                type="date"
                                value={data.presentation_date}
                                onChange={(e) => setData('presentation_date', e.target.value)}
                                className="form-input"
                            />
                            {errors.presentation_date && <p className="text-sm text-red-600 mt-1">{errors.presentation_date}</p>}
                        </div>
                    </div>

                    {/* Learning Objectives */}
                    <div>
                        <label className="block text-sm font-medium text-gray-900 mb-1">Learning Objectives</label>
                        <textarea
                            value={data.learning_objectives}
                            onChange={(e) => setData('learning_objectives', e.target.value)}
                            className="form-textarea"
                            rows={3}
                            placeholder="List the key learning objectives..."
                        />
                        {errors.learning_objectives && <p className="text-sm text-red-600 mt-1">{errors.learning_objectives}</p>}
                    </div>

                    {/* Requirements */}
                    <div>
                        <label className="block text-sm font-medium text-gray-900 mb-1">Requirements & Deliverables</label>
                        <textarea
                            value={data.requirements}
                            onChange={(e) => setData('requirements', e.target.value)}
                            className="form-textarea"
                            rows={3}
                            placeholder="Specify what students need to submit..."
                        />
                        {errors.requirements && <p className="text-sm text-red-600 mt-1">{errors.requirements}</p>}
                    </div>

                    {/* Status */}
                    <div className="max-w-xs">
                        <label className="block text-sm font-medium text-gray-900 mb-1">
                            Status <span className="text-red-600">*</span>
                        </label>
                        <select
                            value={data.status}
                            onChange={(e) => setData('status', e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="in-progress">In Progress</option>
                            <option value="evaluation">Evaluation</option>
                            <option value="completed">Completed</option>
                        </select>
                        {errors.status && <p className="text-sm text-red-600 mt-1">{errors.status}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-2 justify-end pt-4 border-t border-gray-200">
                        <a
                            href={route('principal.professional-development.pbl-assignments.index')}
                            className="btn btn-secondary"
                        >
                            <ArrowLeftIcon className="w-4 h-4" />
                            Cancel
                        </a>
                        <button type="submit" className="btn btn-primary" disabled={processing}>
                            {processing ? 'Saving…' : isEditing ? 'Update Assignment' : 'Create Assignment'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
