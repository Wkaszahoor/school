import React, { useState, useMemo } from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { ArrowLeftIcon, PencilSquareIcon, BookOpenIcon, AcademicCapIcon, TrashIcon, MagnifyingGlassIcon, XCircleIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import TeacherReportsCard from '@/Components/TeacherReportsCard';
import TeacherDevicesCard from '@/Components/TeacherDevicesCard';
import type { PageProps, TeacherProfile, TeacherReport, TeacherDevice } from '@/types';

interface Props extends PageProps {
    teacher: TeacherProfile;
    reportsReceived: TeacherReport[];
    devices: TeacherDevice[];
}

export default function ShowTeacher({ teacher, reportsReceived, devices }: Props) {
    const [editing, setEditing] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const { data, setData, put, processing, errors } = useForm({
        name: teacher.user?.name ?? '',
        phone: teacher.phone ?? '',
        qualification: teacher.qualification ?? '',
        specialisation: teacher.specialisation ?? '',
        is_active: teacher.is_active ?? true,
    });

    // Filter data based on search query
    const filteredAssignments = useMemo(() => {
        if (!searchQuery.trim()) return teacher.assignments || [];
        const q = searchQuery.toLowerCase();
        return (teacher.assignments || []).filter(a =>
            a.class?.name?.toLowerCase().includes(q) ||
            a.class?.section?.toLowerCase().includes(q) ||
            a.subject?.name?.toLowerCase().includes(q)
        );
    }, [searchQuery, teacher.assignments]);

    const filteredLessonPlans = useMemo(() => {
        if (!searchQuery.trim()) return teacher.lesson_plans || [];
        const q = searchQuery.toLowerCase();
        return (teacher.lesson_plans || []).filter(lp =>
            lp.topic?.toLowerCase().includes(q) ||
            lp.week_starting?.toLowerCase().includes(q) ||
            lp.status?.toLowerCase().includes(q)
        );
    }, [searchQuery, teacher.lesson_plans]);

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('admin.teachers.update', teacher.id), {
            onSuccess: () => setEditing(false),
        });
    };

    const handleDelete = () => {
        if (confirm(`Are you sure you want to archive ${teacher.user?.name}? They can be restored later.`)) {
            setDeleting(true);
            router.delete(route('admin.teachers.destroy', teacher.id), {
                onError: () => {
                    setDeleting(false);
                    alert('Failed to archive teacher');
                },
            });
        }
    };

    return (
        <AppLayout title={teacher.user?.name ?? 'Teacher'}>
            <Head title={teacher.user?.name ?? 'Teacher'} />

            <div className="max-w-7xl mx-auto">
                <div className="page-header flex-col gap-4 sm:flex-row">
                    <div className="flex items-center gap-3">
                        <Link href={route('admin.teachers.index')} className="btn-ghost btn-icon">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="page-title">{teacher.user?.name}</h1>
                            <p className="page-subtitle">{teacher.employee_id} · {teacher.specialisation}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2 flex-1 sm:justify-center">
                        <div className="relative flex-1 max-w-xs">
                            <input
                                type="text"
                                placeholder="Search assignments, lessons..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="form-input pl-10 pr-9"
                            />
                            <MagnifyingGlassIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                            {searchQuery && (
                                <button
                                    onClick={() => setSearchQuery('')}
                                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                >
                                    <XCircleIcon className="w-4 h-4" />
                                </button>
                            )}
                        </div>
                    </div>
                    <div className="flex gap-2 sm:ml-auto">
                        <button onClick={() => setEditing(!editing)} className="btn-secondary">
                            <PencilSquareIcon className="w-4 h-4" /> {editing ? 'Cancel Edit' : 'Edit'}
                        </button>
                        <button
                            onClick={handleDelete}
                            disabled={deleting}
                            className="btn-secondary text-red-600 hover:bg-red-50"
                        >
                            <TrashIcon className="w-4 h-4" />
                            {deleting ? 'Archiving…' : 'Archive'}
                        </button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                {/* Profile Card */}
                <div className="card">
                    <div className="card-body flex flex-col items-center text-center gap-4">
                        <div className="w-20 h-20 rounded-full bg-indigo-100 flex items-center justify-center text-2xl font-bold text-indigo-600">
                            {teacher.user?.name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <p className="text-lg font-bold text-gray-900">{teacher.user?.name}</p>
                            <p className="text-sm text-gray-500">{teacher.user?.email}</p>
                        </div>
                        <div className="w-full space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-500">Employee ID</span>
                                <span className="font-mono font-medium">{teacher.employee_id}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Phone</span>
                                <span className="font-medium">{teacher.phone || '—'}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Status</span>
                                <Badge color={teacher.is_active ? 'green' : 'gray'}>{teacher.is_active ? 'Active' : 'Inactive'}</Badge>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-500">Joined</span>
                                <span className="font-medium">{teacher.date_joined || '—'}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="lg:col-span-2 space-y-5">
                    {/* Edit Form */}
                    {editing && (
                        <div className="card border-indigo-200">
                            <div className="card-header bg-indigo-50/60">
                                <p className="card-title text-indigo-700">Edit Profile</p>
                            </div>
                            <form onSubmit={handleUpdate}>
                                <div className="card-body space-y-4">
                                    <div className="grid grid-cols-2 gap-4">
                                        <div className="form-group col-span-2">
                                            <label className="form-label">Full Name</label>
                                            <input className="form-input" value={data.name}
                                                   onChange={e => setData('name', e.target.value)} />
                                        </div>
                                        <div className="form-group">
                                            <label className="form-label">Phone</label>
                                            <input className="form-input" value={data.phone}
                                                   onChange={e => setData('phone', e.target.value)} />
                                        </div>
                                        <div className="form-group">
                                            <label className="form-label">Qualification</label>
                                            <input className="form-input" value={data.qualification}
                                                   onChange={e => setData('qualification', e.target.value)} />
                                        </div>
                                        <div className="form-group col-span-2">
                                            <label className="form-label">Specialisation</label>
                                            <input className="form-input" value={data.specialisation}
                                                   onChange={e => setData('specialisation', e.target.value)} />
                                        </div>
                                        <div className="form-group">
                                            <label className="flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox" checked={data.is_active}
                                                       onChange={e => setData('is_active', e.target.checked)}
                                                       className="w-4 h-4 text-indigo-600 rounded" />
                                                <span className="text-sm font-semibold text-gray-700">Active</span>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" disabled={processing} className="btn-primary">
                                        {processing ? 'Saving…' : 'Save Changes'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    )}

                    {/* Class Teacher Assignment */}
                    {teacher.class_teacher_classes && teacher.class_teacher_classes.length > 0 && (
                        <div className="card border-l-4 border-amber-500">
                            <div className="card-header bg-amber-50/60">
                                <p className="card-title text-amber-700">Class Teacher</p>
                            </div>
                            <div className="card-body">
                                <div className="flex flex-wrap gap-2">
                                    {teacher.class_teacher_classes.map((cls, idx) => (
                                        <Badge key={idx} color="amber">{cls}</Badge>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Assignments */}
                    {teacher.assignments && teacher.assignments.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <div className="flex items-center justify-between">
                                    <p className="card-title flex items-center gap-2"><BookOpenIcon className="w-4 h-4" /> Teaching Assignments</p>
                                    {searchQuery && (
                                        <span className="text-sm text-gray-500">{filteredAssignments.length} of {teacher.assignments.length}</span>
                                    )}
                                </div>
                            </div>
                            {filteredAssignments.length > 0 ? (
                                <div className="table-wrapper">
                                    <table className="table">
                                        <thead>
                                            <tr><th>Class</th><th>Subject</th></tr>
                                        </thead>
                                        <tbody>
                                            {filteredAssignments.map(a => (
                                                <tr key={a.id}>
                                                    <td>{a.class?.name}{a.class?.section ? ` — ${a.class.section}` : ''}</td>
                                                    <td>{a.subject?.name}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="card-body text-center text-gray-500">
                                    No assignments match your search
                                </div>
                            )}
                        </div>
                    )}

                    {/* Lesson Plans */}
                    {teacher.lesson_plans && teacher.lesson_plans.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <div className="flex items-center justify-between">
                                    <p className="card-title flex items-center gap-2"><AcademicCapIcon className="w-4 h-4" /> Lesson Plans</p>
                                    {searchQuery && (
                                        <span className="text-sm text-gray-500">{filteredLessonPlans.length} of {teacher.lesson_plans.length}</span>
                                    )}
                                </div>
                            </div>
                            {filteredLessonPlans.length > 0 ? (
                                <div className="table-wrapper">
                                    <table className="table">
                                        <thead>
                                            <tr><th>Topic</th><th>Week</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            {filteredLessonPlans.map(lp => (
                                                <tr key={lp.id}>
                                                    <td className="font-medium">{lp.topic}</td>
                                                    <td>{lp.week_starting}</td>
                                                    <td>
                                                        <Badge color={lp.status === 'approved' ? 'green' : lp.status === 'rejected' ? 'red' : 'yellow'}>
                                                            {lp.status}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="card-body text-center text-gray-500">
                                    No lesson plans match your search
                                </div>
                            )}
                        </div>
                    )}

                    {/* Devices */}
                    <TeacherDevicesCard
                        teacher={teacher.user!}
                        devices={devices}
                        canAssign={true}
                    />

                    {/* Reports Received */}
                    <TeacherReportsCard reports={reportsReceived} />
                </div>
            </div>
            </div>
        </AppLayout>
    );
}
