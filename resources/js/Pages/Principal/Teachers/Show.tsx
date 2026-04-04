import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, MagnifyingGlassIcon, XCircleIcon, BookOpenIcon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Badge from '@/Components/Badge';
import TeacherDevicesCard from '@/Components/TeacherDevicesCard';
import type { PageProps, TeacherProfile, TeacherDevice } from '@/types';

interface Props extends PageProps {
    teacher: TeacherProfile;
    devices: TeacherDevice[];
    subjectAssignments: any[];
}

export default function ShowTeacher({ teacher, devices, subjectAssignments }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [refreshKey, setRefreshKey] = useState(0);
    const [showImageModal, setShowImageModal] = useState(false);

    // Filter assignments based on search query
    const filteredAssignments = useMemo(() => {
        if (!searchQuery.trim()) return teacher.assignments || [];
        const q = searchQuery.toLowerCase();
        return (teacher.assignments || []).filter(a =>
            a.class?.name?.toLowerCase().includes(q) ||
            a.class?.section?.toLowerCase().includes(q) ||
            a.subject?.name?.toLowerCase().includes(q)
        );
    }, [searchQuery, teacher.assignments]);

    const handleDeviceUpdate = () => {
        setRefreshKey(k => k + 1);
    };

    const handleDelete = () => {
        if (confirm(`Archive "${teacher.user?.name}"? This action can be undone from the teacher list.`)) {
            router.delete(route('principal.teachers.destroy', teacher.id));
        }
    };

    return (
        <AppLayout title={teacher.user?.name ?? 'Teacher'}>
            <Head title={teacher.user?.name ?? 'Teacher'} />

            <div className="max-w-7xl mx-auto">
                <div className="page-header flex-col gap-4 sm:flex-row">
                    <div className="flex items-center gap-3 flex-1">
                        <Link href={route('principal.teachers.index')} className="btn-ghost btn-icon">
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h1 className="page-title">{teacher.user?.name}</h1>
                            <p className="page-subtitle">{teacher.employee_id} · {teacher.specialisation}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Link href={route('principal.teachers.edit', teacher.id)} className="btn-secondary">
                            <PencilSquareIcon className="w-4 h-4" /> Edit
                        </Link>
                        <button onClick={handleDelete} className="btn-secondary text-red-600 hover:text-red-700">
                            <TrashIcon className="w-4 h-4" /> Archive
                        </button>
                    </div>
                </div>

                <div className="flex items-center gap-2 flex-1 sm:justify-center mb-5">
                    <div className="relative flex-1 max-w-xs">
                        <input
                            type="text"
                            placeholder="Search assignments..."
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

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    {/* Profile Card */}
                    <div className="card">
                        <div className="card-body flex flex-col items-center text-center gap-4">
                            {/* Photo */}
                            <div
                                className="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center text-3xl font-bold text-indigo-600 cursor-pointer hover:ring-4 hover:ring-indigo-300 transition overflow-hidden"
                                onClick={() => teacher.photo && setShowImageModal(true)}
                            >
                                {teacher.photo ? (
                                    <img src={`/storage/${teacher.photo}`} alt={teacher.user?.name} className="w-full h-full object-cover" />
                                ) : (
                                    teacher.user?.name?.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
                                )}
                            </div>
                            <div>
                                <p className="text-lg font-bold text-gray-900">{teacher.user?.name}</p>
                                <p className="text-sm text-gray-500">{teacher.user?.email}</p>
                            </div>

                            {/* Personal Information */}
                            <div className="w-full bg-gray-50 rounded-lg p-3 space-y-2 text-sm">
                                <p className="font-semibold text-gray-900 mb-3">Personal Information</p>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Gender</span>
                                    <span className="font-medium capitalize">{teacher.gender || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Date of Birth</span>
                                    <span className="font-medium">{teacher.dob || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Phone</span>
                                    <span className="font-medium">{teacher.phone || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">CNIC</span>
                                    <span className="font-medium">{teacher.cnic || '—'}</span>
                                </div>
                            </div>

                            {/* Academic Information */}
                            <div className="w-full bg-blue-50 rounded-lg p-3 space-y-2 text-sm">
                                <p className="font-semibold text-gray-900 mb-3">Academic Information</p>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Employee ID</span>
                                    <span className="font-mono font-medium">{teacher.employee_id}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Qualification</span>
                                    <span className="font-medium">{teacher.qualification || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Specialization</span>
                                    <span className="font-medium">{teacher.specialisation || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Experience</span>
                                    <span className="font-medium">{teacher.experience_years ? `${teacher.experience_years} years` : '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Joined</span>
                                    <span className="font-medium">{teacher.date_joined || '—'}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-600">Status</span>
                                    <Badge color={teacher.is_active ? 'green' : 'gray'}>{teacher.is_active ? 'Active' : 'Inactive'}</Badge>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="lg:col-span-2 space-y-5">
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

                        {/* Teaching Assignments */}
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

                        {/* Devices */}
                        <TeacherDevicesCard
                            key={refreshKey}
                            teacher={teacher.user!}
                            devices={devices}
                            canAssign={true}
                            onDeviceUpdate={handleDeviceUpdate}
                        />
                    </div>
                </div>
            </div>

            {/* Photo Modal */}
            {showImageModal && teacher.photo && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75 p-4" onClick={() => setShowImageModal(false)}>
                    <div className="relative max-w-2xl max-h-[90vh]" onClick={(e) => e.stopPropagation()}>
                        {/* Close Button */}
                        <button
                            onClick={() => setShowImageModal(false)}
                            className="absolute -top-10 right-0 text-white hover:text-gray-300 transition"
                        >
                            <XMarkIcon className="w-8 h-8" />
                        </button>

                        {/* Image */}
                        <img
                            src={`/storage/${teacher.photo}`}
                            alt={teacher.user?.name}
                            className="w-full h-auto rounded-lg shadow-2xl max-h-[90vh] object-contain"
                        />

                        {/* Teacher Info Below Image */}
                        <div className="mt-4 bg-white rounded-lg p-4 text-center">
                            <p className="text-lg font-bold text-gray-900">{teacher.user?.name}</p>
                            <p className="text-sm text-gray-600">{teacher.employee_id}</p>
                            <p className="text-sm text-gray-500 mt-1">{teacher.specialisation}</p>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
