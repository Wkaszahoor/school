import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    PlusIcon, EyeIcon, TrashIcon, CheckCircleIcon, XCircleIcon,
    UserGroupIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';
import Badge from '@/Components/Badge';
import type { PageProps, PaginatedData, Notice, SchoolClass } from '@/types';

interface TeacherProfile {
    id: number;
    user?: { id: number; name: string };
}

interface Props extends PageProps {
    notices: PaginatedData<Notice>;
    classes: SchoolClass[];
    teachers: TeacherProfile[];
}

export default function NoticesIndex({ notices, classes, teachers }: Props) {
    const [isOpen, setIsOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        body: '',
        target_scope: 'all',
        target_role: '',
        target_user_id: '',
        target_class_id: '',
        expires_at: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.notices.store'), {
            onSuccess: () => {
                setIsOpen(false);
                reset();
            },
        });
    };

    const handleToggle = (notice: Notice) => {
        router.post(route('principal.notices.toggle', notice.id), {}, {
            preserveScroll: true,
        });
    };

    const handleDelete = (notice: Notice) => {
        if (confirm('Are you sure you want to delete this notice?')) {
            router.delete(route('principal.notices.destroy', notice.id), {
                preserveScroll: true,
            });
        }
    };

    const getTargetLabel = (notice: Notice): string => {
        if (notice.target_scope === 'all') return 'All Users';
        if (notice.target_scope === 'role') return `Role: ${notice.target_role}`;
        if (notice.target_scope === 'teacher') {
            const teacher = teachers.find(t => t.user?.id === notice.target_user_id);
            return `Teacher: ${teacher?.user?.name || 'N/A'}`;
        }
        if (notice.target_scope === 'class') {
            const cls = classes.find(c => c.id === Number(notice.target_class_id));
            return `Class: ${cls?.class}${cls?.section ? ` - ${cls.section}` : ''}`;
        }
        return 'Unknown';
    };

    return (
        <AppLayout title="Notices">
            <Head title="Notices & Broadcast" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Notices & Broadcast</h1>
                    <p className="page-subtitle">Manage and publish notices to staff and students</p>
                </div>
                <button
                    onClick={() => setIsOpen(true)}
                    className="btn-primary"
                >
                    <PlusIcon className="w-4 h-4" />
                    Publish Notice
                </button>
            </div>

            {/* Table */}
            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Audience</th>
                                <th>Status</th>
                                <th>Expires</th>
                                <th>Posted by</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {notices.data.length === 0 ? (
                                <tr>
                                    <td colSpan={6}>
                                        <div className="empty-state py-12">
                                            <UserGroupIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No notices published yet</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : notices.data.map(notice => (
                                <tr key={notice.id}>
                                    <td>
                                        <div className="font-medium text-gray-900">{notice.title}</div>
                                        <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{notice.body}</p>
                                    </td>
                                    <td className="text-sm text-gray-700">{getTargetLabel(notice)}</td>
                                    <td>
                                        <Badge color={notice.is_active ? 'green' : 'gray'}>
                                            {notice.is_active ? 'Active' : 'Inactive'}
                                        </Badge>
                                    </td>
                                    <td className="text-sm text-gray-500">
                                        {notice.expires_at
                                            ? new Date(notice.expires_at).toLocaleDateString('en-GB', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                            })
                                            : '—'
                                        }
                                    </td>
                                    <td className="text-sm text-gray-700">{notice.postedBy?.name}</td>
                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            <button
                                                onClick={() => handleToggle(notice)}
                                                className="btn-ghost btn-icon btn-sm"
                                                title={notice.is_active ? 'Deactivate' : 'Activate'}
                                            >
                                                {notice.is_active ? (
                                                    <CheckCircleIcon className="w-4 h-4 text-green-600" />
                                                ) : (
                                                    <XCircleIcon className="w-4 h-4 text-gray-400" />
                                                )}
                                            </button>
                                            <button
                                                onClick={() => handleDelete(notice)}
                                                className="btn-ghost btn-icon btn-sm"
                                                title="Delete"
                                            >
                                                <TrashIcon className="w-4 h-4 text-red-600" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={notices} />
                </div>
            </div>

            {/* Publish Notice Modal */}
            <Modal isOpen={isOpen} onClose={() => setIsOpen(false)} title="Publish Notice" size="lg">
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Title */}
                    <div className="form-group">
                        <label className="form-label">Title *</label>
                        <input
                            type="text"
                            className="form-input"
                            value={data.title}
                            onChange={e => setData('title', e.target.value)}
                            placeholder="e.g., School Closure Due to Holiday"
                        />
                        {errors.title && <p className="form-error">{errors.title}</p>}
                    </div>

                    {/* Body */}
                    <div className="form-group">
                        <label className="form-label">Message *</label>
                        <textarea
                            className="form-textarea"
                            rows={4}
                            value={data.body}
                            onChange={e => setData('body', e.target.value)}
                            placeholder="Write your notice message here..."
                        />
                        {errors.body && <p className="form-error">{errors.body}</p>}
                    </div>

                    {/* Scope */}
                    <div className="form-group">
                        <label className="form-label">Audience *</label>
                        <select
                            className="form-select"
                            value={data.target_scope}
                            onChange={e => {
                                setData('target_scope', e.target.value);
                                setData('target_role', '');
                                setData('target_user_id', '');
                                setData('target_class_id', '');
                            }}
                        >
                            <option value="all">All Users</option>
                            <option value="role">Specific Role</option>
                            <option value="teacher">Specific Teacher</option>
                            <option value="class">Specific Class</option>
                        </select>
                        {errors.target_scope && <p className="form-error">{errors.target_scope}</p>}
                    </div>

                    {/* Conditional Target Fields */}
                    {data.target_scope === 'role' && (
                        <div className="form-group">
                            <label className="form-label">Role *</label>
                            <select
                                className="form-select"
                                value={data.target_role}
                                onChange={e => setData('target_role', e.target.value)}
                            >
                                <option value="">Select Role</option>
                                <option value="admin">Admin</option>
                                <option value="principal">Principal</option>
                                <option value="teacher">Teacher</option>
                                <option value="receptionist">Receptionist</option>
                                <option value="doctor">Doctor</option>
                                <option value="inventory_manager">Inventory Manager</option>
                                <option value="principal_helper">Principal Helper</option>
                            </select>
                            {errors.target_role && <p className="form-error">{errors.target_role}</p>}
                        </div>
                    )}

                    {data.target_scope === 'teacher' && (
                        <div className="form-group">
                            <label className="form-label">Teacher *</label>
                            <select
                                className="form-select"
                                value={data.target_user_id}
                                onChange={e => setData('target_user_id', e.target.value)}
                            >
                                <option value="">Select Teacher</option>
                                {teachers.map(teacher => (
                                    <option key={teacher.id} value={teacher.user?.id || ''}>
                                        {teacher.user?.name}
                                    </option>
                                ))}
                            </select>
                            {errors.target_user_id && <p className="form-error">{errors.target_user_id}</p>}
                        </div>
                    )}

                    {data.target_scope === 'class' && (
                        <div className="form-group">
                            <label className="form-label">Class *</label>
                            <select
                                className="form-select"
                                value={data.target_class_id}
                                onChange={e => setData('target_class_id', e.target.value)}
                            >
                                <option value="">Select Class</option>
                                {classes.map(cls => (
                                    <option key={cls.id} value={cls.id}>
                                        Class {cls.class}{cls.section ? ` - ${cls.section}` : ''} ({cls.academic_year})
                                    </option>
                                ))}
                            </select>
                            {errors.target_class_id && <p className="form-error">{errors.target_class_id}</p>}
                        </div>
                    )}

                    {/* Expiry Date */}
                    <div className="form-group">
                        <label className="form-label">Expires At (optional)</label>
                        <input
                            type="datetime-local"
                            className="form-input"
                            value={data.expires_at}
                            onChange={e => setData('expires_at', e.target.value)}
                        />
                        <p className="form-hint">Leave blank to keep notice active indefinitely</p>
                        {errors.expires_at && <p className="form-error">{errors.expires_at}</p>}
                    </div>

                    {/* Actions */}
                    <div className="flex gap-2 justify-end pt-4 border-t border-gray-100">
                        <button
                            type="button"
                            onClick={() => {
                                setIsOpen(false);
                                reset();
                            }}
                            className="btn-secondary"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-primary"
                        >
                            {processing ? 'Publishing...' : 'Publish Notice'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
