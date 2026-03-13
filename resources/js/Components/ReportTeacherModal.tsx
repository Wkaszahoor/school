import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { XMarkIcon } from '@heroicons/react/24/outline';

interface Props {
    isOpen: boolean;
    onClose: () => void;
    classId: number;
    subjectTeachers: Array<{ id: number; name: string }>;
}

type ReportType = 'general' | 'performance' | 'conduct' | 'attendance';

const REPORT_TYPES: Record<ReportType, { label: string; description: string }> = {
    general: { label: 'General', description: 'General observations and notes' },
    performance: { label: 'Performance', description: 'Teaching effectiveness and lesson quality' },
    conduct: { label: 'Conduct', description: 'Behavior, professionalism, and classroom management' },
    attendance: { label: 'Attendance', description: 'Punctuality and lesson coverage' },
};

export default function ReportTeacherModal({ isOpen, onClose, classId, subjectTeachers }: Props) {
    const [formData, setFormData] = useState({
        subject_teacher_id: '',
        report_type: 'general' as ReportType,
        notes: '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setError('');

        if (!formData.subject_teacher_id || !formData.notes.trim()) {
            setError('Please fill in all fields');
            return;
        }

        if (formData.notes.length < 10) {
            setError('Notes must be at least 10 characters');
            return;
        }

        setSubmitting(true);
        router.post(route('teacher.teacher-reports.store'), {
            subject_teacher_id: parseInt(formData.subject_teacher_id),
            class_id: classId,
            report_type: formData.report_type,
            notes: formData.notes,
        }, {
            onSuccess: () => {
                setFormData({ subject_teacher_id: '', report_type: 'general', notes: '' });
                onClose();
            },
            onError: (errors: any) => {
                setError(errors.notes || 'Failed to submit report');
                setSubmitting(false);
            },
        });
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div className="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div className="flex items-center justify-between p-6 border-b">
                    <h2 className="text-lg font-bold text-gray-900">Report Subject Teacher</h2>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <XMarkIcon className="w-6 h-6" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    {error && (
                        <div className="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <div className="form-group">
                        <label className="form-label">Subject Teacher</label>
                        <select
                            className="form-select"
                            value={formData.subject_teacher_id}
                            onChange={(e) => setFormData({ ...formData, subject_teacher_id: e.target.value })}
                        >
                            <option value="">Select a teacher...</option>
                            {subjectTeachers.map(teacher => (
                                <option key={teacher.id} value={teacher.id}>
                                    {teacher.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Report Type</label>
                        <select
                            className="form-select"
                            value={formData.report_type}
                            onChange={(e) => setFormData({ ...formData, report_type: e.target.value as ReportType })}
                        >
                            {Object.entries(REPORT_TYPES).map(([key, { label }]) => (
                                <option key={key} value={key}>
                                    {label}
                                </option>
                            ))}
                        </select>
                        <p className="text-xs text-gray-500 mt-1">
                            {REPORT_TYPES[formData.report_type as ReportType].description}
                        </p>
                    </div>

                    <div className="form-group">
                        <label className="form-label">Notes</label>
                        <textarea
                            className="form-input min-h-[120px] resize-none"
                            placeholder="Enter detailed notes (10-1000 characters)..."
                            value={formData.notes}
                            onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                            maxLength={1000}
                        />
                        <p className="text-xs text-gray-500 mt-1">
                            {formData.notes.length}/1000 characters
                        </p>
                    </div>

                    <div className="flex gap-3 pt-4">
                        <button
                            type="button"
                            onClick={onClose}
                            className="btn-ghost flex-1"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={submitting}
                            className="btn-primary flex-1"
                        >
                            {submitting ? 'Submitting...' : 'Submit Report'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
