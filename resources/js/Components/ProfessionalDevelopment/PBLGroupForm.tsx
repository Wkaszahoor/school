import React, { useState } from 'react';
import { useForm } from '@inertiajs/react';
import Modal from '@/Components/Modal';

interface Student {
    id: number;
    full_name: string;
    admission_no: string;
}

interface PBLGroupFormProps {
    isOpen: boolean;
    onClose: () => void;
    assignmentId: number;
    students: Student[];
    onSubmit: (data: { group_name: string; student_ids: number[]; group_leader_id: number }) => void;
    isLoading?: boolean;
}

export default function PBLGroupForm({
    isOpen, onClose, assignmentId, students, onSubmit, isLoading = false
}: PBLGroupFormProps) {
    const [groupName, setGroupName] = useState('');
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [groupLeaderId, setGroupLeaderId] = useState<number | ''>('');

    const handleStudentToggle = (studentId: number) => {
        setSelectedStudents(prev =>
            prev.includes(studentId)
                ? prev.filter(id => id !== studentId)
                : [...prev, studentId]
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!groupName.trim() || selectedStudents.length === 0 || !groupLeaderId) {
            return;
        }
        onSubmit({
            group_name: groupName,
            student_ids: selectedStudents,
            group_leader_id: groupLeaderId as number,
        });
        setGroupName('');
        setSelectedStudents([]);
        setGroupLeaderId('');
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Create Student Group" size="lg">
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="groupName" className="block text-sm font-medium text-gray-900 mb-1">
                        Group Name <span className="text-red-600">*</span>
                    </label>
                    <input
                        id="groupName"
                        type="text"
                        value={groupName}
                        onChange={(e) => setGroupName(e.target.value)}
                        placeholder="e.g., Group A, Team 1"
                        className="form-input"
                        required
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-900 mb-3">
                        Select Students <span className="text-red-600">*</span>
                    </label>
                    <div className="space-y-2 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
                        {students.length === 0 ? (
                            <p className="text-sm text-gray-500">No students available</p>
                        ) : (
                            students.map(student => (
                                <label key={student.id} className="flex items-center gap-3 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                    <input
                                        type="checkbox"
                                        checked={selectedStudents.includes(student.id)}
                                        onChange={() => handleStudentToggle(student.id)}
                                        className="form-checkbox"
                                    />
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-900">{student.full_name}</p>
                                        <p className="text-xs text-gray-500">{student.admission_no}</p>
                                    </div>
                                </label>
                            ))
                        )}
                    </div>
                    {selectedStudents.length > 0 && (
                        <p className="text-xs text-blue-600 mt-2">{selectedStudents.length} student(s) selected</p>
                    )}
                </div>

                <div>
                    <label htmlFor="groupLeader" className="block text-sm font-medium text-gray-900 mb-1">
                        Group Leader <span className="text-red-600">*</span>
                    </label>
                    <select
                        id="groupLeader"
                        value={groupLeaderId}
                        onChange={(e) => setGroupLeaderId(e.target.value ? parseInt(e.target.value) : '')}
                        className="form-select"
                        required
                    >
                        <option value="">Select a student…</option>
                        {selectedStudents.map(studentId => {
                            const student = students.find(s => s.id === studentId);
                            return student ? (
                                <option key={student.id} value={student.id}>
                                    {student.full_name}
                                </option>
                            ) : null;
                        })}
                    </select>
                </div>

                <div className="flex gap-2 justify-end pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        onClick={onClose}
                        className="btn btn-secondary"
                        disabled={isLoading}
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        className="btn btn-primary"
                        disabled={isLoading || !groupName.trim() || selectedStudents.length === 0 || !groupLeaderId}
                    >
                        {isLoading ? 'Creating…' : 'Create Group'}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
