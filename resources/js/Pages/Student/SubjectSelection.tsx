import React, { useState, useMemo } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { CheckIcon, XMarkIcon, InformationCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, Student, SubjectGroup, Subject } from '@/types';

interface Props extends PageProps {
    student: Student;
    subjectGroups: SubjectGroup[];
    selectedSubjects: number[];
    class: any;
}

export default function SubjectSelection({ student, subjectGroups, selectedSubjects, class: classData }: Props) {
    const [selections, setSelections] = useState<Array<{subject_id: number, subject_group_id: number}>>(
        selectedSubjects.map(subjectId => {
            const group = subjectGroups.find(g => g.subjects?.some(s => s.id === subjectId));
            return { subject_id: subjectId, subject_group_id: group?.id || 0 };
        })
    );
    const [validationErrors, setValidationErrors] = useState<string[]>([]);

    const { post, processing } = useForm();

    const toggleSubject = (subjectId: number, groupId: number) => {
        const exists = selections.some(s => s.subject_id === subjectId);

        if (exists) {
            setSelections(selections.filter(s => s.subject_id !== subjectId));
        } else {
            setSelections([...selections, { subject_id: subjectId, subject_group_id: groupId }]);
        }

        setValidationErrors([]);
    };

    const getGroupSelectionCount = (groupId: number) => {
        return selections.filter(s => s.subject_group_id === groupId).length;
    };

    const isGroupValid = (group: SubjectGroup) => {
        const count = getGroupSelectionCount(group.id);

        if (!group.is_optional_group && group.min_select > 0) {
            return count >= group.min_select;
        }

        if (group.is_optional_group) {
            return count >= group.min_select && count <= group.max_select;
        }

        return true;
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Validate
        const errors: string[] = [];

        subjectGroups.forEach(group => {
            const count = getGroupSelectionCount(group.id);

            if (!group.is_optional_group && group.min_select > 0) {
                if (count < group.min_select) {
                    errors.push(`${group.group_name}: Select at least ${group.min_select} subjects (currently ${count})`);
                }
            }

            if (group.is_optional_group) {
                if (count < group.min_select) {
                    errors.push(`${group.group_name}: Select at least ${group.min_select} subject (currently ${count})`);
                }
                if (count > group.max_select) {
                    errors.push(`${group.group_name}: Select maximum ${group.max_select} subjects (currently ${count})`);
                }
            }
        });

        if (errors.length > 0) {
            setValidationErrors(errors);
            return;
        }

        post(route('student.subject-selection.store'), { data: { selections } });
    };

    return (
        <AppLayout title="Select Subjects">
            <Head title="Select Subjects" />

            <div className="max-w-4xl mx-auto">
                <div className="page-header mb-6">
                    <div>
                        <h1 className="page-title">Subject Selection</h1>
                        <p className="page-subtitle">
                            {classData.class}{classData.section ? `-${classData.section}` : ''} · {student.group_stream}
                        </p>
                    </div>
                </div>

                {/* Info Alert */}
                <div className="mb-6 flex gap-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <InformationCircleIcon className="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" />
                    <div className="text-sm text-blue-700">
                        <p className="font-medium">Subject Selection Rules</p>
                        <ul className="list-disc list-inside mt-1 space-y-1">
                            <li><strong>Compulsory subjects</strong>: You must select all subjects marked as required</li>
                            <li><strong>Optional groups</strong>: Choose the specified number of subjects from each group (e.g., 1 from Biology/Computer)</li>
                        </ul>
                    </div>
                </div>

                {/* Stream-Specific Rules */}
                {student.stream === 'ICS' && (
                    <div className="mb-6 flex gap-3 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                        <div className="text-2xl">🖥️</div>
                        <div className="text-sm text-purple-700 flex-1">
                            <p className="font-medium">ICS Stream Requirements</p>
                            <p className="mt-1">You must select <strong>Computer Science</strong> and <strong>NOT Biology</strong>. Computer Science is essential for ICS specialization.</p>
                        </div>
                    </div>
                )}

                {student.stream === 'Pre-Medical' && (
                    <div className="mb-6 flex gap-3 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div className="text-2xl">🔬</div>
                        <div className="text-sm text-red-700 flex-1">
                            <p className="font-medium">Pre-Medical Stream Requirements</p>
                            <p className="mt-1">You must select <strong>Biology</strong> and <strong>NOT Computer Science</strong>. Biology is mandatory for Pre-Medical studies.</p>
                        </div>
                    </div>
                )}

                {/* Validation Errors */}
                {validationErrors.length > 0 && (
                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p className="font-medium text-red-700 mb-2">Please fix the following errors:</p>
                        <ul className="list-disc list-inside space-y-1 text-sm text-red-600">
                            {validationErrors.map((error, idx) => (
                                <li key={idx}>{error}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Subject Groups */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    {subjectGroups.map((group) => {
                        const groupSelections = selections.filter(s => s.subject_group_id === group.id);
                        const isValid = isGroupValid(group);

                        return (
                            <div key={group.id} className={`card border-l-4 ${isValid ? 'border-green-500' : 'border-orange-500'}`}>
                                <div className="card-header bg-gray-50/50">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <p className="card-title">{group.group_name}</p>
                                            <p className="text-sm text-gray-600">
                                                {group.is_optional_group ? (
                                                    <>Choose {group.min_select} from {group.max_select === group.min_select ? group.min_select : `${group.min_select}-${group.max_select}`}</>
                                                ) : (
                                                    <>Select all {group.min_select} required subjects</>
                                                )}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <span className={`text-sm font-medium px-2 py-1 rounded ${isValid ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'}`}>
                                                {groupSelections.length}/{group.min_select}{group.max_select > group.min_select ? `-${group.max_select}` : ''}
                                            </span>
                                            {isValid ? (
                                                <CheckIcon className="w-5 h-5 text-green-600" />
                                            ) : (
                                                <XMarkIcon className="w-5 h-5 text-orange-600" />
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="card-body">
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        {group.subjects?.map((subject) => {
                                            const isSelected = selections.some(s => s.subject_id === subject.id);
                                            const isCompulsory = subject.pivot?.subject_type === 'compulsory';

                                            return (
                                                <label
                                                    key={subject.id}
                                                    className={`flex items-center gap-3 p-3 border-2 rounded-lg cursor-pointer transition ${
                                                        isSelected
                                                            ? 'border-indigo-500 bg-indigo-50'
                                                            : 'border-gray-200 bg-white hover:border-gray-300'
                                                    }`}
                                                >
                                                    <input
                                                        type="checkbox"
                                                        checked={isSelected}
                                                        onChange={() => toggleSubject(subject.id, group.id)}
                                                        disabled={isCompulsory && isSelected}
                                                        className="w-4 h-4 rounded text-indigo-600"
                                                    />
                                                    <div className="flex-1 min-w-0">
                                                        <p className="font-medium text-gray-900 text-sm">{subject.subject_name}</p>
                                                        {isCompulsory && (
                                                            <p className="text-xs text-gray-500">Required</p>
                                                        )}
                                                    </div>
                                                    {isSelected && (
                                                        <CheckIcon className="w-4 h-4 text-indigo-600 flex-shrink-0" />
                                                    )}
                                                </label>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    {/* Submit Button */}
                    <div className="flex gap-3 pt-6 border-t">
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-primary"
                        >
                            {processing ? 'Saving...' : 'Save Selections'}
                        </button>
                        <button
                            type="button"
                            onClick={() => window.history.back()}
                            className="btn-secondary"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
