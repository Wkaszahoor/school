import React, { useState } from 'react';
import { Head, router, useForm, Link } from '@inertiajs/react';
import { PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Modal from '@/Components/Modal';
import Badge from '@/Components/Badge';
import type { PageProps } from '@/types';

declare const route: (name: string, params?: any) => string;

interface Student {
    id: number;
    admission_no: string;
    full_name: string;
    class: string;
    section?: string | null;
    class_id: number;
    stream?: string | null;
}

interface Teacher {
    id: number;
    user_id: number;
    teacher_name: string;
    teacher_email: string;
    role: 'class_teacher' | 'subject_teacher';
    subject_id?: number;
    subject_name?: string;
}

interface SubjectGroupType {
    id: number;
    name: string;
    group_name: string;
    stream?: string;
    description?: string;
    is_active: boolean;
    education_level?: 'SSC' | 'HSSC' | null;
    subject_count: number;
    student_count: number;
    subjects: Array<{ id: number; subject_name: string; subject_type: string }>;
    teachers: Teacher[];
    students?: Student[];
}

interface Subject {
    id: number;
    subject_name: string;
    subject_code: string;
}

interface TeacherOption {
    id: number;
    name: string;
    email: string;
}

interface Props extends PageProps {
    groups: SubjectGroupType[];
    allSubjects: Subject[];
    allStudents: Student[];
    allTeachers: TeacherOption[];
}

export default function SubjectGroupsIndex({ groups, allSubjects, allStudents, allTeachers }: Props) {
    const [selectedGroup, setSelectedGroup] = useState<SubjectGroupType | null>(null);
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isEditModalOpen, setIsEditModalOpen] = useState(false);
    const [addSubjectSelect, setAddSubjectSelect] = useState('');
    const [addSubjectType, setAddSubjectType] = useState<'compulsory' | 'optional' | 'major'>('compulsory');
    const [filterSubjectName, setFilterSubjectName] = useState('');
    const [isAddStudentModalOpen, setIsAddStudentModalOpen] = useState(false);
    const [selectedStudents, setSelectedStudents] = useState<number[]>([]);
    const [filterStudentClass, setFilterStudentClass] = useState('');
    const [isAddTeacherModalOpen, setIsAddTeacherModalOpen] = useState(false);
    const [addTeacherUserId, setAddTeacherUserId] = useState('');
    const [addTeacherRole, setAddTeacherRole] = useState<'class_teacher' | 'subject_teacher'>('subject_teacher');
    const [addTeacherSubjectId, setAddTeacherSubjectId] = useState('');
    const [studentPageIndex, setStudentPageIndex] = useState(0);
    const STUDENTS_PER_PAGE = 10;

    const { data, setData, post, put, reset, processing, errors } = useForm({
        group_name: '',
        stream: '',
        description: '',
        is_active: true,
    });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.subject-groups.store'), {
            onSuccess: () => {
                reset();
                setIsCreateModalOpen(false);
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedGroup) return;
        put(route('principal.subject-groups.update', selectedGroup.id), {
            onSuccess: () => {
                reset();
                setIsEditModalOpen(false);
                setSelectedGroup(null);
            },
        });
    };

    const handleEdit = (group: SubjectGroupType) => {
        setSelectedGroup(group);
        setData({
            group_name: group.group_name,
            stream: group.stream || '',
            description: group.description || '',
            is_active: group.is_active,
        });
        setIsEditModalOpen(true);
    };

    const handleDelete = (group: SubjectGroupType) => {
        if (confirm(`Delete "${group.name}" group? This will not affect existing students.`)) {
            router.delete(route('principal.subject-groups.destroy', group.id));
        }
    };

    const handleAddSubject = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedGroup || !addSubjectSelect) return;

        router.post(route('principal.subject-groups.add-subject', selectedGroup.id), {
            subject_id: addSubjectSelect,
            subject_type: addSubjectType,
        }, {
            preserveScroll: true,
            only: ['groups'],
            onSuccess: (page) => {
                setAddSubjectSelect('');
                setAddSubjectType('compulsory');
                // Update selected group with fresh data from response
                if (page.props.groups) {
                    const updatedGroup = page.props.groups.find((g: any) => g.id === selectedGroup.id);
                    if (updatedGroup) {
                        setSelectedGroup(updatedGroup);
                    }
                }
            },
        });
    };

    const handleRemoveSubject = (subjectId: number) => {
        if (!selectedGroup) return;
        if (confirm('Remove this subject from the group?')) {
            router.delete(
                route('principal.subject-groups.remove-subject', [selectedGroup.id, subjectId])
            );
        }
    };

    const handleAddStudent = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedGroup || selectedStudents.length === 0) return;

        // Add all selected students one by one
        let completed = 0;
        selectedStudents.forEach((studentId, index) => {
            router.post(route('principal.subject-groups.add-student', selectedGroup.id), {
                student_id: studentId,
            }, {
                preserveScroll: true,
                only: ['groups', 'allStudents'],
                onSuccess: (page) => {
                    completed++;
                    // Update selected group after each addition
                    if (page.props.groups) {
                        const updatedGroup = page.props.groups.find((g: any) => g.id === selectedGroup.id);
                        if (updatedGroup) {
                            setSelectedGroup(updatedGroup);
                        }
                    }
                    // Close modal and reset after all students are added
                    if (completed === selectedStudents.length) {
                        setSelectedStudents([]);
                        setIsAddStudentModalOpen(false);
                    }
                },
                onError: () => {
                    alert(`Error adding student. Please try again.`);
                },
            });
        });
    };

    const handleRemoveStudent = (studentId: number) => {
        if (!selectedGroup) return;
        if (confirm('Remove this student from the group?')) {
            router.delete(
                route('principal.subject-groups.remove-student', [selectedGroup.id, studentId]),
                {
                    preserveScroll: true,
                    only: ['groups', 'allStudents'],
                    onSuccess: (page) => {
                        // Update selected group with fresh data from response
                        if (page.props.groups) {
                            const updatedGroup = page.props.groups.find((g: any) => g.id === selectedGroup.id);
                            if (updatedGroup) {
                                setSelectedGroup(updatedGroup);
                            }
                        }
                    },
                    onError: () => {
                        alert('Error removing student. Please try again.');
                    },
                }
            );
        }
    };

    const handleAddTeacher = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedGroup || !addTeacherUserId) return;

        router.post(route('principal.subject-groups.add-teacher', selectedGroup.id), {
            user_id: addTeacherUserId,
            role: addTeacherRole,
            subject_id: addTeacherSubjectId || null,
        }, {
            preserveScroll: true,
            only: ['groups'],
            onSuccess: (page) => {
                setAddTeacherUserId('');
                setAddTeacherRole('subject_teacher');
                setAddTeacherSubjectId('');
                setIsAddTeacherModalOpen(false);
                if (page.props.groups) {
                    const updatedGroup = page.props.groups.find((g: any) => g.id === selectedGroup.id);
                    if (updatedGroup) {
                        setSelectedGroup(updatedGroup);
                    }
                }
            },
            onError: () => {
                alert('Error adding teacher. Please try again.');
            },
        });
    };

    const handleRemoveTeacher = (teacherId: number) => {
        if (!selectedGroup) return;
        if (confirm('Remove this teacher from the group?')) {
            router.delete(
                route('principal.subject-groups.remove-teacher', [selectedGroup.id, teacherId]),
                {
                    preserveScroll: true,
                    only: ['groups'],
                    onSuccess: (page) => {
                        if (page.props.groups) {
                            const updatedGroup = page.props.groups.find((g: any) => g.id === selectedGroup.id);
                            if (updatedGroup) {
                                setSelectedGroup(updatedGroup);
                            }
                        }
                    },
                    onError: () => {
                        alert('Error removing teacher. Please try again.');
                    },
                }
            );
        }
    };

    const availableSubjects = allSubjects.filter(
        (s) => !selectedGroup?.subjects.some((gs) => gs.id === s.id) &&
               s.subject_name.toLowerCase().includes(filterSubjectName.toLowerCase())
    );

    const availableStudents = allStudents.filter(
        (s) => !selectedGroup?.students?.some((gs) => gs.id === s.id) &&
               (!filterStudentClass || s.class === filterStudentClass)
    );

    const classWiseStudents = selectedGroup?.students?.reduce((acc, student) => {
        const className = student.class;
        if (!acc[className]) {
            acc[className] = 0;
        }
        acc[className]++;
        return acc;
    }, {} as Record<string, number>) || {};

    // Pagination for students
    const filteredStudentsList = selectedGroup?.students?.filter(student =>
        !filterStudentClass || student.class === filterStudentClass
    ) || [];
    const totalStudentPages = Math.ceil(filteredStudentsList.length / STUDENTS_PER_PAGE);
    const paginatedStudents = filteredStudentsList.slice(
        studentPageIndex * STUDENTS_PER_PAGE,
        (studentPageIndex + 1) * STUDENTS_PER_PAGE
    );

    // Show all groups in sidebar
    const filteredGroups = groups;

    // Group by education level
    const groupedByLevel = filteredGroups.reduce((acc, group) => {
        const level = group.education_level || 'Other';
        if (!acc[level]) {
            acc[level] = [];
        }
        acc[level].push(group);
        return acc;
    }, {} as Record<string, SubjectGroupType[]>);

    // Ordered levels
    const levelOrder = ['SSC', 'HSSC', 'Other'];
    const orderedLevels = levelOrder.filter(level => groupedByLevel[level]);

    return (
        <AppLayout title="Subject Groups">
            <Head title="Subject Groups" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Subject Groups</h1>
                    <p className="page-subtitle">SSC (Classes 9-10) and HSSC (Classes 11-12) subject group management</p>
                </div>
                <button
                    onClick={() => {
                        reset();
                        setIsCreateModalOpen(true);
                    }}
                    className="btn-primary"
                >
                    <PlusIcon className="w-4 h-4" /> New Group
                </button>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Groups List */}
                <div className="lg:col-span-1">
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">All Groups</p>
                            <span className="badge badge-blue">{filteredGroups.length}</span>
                        </div>
                        <div className="space-y-6">
                            {filteredGroups.length === 0 ? (
                                <div className="p-4 text-center text-gray-500 text-sm">
                                    {filterGroupClass ? 'No groups found for this class' : 'No subject groups created yet'}
                                </div>
                            ) : (
                                orderedLevels.map((level) => (
                                    <div key={level} className="border-l-4" style={{
                                        borderLeftColor: level === 'SSC' ? '#3b82f6' : level === 'HSSC' ? '#a855f7' : '#6b7280'
                                    }}>
                                        <div className={`px-3 py-2.5 rounded-lg sticky top-0 ${
                                            level === 'SSC' ? 'bg-blue-50' : level === 'HSSC' ? 'bg-purple-50' : 'bg-gray-50'
                                        }`}>
                                            <p className={`text-sm font-bold ${
                                                level === 'SSC' ? 'text-blue-900' : level === 'HSSC' ? 'text-purple-900' : 'text-gray-900'
                                            }`}>{level} Groups</p>
                                        </div>
                                        <div className="space-y-2 mt-3 pl-2">
                                            {groupedByLevel[level]?.map((group) => (
                                                <button
                                                    key={group.id}
                                                    onClick={async () => {
                                                        // Fetch students for this group
                                                        try {
                                                            const response = await fetch(route('principal.subject-groups.show', group.id));
                                                            const groupData = await response.json();
                                                            setSelectedGroup(groupData);
                                                        } catch (error) {
                                                            console.error('Failed to fetch group data:', error);
                                                            setSelectedGroup(group);
                                                        }
                                                        setFilterSubjectName('');
                                                        setAddSubjectSelect('');
                                                        setFilterStudentClass('');
                                                        setStudentPageIndex(0);
                                                    }}
                                                    className={`w-full text-left p-3 rounded-lg border-2 transition-all ${
                                                        selectedGroup?.id === group.id
                                                            ? 'border-indigo-500 bg-indigo-50'
                                                            : 'border-gray-200 hover:border-gray-300'
                                                    }`}
                                                >
                                                    <div className="flex items-center justify-between">
                                                        <div className="font-semibold text-gray-900">{group.name}</div>
                                                    </div>
                                                    {group.stream && (
                                                        <div className="text-xs text-gray-500 mt-1">{group.stream}</div>
                                                    )}
                                                    <div className="flex gap-2 mt-2 text-xs">
                                                        <Badge color="blue">{group.subject_count} subjects</Badge>
                                                        <Badge color="green">{group.student_count} students</Badge>
                                                    </div>
                                                    {group.student_count > 0 && group.students && group.students.length > 0 && (
                                                        <div className="mt-2 pt-2 border-t border-gray-200">
                                                            <div className="flex flex-wrap gap-1">
                                                                {Array.from(new Set(group.students.map(s => s.class))).sort().map((className) => {
                                                                    const count = group.students?.filter(s => s.class === className).length || 0;
                                                                    return (
                                                                        <span key={className} className="text-xs bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded">
                                                                            {className}: {count}
                                                                        </span>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>

                {/* Group Details */}
                <div className="lg:col-span-2">
                    {selectedGroup ? (
                        <div className="card">
                            <div className="card-header">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <p className="card-title">{selectedGroup.name}</p>
                                        {selectedGroup.education_level && (
                                            <span className={`px-3 py-1 rounded-full text-sm font-semibold ${
                                                selectedGroup.education_level === 'SSC'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-purple-100 text-purple-800'
                                            }`}>
                                                {selectedGroup.education_level}
                                            </span>
                                        )}
                                    </div>
                                    {selectedGroup.description && (
                                        <p className="text-sm text-gray-500 mt-1">{selectedGroup.description}</p>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => handleEdit(selectedGroup)}
                                        className="btn-secondary btn-sm"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => handleDelete(selectedGroup)}
                                        className="btn-ghost btn-sm text-red-600 hover:text-red-700"
                                    >
                                        <TrashIcon className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {/* Subjects */}
                            <div>
                                <h3 className="px-5 py-3 text-sm font-semibold text-gray-900 border-b">
                                    Subjects ({selectedGroup.subjects.length})
                                </h3>
                                <div className="divide-y divide-gray-50">
                                    {selectedGroup.subjects.length === 0 ? (
                                        <div className="p-5 text-center text-gray-500 text-sm">
                                            No subjects added yet
                                        </div>
                                    ) : (
                                        <>
                                            {['compulsory', 'optional', 'major'].map((type) => {
                                                const subjectsOfType = selectedGroup.subjects.filter(s => s.subject_type === type);
                                                if (subjectsOfType.length === 0) return null;

                                                const typeLabel = type === 'compulsory' ? '📘 Compulsory' : type === 'optional' ? '💜 Optional' : '⭐ Major';
                                                const typeColor = type === 'compulsory' ? 'bg-blue-50 border-blue-200' : type === 'optional' ? 'bg-purple-50 border-purple-200' : 'bg-amber-50 border-amber-200';

                                                return (
                                                    <div key={type} className={`${typeColor} border-b`}>
                                                        <div className="px-5 py-2 bg-gray-100 sticky">
                                                            <p className="text-xs font-bold text-gray-700">{typeLabel}</p>
                                                        </div>
                                                        {subjectsOfType.map((subject) => (
                                                            <div
                                                                key={subject.id}
                                                                className="flex items-center justify-between px-5 py-3 hover:bg-white group transition"
                                                            >
                                                                <div className="flex-1">
                                                                    <p className="font-medium text-gray-900">
                                                                        {subject.subject_name}
                                                                    </p>
                                                                </div>
                                                                <div className="flex items-center gap-3">
                                                                    <select
                                                                        value={subject.subject_type}
                                                                        onChange={(e) => {
                                                                            const newType = e.target.value;
                                                                            // Update local state immediately
                                                                            if (selectedGroup) {
                                                                                setSelectedGroup({
                                                                                    ...selectedGroup,
                                                                                    subjects: selectedGroup.subjects.map(s =>
                                                                                        s.id === subject.id ? { ...s, subject_type: newType } : s
                                                                                    )
                                                                                });
                                                                            }
                                                                            // Send update to server
                                                                            router.put(
                                                                                route('principal.subject-groups.update-subject-type', [selectedGroup.id, subject.id]),
                                                                                { subject_type: newType },
                                                                                { preserveScroll: true }
                                                                            );
                                                                        }}
                                                                        className={`text-xs py-1.5 px-2.5 rounded font-semibold border-2 cursor-pointer transition focus:outline-none ${
                                                                            subject.subject_type === 'compulsory'
                                                                                ? 'bg-blue-50 border-blue-400 text-blue-800'
                                                                                : subject.subject_type === 'optional'
                                                                                ? 'bg-purple-50 border-purple-400 text-purple-800'
                                                                                : 'bg-amber-50 border-amber-400 text-amber-800'
                                                        }`}
                                                                    >
                                                                        <option value="compulsory">📘 Compulsory</option>
                                                                        <option value="optional">💜 Optional</option>
                                                                        <option value="major">⭐ Major</option>
                                                                    </select>
                                                                    <button
                                                                        onClick={() => handleRemoveSubject(subject.id)}
                                                                        className="text-red-500 hover:text-red-700 transition opacity-0 group-hover:opacity-100"
                                                                        title="Remove subject"
                                                                    >
                                                                        <XMarkIcon className="w-4 h-4" />
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                );
                                            })}
                                        </>
                                    )}
                                </div>
                            </div>

                            {/* Add Subject */}
                            {allSubjects.some((s) => !selectedGroup?.subjects.some((gs) => gs.id === s.id)) && (
                                <form onSubmit={handleAddSubject} className="border-t p-5 space-y-3">
                                    <label className="form-label">Add Subject</label>
                                    <input
                                        type="text"
                                        placeholder="Search subjects..."
                                        value={filterSubjectName}
                                        onChange={(e) => setFilterSubjectName(e.target.value)}
                                        className="form-input w-full"
                                    />
                                    <div className="flex gap-2">
                                        <select
                                            value={addSubjectSelect}
                                            onChange={(e) => setAddSubjectSelect(e.target.value)}
                                            className="form-select flex-1"
                                        >
                                            <option value="">
                                                {availableSubjects.length === 0
                                                    ? 'No subjects available'
                                                    : 'Select subject...'}
                                            </option>
                                            {availableSubjects.map((subject) => (
                                                <option key={subject.id} value={subject.id}>
                                                    {subject.subject_name}
                                                </option>
                                            ))}
                                        </select>
                                        <select
                                            value={addSubjectType}
                                            onChange={(e) => setAddSubjectType(e.target.value as 'compulsory' | 'optional' | 'major')}
                                            className={`form-select border-2 font-semibold ${
                                                addSubjectType === 'compulsory'
                                                    ? 'bg-blue-50 border-blue-400 text-blue-800'
                                                    : addSubjectType === 'optional'
                                                    ? 'bg-purple-50 border-purple-400 text-purple-800'
                                                    : 'bg-amber-50 border-amber-400 text-amber-800'
                                            }`}
                                        >
                                            <option value="compulsory">📘 Compulsory</option>
                                            <option value="optional">💜 Optional</option>
                                            <option value="major">⭐ Major</option>
                                        </select>
                                        <button
                                            type="submit"
                                            disabled={!addSubjectSelect || processing}
                                            className="btn-primary btn-sm"
                                        >
                                            Add
                                        </button>
                                    </div>
                                </form>
                            )}

                            {/* Students */}
                            <div className="border-t">
                                <div className="px-5 py-4 border-b bg-gray-50">
                                    <h3 className="text-sm font-semibold text-gray-900 mb-3">
                                        Students ({selectedGroup.students?.length || 0})
                                    </h3>
                                    {selectedGroup.students && selectedGroup.students.length > 0 && (
                                        <>
                                            <div className="mb-3">
                                                <label className="text-xs font-medium text-gray-700 block mb-1">Filter by Class</label>
                                                <select
                                                    value={filterStudentClass}
                                                    onChange={(e) => {
                                                        setFilterStudentClass(e.target.value);
                                                        setStudentPageIndex(0);
                                                    }}
                                                    className="form-select text-sm"
                                                >
                                                    <option value="">All Classes ({selectedGroup.students.length})</option>
                                                    {Array.from(new Set(selectedGroup.students.map(s => s.class))).sort().map(className => {
                                                        const count = selectedGroup.students?.filter(s => s.class === className).length || 0;
                                                        return (
                                                            <option key={className} value={className}>
                                                                {className} ({count})
                                                            </option>
                                                        );
                                                    })}
                                                </select>
                                            </div>
                                            <div className="flex flex-wrap gap-2">
                                                {Object.entries(classWiseStudents).map(([className, count]) => (
                                                    <span key={className} className="text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded">
                                                        {className}: <span className="font-semibold">{count}</span>
                                                    </span>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-gray-200 bg-gray-50">
                                            <th className="text-left px-5 py-3 font-semibold text-gray-900">Student Name</th>
                                            <th className="text-left px-5 py-3 font-semibold text-gray-900">Admission No</th>
                                            <th className="text-left px-5 py-3 font-semibold text-gray-900">Class & Section</th>
                                            <th className="text-left px-5 py-3 font-semibold text-gray-900">Stream</th>
                                            <th className="text-center px-5 py-3 font-semibold text-gray-900 w-10">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {!selectedGroup.students || selectedGroup.students.length === 0 ? (
                                            <tr>
                                                <td colSpan={5} className="text-center py-8 text-gray-500 text-sm">
                                                    No students assigned yet
                                                </td>
                                            </tr>
                                        ) : paginatedStudents.length === 0 ? (
                                            <tr>
                                                <td colSpan={5} className="text-center py-8 text-gray-500 text-sm">
                                                    No students in this filter
                                                </td>
                                            </tr>
                                        ) : (
                                            paginatedStudents.map((student) => (
                                                <tr key={student.id} className="border-b border-gray-100 hover:bg-gray-50">
                                                    <td className="px-5 py-3 text-gray-900 font-medium">{student.full_name}</td>
                                                    <td className="px-5 py-3 text-gray-600 font-mono text-xs">{student.admission_no}</td>
                                                    <td className="px-5 py-3">
                                                        <span className="inline-block bg-blue-100 text-blue-700 px-2.5 py-1 rounded text-xs font-semibold">
                                                            {student.class}{student.section ? `-${student.section}` : ''}
                                                        </span>
                                                    </td>
                                                    <td className="px-5 py-3">
                                                        <select
                                                            value={student.stream || ''}
                                                            onChange={(e) => {
                                                                const newStream = e.target.value || null;
                                                                // Update local state
                                                                if (selectedGroup) {
                                                                    setSelectedGroup({
                                                                        ...selectedGroup,
                                                                        students: selectedGroup.students.map(s =>
                                                                            s.id === student.id ? { ...s, stream: newStream } : s
                                                                        )
                                                                    });
                                                                }
                                                                // Send update to server
                                                                fetch(route('principal.students.update-stream', student.id), {
                                                                    method: 'PUT',
                                                                    headers: {
                                                                        'Content-Type': 'application/json',
                                                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                                                    },
                                                                    body: JSON.stringify({ stream: newStream }),
                                                                }).catch(error => console.error('Failed to update stream:', error));
                                                            }}
                                                            className="form-select text-xs py-1 px-2 border border-gray-300 rounded"
                                                        >
                                                            <option value="">No Stream</option>
                                                            <option value="ICS">ICS</option>
                                                            <option value="Pre-Medical">Pre-Medical</option>
                                                        </select>
                                                    </td>
                                                    <td className="px-5 py-3 text-center">
                                                        <button
                                                            onClick={() => handleRemoveStudent(student.id)}
                                                            className="text-red-500 hover:text-red-700 transition"
                                                            title="Remove student"
                                                        >
                                                            <XMarkIcon className="w-4 h-4" />
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>

                                {/* Pagination */}
                                {totalStudentPages > 1 && (
                                    <div className="flex items-center justify-between px-5 py-4 border-t border-gray-200 bg-gray-50">
                                        <div className="text-xs text-gray-600">
                                            Showing {paginatedStudents.length > 0 ? studentPageIndex * STUDENTS_PER_PAGE + 1 : 0} - {Math.min((studentPageIndex + 1) * STUDENTS_PER_PAGE, filteredStudentsList.length)} of {filteredStudentsList.length} students
                                        </div>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => setStudentPageIndex(Math.max(0, studentPageIndex - 1))}
                                                disabled={studentPageIndex === 0}
                                                className="px-3 py-1.5 text-xs border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Previous
                                            </button>
                                            <div className="flex items-center gap-1">
                                                {Array.from({ length: totalStudentPages }, (_, i) => (
                                                    <button
                                                        key={i}
                                                        onClick={() => setStudentPageIndex(i)}
                                                        className={`px-2 py-1.5 text-xs rounded ${
                                                            studentPageIndex === i
                                                                ? 'bg-indigo-600 text-white'
                                                                : 'border border-gray-300 hover:bg-gray-100'
                                                        }`}
                                                    >
                                                        {i + 1}
                                                    </button>
                                                ))}
                                            </div>
                                            <button
                                                onClick={() => setStudentPageIndex(Math.min(totalStudentPages - 1, studentPageIndex + 1))}
                                                disabled={studentPageIndex === totalStudentPages - 1}
                                                className="px-3 py-1.5 text-xs border border-gray-300 rounded hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                Next
                                            </button>
                                        </div>
                                    </div>
                                )}
                            </div>

                            {/* Add Student Button */}
                            {allStudents.some(s => !selectedGroup?.students?.some(gs => gs.id === s.id)) && (
                                <div className="border-t p-5">
                                    <button
                                        onClick={() => {
                                            setSelectedStudents([]);
                                            setFilterStudentClass('');
                                            setIsAddStudentModalOpen(true);
                                        }}
                                        className="btn-secondary w-full text-sm"
                                    >
                                        <PlusIcon className="w-4 h-4" /> Add Students
                                    </button>
                                </div>
                            )}

                            {/* Teachers Section */}
                            <div className="border-t">
                                <div className="px-5 py-3 border-b bg-gray-50">
                                    <h3 className="text-sm font-semibold text-gray-900">
                                        Teachers ({selectedGroup.teachers?.length || 0})
                                    </h3>
                                </div>
                                <div className="divide-y divide-gray-50">
                                    {!selectedGroup.teachers || selectedGroup.teachers.length === 0 ? (
                                        <div className="p-5 text-center text-gray-500 text-sm">
                                            No teachers assigned yet
                                        </div>
                                    ) : (
                                        selectedGroup.teachers.map((teacher) => (
                                            <div
                                                key={teacher.id}
                                                className="flex items-center justify-between px-5 py-3 hover:bg-gray-50 group"
                                            >
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <p className="font-medium text-gray-900">{teacher.teacher_name}</p>
                                                        <span className={`text-xs px-2 py-0.5 rounded font-semibold ${
                                                            teacher.role === 'class_teacher'
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-blue-100 text-blue-800'
                                                        }`}>
                                                            {teacher.role === 'class_teacher' ? '👨‍🎓 Class Teacher' : '📚 Subject Teacher'}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-gray-500 mt-1">
                                                        {teacher.teacher_email}
                                                        {teacher.subject_name && ` • ${teacher.subject_name}`}
                                                    </p>
                                                </div>
                                                <button
                                                    onClick={() => handleRemoveTeacher(teacher.id)}
                                                    className="text-red-500 hover:text-red-700 transition opacity-0 group-hover:opacity-100"
                                                    title="Remove teacher"
                                                >
                                                    <XMarkIcon className="w-4 h-4" />
                                                </button>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>

                            {/* Add Teacher Button */}
                            {allTeachers.some(t => !selectedGroup?.teachers?.some(gt => gt.user_id === t.id)) && (
                                <div className="border-t p-5">
                                    <button
                                        onClick={() => setIsAddTeacherModalOpen(true)}
                                        className="btn-secondary w-full text-sm"
                                    >
                                        <PlusIcon className="w-4 h-4" /> Assign Teacher
                                    </button>
                                </div>
                            )}
                        </div>
                    ) : (
                        <div className="card empty-state py-16">
                            <p className="empty-state-text">Select a group to manage its subjects</p>
                        </div>
                    )}
                </div>
            </div>

            {/* Create Modal */}
            <Modal
                isOpen={isCreateModalOpen}
                onClose={() => setIsCreateModalOpen(false)}
                title="Create Subject Group"
            >
                <form onSubmit={handleCreateSubmit} className="space-y-4">
                    <div>
                        <label className="form-label">Group Name *</label>
                        <input
                            type="text"
                            value={data.group_name}
                            onChange={(e) => setData('group_name', e.target.value)}
                            placeholder="e.g., Pre-Medical"
                            className="form-input"
                            required
                        />
                        {errors.group_name && (
                            <p className="text-red-500 text-xs mt-1">{errors.group_name}</p>
                        )}
                    </div>

                    <div>
                        <label className="form-label">Stream Label</label>
                        <input
                            type="text"
                            value={data.stream}
                            onChange={(e) => setData('stream', e.target.value)}
                            placeholder="e.g., Science, Arts"
                            className="form-input"
                        />
                    </div>

                    <div>
                        <label className="form-label">Description</label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Optional description"
                            className="form-textarea"
                            rows={3}
                        />
                    </div>

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setIsCreateModalOpen(false)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Creating...' : 'Create Group'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal
                isOpen={isEditModalOpen}
                onClose={() => setIsEditModalOpen(false)}
                title="Edit Subject Group"
            >
                <form onSubmit={handleEditSubmit} className="space-y-4">
                    <div>
                        <label className="form-label">Group Name *</label>
                        <input
                            type="text"
                            value={data.group_name}
                            onChange={(e) => setData('group_name', e.target.value)}
                            className="form-input"
                            required
                        />
                        {errors.group_name && (
                            <p className="text-red-500 text-xs mt-1">{errors.group_name}</p>
                        )}
                    </div>

                    <div>
                        <label className="form-label">Stream Label</label>
                        <input
                            type="text"
                            value={data.stream}
                            onChange={(e) => setData('stream', e.target.value)}
                            className="form-input"
                        />
                    </div>

                    <div>
                        <label className="form-label">Description</label>
                        <textarea
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="form-textarea"
                            rows={3}
                        />
                    </div>

                    <div>
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={data.is_active}
                                onChange={(e) => setData('is_active', e.target.checked)}
                            />
                            <span>Active</span>
                        </label>
                    </div>

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setIsEditModalOpen(false)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button type="submit" disabled={processing} className="btn-primary">
                            {processing ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Add Students Modal */}
            <Modal
                isOpen={isAddStudentModalOpen}
                onClose={() => setIsAddStudentModalOpen(false)}
                title="Add Students to Group"
            >
                <form onSubmit={handleAddStudent} className="space-y-4">
                    <div>
                        <label className="form-label">Filter by Class</label>
                        <select
                            value={filterStudentClass}
                            onChange={(e) => {
                                setFilterStudentClass(e.target.value);
                                setStudentPageIndex(0);
                            }}
                            className="form-select"
                        >
                            <option value="">All Classes</option>
                            {Array.from(new Set(allStudents.map(s => s.class)))
                                .sort((a, b) => {
                                    // Sort numerically if they start with numbers
                                    const numA = parseInt(a?.toString().split('-')[0] || '0');
                                    const numB = parseInt(b?.toString().split('-')[0] || '0');
                                    return numA - numB || a.localeCompare(b);
                                })
                                .map((className) => {
                                    const count = allStudents.filter(s => s.class === className).length;
                                    return (
                                        <option key={className} value={className}>
                                            {className} ({count} students)
                                        </option>
                                    );
                                })}
                        </select>
                    </div>

                    <div>
                        <label className="form-label">Select Students * ({selectedStudents.length} selected)</label>
                        <div className="border border-gray-300 rounded-lg overflow-y-auto max-h-96 p-3 space-y-2 bg-white">
                            {availableStudents.length === 0 ? (
                                <p className="text-gray-500 text-sm text-center py-4">No students available</p>
                            ) : (
                                availableStudents.map((student) => {
                                    // Extract section from class (e.g., "9-A" -> "A")
                                    const sectionMatch = student.class?.match(/[A-Z]$/);
                                    const section = sectionMatch ? sectionMatch[0] : null;

                                    return (
                                        <label key={student.id} className="flex items-center gap-3 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={selectedStudents.includes(student.id)}
                                                onChange={(e) => {
                                                    if (e.target.checked) {
                                                        setSelectedStudents([...selectedStudents, student.id]);
                                                    } else {
                                                        setSelectedStudents(selectedStudents.filter(id => id !== student.id));
                                                    }
                                                }}
                                                className="rounded"
                                            />
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="text-sm font-medium text-gray-900">{student.full_name}</p>
                                                    {section && (
                                                        <span className="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-xs font-semibold">
                                                            {section}
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-gray-500">{student.admission_no} — {student.class}</p>
                                            </div>
                                        </label>
                                    );
                                })
                            )}
                        </div>
                    </div>

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setIsAddStudentModalOpen(false)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button type="submit" disabled={selectedStudents.length === 0 || processing} className="btn-primary">
                            {processing ? 'Adding...' : `Add ${selectedStudents.length} Student${selectedStudents.length !== 1 ? 's' : ''}`}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Add Teacher Modal */}
            <Modal
                isOpen={isAddTeacherModalOpen}
                onClose={() => setIsAddTeacherModalOpen(false)}
                title="Assign Teacher to Group"
            >
                <form onSubmit={handleAddTeacher} className="space-y-4">
                    <div>
                        <label className="form-label">Select Teacher *</label>
                        <select
                            value={addTeacherUserId}
                            onChange={(e) => setAddTeacherUserId(e.target.value)}
                            className="form-select"
                            required
                        >
                            <option value="">Choose a teacher...</option>
                            {allTeachers
                                .filter(t => !selectedGroup?.teachers?.some(gt => gt.user_id === t.id))
                                .map((teacher) => (
                                    <option key={teacher.id} value={teacher.id}>
                                        {teacher.name} ({teacher.email})
                                    </option>
                                ))}
                        </select>
                    </div>

                    <div>
                        <label className="form-label">Role *</label>
                        <select
                            value={addTeacherRole}
                            onChange={(e) => setAddTeacherRole(e.target.value as 'class_teacher' | 'subject_teacher')}
                            className="form-select"
                        >
                            <option value="subject_teacher">Subject Teacher</option>
                            <option value="class_teacher">Class Teacher</option>
                        </select>
                        <p className="text-xs text-gray-500 mt-1">
                            {addTeacherRole === 'class_teacher'
                                ? 'Class Teachers oversee the entire group'
                                : 'Subject Teachers teach specific subjects'}
                        </p>
                    </div>

                    {addTeacherRole === 'subject_teacher' && (
                        <div>
                            <label className="form-label">Subject (Optional)</label>
                            <select
                                value={addTeacherSubjectId}
                                onChange={(e) => setAddTeacherSubjectId(e.target.value)}
                                className="form-select"
                            >
                                <option value="">Teaching all subjects</option>
                                {selectedGroup?.subjects.map((subject) => (
                                    <option key={subject.id} value={subject.id}>
                                        {subject.subject_name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    )}

                    <div className="flex gap-2 justify-end pt-4 border-t">
                        <button
                            type="button"
                            onClick={() => setIsAddTeacherModalOpen(false)}
                            className="btn-ghost"
                        >
                            Cancel
                        </button>
                        <button type="submit" disabled={!addTeacherUserId || processing} className="btn-primary">
                            {processing ? 'Assigning...' : 'Assign Teacher'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
