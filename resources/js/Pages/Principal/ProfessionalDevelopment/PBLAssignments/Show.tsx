import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';
import type { PageProps } from '@/types';

interface Props extends PageProps {
    assignment: {
        id: number;
        project_title: string;
        description: string;
        project_type: string;
        learning_objectives: string | null;
        requirements: string | null;
        group_size: number | null;
        start_date: string;
        due_date: string;
        presentation_date: string | null;
        total_marks: number;
        status: string;
        teacher: { id: number; name: string; email: string } | null;
        class: { id: number; class: string; section: string } | null;
        subject: { id: number; subject_name: string } | null;
        rubric: { id: number; rubric_name: string } | null;
        groups: Array<{
            id: number;
            group_name: string;
            active_members: Array<{ student: { id: number; full_name: string } }>;
        }>;
        submissions: Array<{
            id: number;
            submitted_at: string;
            status: string;
        }>;
    };
}

export default function PBLAssignmentShow({ assignment }: Props) {
    const { flash } = usePage().props as any;

    const fmt = (d: string | null) => d ? new Date(d).toLocaleDateString('en-GB') : '—';

    return (
        <AppLayout title={assignment.project_title}>
            <Head title={assignment.project_title} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">{assignment.project_title}</h1>
                    <p className="page-subtitle">PBL Assignment Details</p>
                </div>
                <div className="flex gap-2">
                    <Link
                        href={route('principal.professional-development.pbl-assignments.edit', assignment.id)}
                        className="btn btn-secondary"
                    >
                        Edit
                    </Link>
                    <Link
                        href={route('principal.professional-development.pbl-assignments.index')}
                        className="btn btn-secondary"
                    >
                        Back to List
                    </Link>
                </div>
            </div>

            {flash?.success && (
                <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {flash.success}
                </div>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">

                {/* Main Info */}
                <div className="lg:col-span-2 space-y-6">
                    <div className="card">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Assignment Details</h2>
                        <div className="space-y-4">
                            <div>
                                <p className="text-sm text-gray-500">Description</p>
                                <p className="text-gray-900 mt-1 whitespace-pre-line">{assignment.description}</p>
                            </div>

                            {assignment.learning_objectives && (
                                <div>
                                    <p className="text-sm text-gray-500">Learning Objectives</p>
                                    <p className="text-gray-900 mt-1 whitespace-pre-line">{assignment.learning_objectives}</p>
                                </div>
                            )}

                            {assignment.requirements && (
                                <div>
                                    <p className="text-sm text-gray-500">Requirements & Deliverables</p>
                                    <p className="text-gray-900 mt-1 whitespace-pre-line">{assignment.requirements}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Student Groups */}
                    <div className="card">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Student Groups
                            <span className="ml-2 text-sm font-normal text-gray-500">({assignment.groups.length})</span>
                        </h2>
                        {assignment.groups.length > 0 ? (
                            <div className="space-y-3">
                                {assignment.groups.map(group => (
                                    <div key={group.id} className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <p className="font-medium text-gray-900">{group.group_name}</p>
                                        <div className="flex flex-wrap gap-2 mt-2">
                                            {group.active_members?.map(m => (
                                                <span key={m.student?.id} className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                    {m.student?.full_name}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm">No student groups assigned yet.</p>
                        )}
                    </div>

                    {/* Submissions */}
                    <div className="card">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                            Submissions
                            <span className="ml-2 text-sm font-normal text-gray-500">({assignment.submissions.length})</span>
                        </h2>
                        {assignment.submissions.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-gray-50 border-b">
                                        <tr>
                                            <th className="text-left py-2 px-3 font-semibold text-gray-700">#</th>
                                            <th className="text-left py-2 px-3 font-semibold text-gray-700">Submitted At</th>
                                            <th className="text-center py-2 px-3 font-semibold text-gray-700">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {assignment.submissions.map((sub, i) => (
                                            <tr key={sub.id} className="border-b">
                                                <td className="py-2 px-3 text-gray-600">{i + 1}</td>
                                                <td className="py-2 px-3 text-gray-600">{fmt(sub.submitted_at)}</td>
                                                <td className="py-2 px-3 text-center">
                                                    <StatusBadge status={sub.status} type="submission" size="sm" />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-gray-500 text-sm">No submissions yet.</p>
                        )}
                    </div>
                </div>

                {/* Sidebar */}
                <div className="space-y-6">
                    <div className="card">
                        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Info</h2>
                        <dl className="space-y-3 text-sm">
                            <div>
                                <dt className="text-gray-500">Status</dt>
                                <dd className="mt-1">
                                    <StatusBadge status={assignment.status} type="assignment" size="sm" />
                                </dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Teacher</dt>
                                <dd className="text-gray-900 font-medium">{assignment.teacher?.name ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Class</dt>
                                <dd className="text-gray-900">{assignment.class ? `${assignment.class.class} ${assignment.class.section}` : '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Subject</dt>
                                <dd className="text-gray-900">{assignment.subject?.subject_name ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Project Type</dt>
                                <dd className="text-gray-900 capitalize">{assignment.project_type}</dd>
                            </div>
                            {assignment.group_size && (
                                <div>
                                    <dt className="text-gray-500">Group Size</dt>
                                    <dd className="text-gray-900">{assignment.group_size} students</dd>
                                </div>
                            )}
                            <div>
                                <dt className="text-gray-500">Total Marks</dt>
                                <dd className="text-gray-900 font-semibold">{assignment.total_marks}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Rubric</dt>
                                <dd className="text-gray-900">{assignment.rubric?.rubric_name ?? 'None'}</dd>
                            </div>
                            <hr />
                            <div>
                                <dt className="text-gray-500">Start Date</dt>
                                <dd className="text-gray-900">{fmt(assignment.start_date)}</dd>
                            </div>
                            <div>
                                <dt className="text-gray-500">Due Date</dt>
                                <dd className="text-gray-900 font-medium">{fmt(assignment.due_date)}</dd>
                            </div>
                            {assignment.presentation_date && (
                                <div>
                                    <dt className="text-gray-500">Presentation</dt>
                                    <dd className="text-gray-900">{fmt(assignment.presentation_date)}</dd>
                                </div>
                            )}
                        </dl>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
