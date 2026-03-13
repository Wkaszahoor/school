import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { DocumentTextIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import ReportTeacherModal from '@/Components/ReportTeacherModal';
import type { PageProps, SchoolClass, TeacherAssignment } from '@/types';

interface Props extends PageProps {
    classTeacherClass: SchoolClass | null;
    subjectTeachers: Array<{ id: number; name: string; subject: string; assignment_id: number }>;
}

export default function ClassManagement({ classTeacherClass, subjectTeachers }: Props) {
    const [isReportModalOpen, setIsReportModalOpen] = useState(false);

    if (!classTeacherClass) {
        return (
            <AppLayout title="Class Management">
                <Head title="Class Management" />
                <div className="page-header">
                    <div>
                        <h1 className="page-title">Class Management</h1>
                        <p className="page-subtitle">Manage your homeroom class</p>
                    </div>
                </div>
                <div className="card">
                    <div className="card-body empty-state">
                        <p className="empty-state-text">You are not assigned as a class teacher</p>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout title="Class Management">
            <Head title="Class Management" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Class Management</h1>
                    <p className="page-subtitle">Manage your homeroom class: {classTeacherClass.class}{classTeacherClass.section ? `-${classTeacherClass.section}` : ''}</p>
                </div>
            </div>

            {/* Class Info Card */}
            <div className="card mb-5">
                <div className="card-body">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <p className="text-sm text-gray-500">Class</p>
                            <p className="text-lg font-bold text-gray-900">{classTeacherClass.class}{classTeacherClass.section ? `-${classTeacherClass.section}` : ''}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Academic Year</p>
                            <p className="text-lg font-bold text-gray-900">{classTeacherClass.academic_year}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Status</p>
                            <p className="text-lg font-bold text-emerald-600">{classTeacherClass.is_active ? 'Active' : 'Inactive'}</p>
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">Subject Teachers</p>
                            <p className="text-lg font-bold text-blue-600">{subjectTeachers.length}</p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Subject Teachers List */}
            <div className="card">
                <div className="card-header">
                    <p className="card-title">Subject Teachers</p>
                    <p className="text-sm text-gray-500">{subjectTeachers.length} teachers</p>
                </div>
                {subjectTeachers.length === 0 ? (
                    <div className="card-body empty-state">
                        <p className="empty-state-text">No subject teachers assigned to this class</p>
                    </div>
                ) : (
                    <div className="divide-y divide-gray-100">
                        {subjectTeachers.map(teacher => (
                            <div key={teacher.assignment_id} className="p-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                                <div>
                                    <p className="font-medium text-gray-900">{teacher.name}</p>
                                    <p className="text-sm text-gray-500">{teacher.subject}</p>
                                </div>
                                <button
                                    onClick={() => setIsReportModalOpen(true)}
                                    className="btn-secondary btn-sm"
                                >
                                    <DocumentTextIcon className="w-4 h-4" />
                                    Report
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>

            <ReportTeacherModal
                isOpen={isReportModalOpen}
                onClose={() => setIsReportModalOpen(false)}
                classId={classTeacherClass.id}
                subjectTeachers={subjectTeachers.map(t => ({ id: t.id, name: t.name }))}
            />
        </AppLayout>
    );
}
