import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';

interface Teacher {
    id: number;
    name: string;
    email: string;
}

interface EnrollCourseModalProps {
    isOpen: boolean;
    onClose: () => void;
    courseId: number;
    teachers: Teacher[];
    onSubmit: (data: { teacher_ids: number[] }) => void;
    isLoading?: boolean;
}

export default function EnrollCourseModal({
    isOpen, onClose, courseId, teachers, onSubmit, isLoading = false
}: EnrollCourseModalProps) {
    const [selectedTeachers, setSelectedTeachers] = useState<number[]>([]);

    const handleTeacherToggle = (teacherId: number) => {
        setSelectedTeachers(prev =>
            prev.includes(teacherId)
                ? prev.filter(id => id !== teacherId)
                : [...prev, teacherId]
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedTeachers.length === 0) return;
        onSubmit({ teacher_ids: selectedTeachers });
        setSelectedTeachers([]);
    };

    const handleClose = () => {
        setSelectedTeachers([]);
        onClose();
    };

    return (
        <Modal isOpen={isOpen} onClose={handleClose} title="Enroll Teachers in Course" size="md">
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-900 mb-3">
                        Select Teachers <span className="text-red-600">*</span>
                    </label>
                    <div className="space-y-2 max-h-72 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        {teachers.length === 0 ? (
                            <p className="text-sm text-gray-500">No teachers available</p>
                        ) : (
                            teachers.map(teacher => (
                                <label key={teacher.id} className="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input
                                        type="checkbox"
                                        checked={selectedTeachers.includes(teacher.id)}
                                        onChange={() => handleTeacherToggle(teacher.id)}
                                        className="form-checkbox"
                                    />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900">{teacher.name}</p>
                                        <p className="text-xs text-gray-500">{teacher.email}</p>
                                    </div>
                                </label>
                            ))
                        )}
                    </div>
                    {selectedTeachers.length > 0 && (
                        <p className="text-xs text-blue-600 mt-2">{selectedTeachers.length} teacher(s) selected</p>
                    )}
                </div>

                <div className="flex gap-2 justify-end pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        onClick={handleClose}
                        className="btn btn-secondary"
                        disabled={isLoading}
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={isLoading || selectedTeachers.length === 0}
                    >
                        {isLoading ? 'Enrolling…' : 'Enroll Teachers'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
