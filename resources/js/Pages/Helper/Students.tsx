import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import { FunnelIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import SearchInput from '@/Components/SearchInput';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import type { PageProps, Student, SchoolClass, SubjectGroup, PaginatedData } from '@/types';

interface Props extends PageProps {
    students: PaginatedData<Student>;
    classes: SchoolClass[];
    groups: SubjectGroup[];
    filters: { search?: string; class_id?: string };
}

export default function HelperStudents({ students, classes, groups, filters }: Props) {
    const [assignTarget, setAssignTarget] = useState<Student | null>(null);
    const { data, setData, post, processing } = useForm({ subject_group_id: '' });

    const handleClassFilter = (classId: string) => {
        router.get(route('helper.students'), { ...filters, class_id: classId }, {
            preserveState: true, replace: true,
        });
    };

    const openAssign = (s: Student) => {
        setData('subject_group_id', String(s.subject_group_id ?? ''));
        setAssignTarget(s);
    };

    const handleAssign = (e: React.FormEvent) => {
        e.preventDefault();
        if (!assignTarget) return;
        post(route('helper.students.assign-group', assignTarget.id), {
            onSuccess: () => setAssignTarget(null),
        });
    };

    return (
        <AppLayout title="Students">
            <Head title="Students" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Students</h1>
                    <p className="page-subtitle">{students.total} active students</p>
                </div>
            </div>

            <div className="card">
                <div className="card-header gap-3 flex-wrap">
                    <SearchInput value={filters.search} placeholder="Search name or admission no…" />
                    <div className="flex items-center gap-2">
                        <FunnelIcon className="w-4 h-4 text-gray-400" />
                        <select className="form-select !py-2 !text-xs w-44" value={filters.class_id ?? ''}
                                onChange={e => handleClassFilter(e.target.value)}>
                            <option value="">All Classes</option>
                            {classes.map(c => (
                                <option key={c.id} value={c.id}>
                                    {c.class}{c.section ? ` — ${c.section}` : ''}
                                </option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No.</th>
                                <th>Class</th>
                                <th>Subject Group</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {students.data.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="text-center py-12 text-gray-400">No students found</td>
                                </tr>
                            ) : students.data.map(s => (
                                <tr key={s.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-sm font-bold text-indigo-600">
                                                {s.full_name.charAt(0).toUpperCase()}
                                            </div>
                                            <span className="font-semibold text-gray-900">{s.full_name}</span>
                                        </div>
                                    </td>
                                    <td className="font-mono text-sm">{s.admission_no}</td>
                                    <td>{s.class?.name}{s.class?.section ? ` — ${s.class.section}` : ''}</td>
                                    <td>
                                        {s.subject_group ? (
                                            <Badge color="indigo">{s.subject_group.name}</Badge>
                                        ) : (
                                            <span className="text-gray-400 text-sm">Unassigned</span>
                                        )}
                                    </td>
                                    <td>
                                        <button onClick={() => openAssign(s)} className="btn-ghost btn-sm">
                                            <UserGroupIcon className="w-4 h-4" /> Assign Group
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <p className="text-sm text-gray-500">Showing {students.from ?? 0}–{students.to ?? 0} of {students.total}</p>
                    <Pagination data={students.links} />
                </div>
            </div>

            {/* Assign Group Modal */}
            <Modal isOpen={!!assignTarget} onClose={() => setAssignTarget(null)} title="Assign Subject Group" size="sm">
                {assignTarget && (
                    <form onSubmit={handleAssign} className="space-y-4">
                        <p className="text-sm text-gray-600">
                            Assigning group for <span className="font-semibold">{assignTarget.name}</span>
                        </p>
                        <div className="form-group">
                            <label className="form-label">Subject Group</label>
                            <select className="form-select" value={data.subject_group_id}
                                    onChange={e => setData('subject_group_id', e.target.value)}>
                                <option value="">None (remove assignment)</option>
                                {groups.map(g => (
                                    <option key={g.id} value={g.id}>{g.name} — {g.stream}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex gap-2 justify-end pt-2">
                            <button type="button" onClick={() => setAssignTarget(null)} className="btn-secondary">Cancel</button>
                            <button type="submit" disabled={processing} className="btn-primary">
                                {processing ? 'Saving…' : 'Assign'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>
        </AppLayout>
    );
}
