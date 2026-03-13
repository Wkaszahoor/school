import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import { PlusIcon, PencilSquareIcon, TrashIcon, KeyIcon, ShieldCheckIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import SearchInput from '@/Components/SearchInput';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import type { PageProps, PaginatedData, User } from '@/types';

interface Props extends PageProps {
    users: PaginatedData<User>;
}

const ROLES = [
    { value: 'admin',             label: 'Administrator' },
    { value: 'principal',         label: 'Principal' },
    { value: 'teacher',           label: 'Teacher' },
    { value: 'receptionist',      label: 'Receptionist' },
    { value: 'principal_helper',  label: 'Principal Helper' },
    { value: 'inventory_manager', label: 'Inventory Manager' },
    { value: 'doctor',            label: 'Doctor' },
];

const ROLE_COLORS: Record<string, 'red' | 'purple' | 'blue' | 'green' | 'indigo' | 'orange' | 'yellow'> = {
    admin: 'red', principal: 'purple', teacher: 'blue',
    receptionist: 'green', principal_helper: 'indigo',
    inventory_manager: 'orange', doctor: 'yellow',
};

export default function StaffUsersIndex({ users }: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [editUser, setEditUser] = useState<User | null>(null);

    const createForm = useForm({
        name: '', email: '', password: '', role: 'teacher',
    });

    const editForm = useForm({
        name: '', role: 'teacher', is_active: true, password: '',
    });

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post(route('admin.users.store'), {
            onSuccess: () => { setCreateOpen(false); createForm.reset(); },
        });
    };

    const openEdit = (user: User) => {
        setEditUser(user);
        editForm.setData({ name: user.name, role: user.role, is_active: true, password: '' });
    };

    const handleEdit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editUser) return;
        editForm.put(route('admin.users.update', editUser.id), {
            onSuccess: () => { setEditUser(null); editForm.reset(); },
        });
    };

    return (
        <AppLayout title="Staff Users">
            <Head title="Staff Users" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Staff Users</h1>
                    <p className="page-subtitle">{users.total} system users</p>
                </div>
                <button onClick={() => setCreateOpen(true)} className="btn-primary">
                    <PlusIcon className="w-4 h-4" />
                    Add User
                </button>
            </div>

            <div className="card mb-5">
                <div className="card-body !py-4 flex flex-col sm:flex-row flex-wrap gap-3 items-stretch sm:items-center">
                    <SearchInput placeholder="Search users…" className="w-full sm:w-64" />
                    <select
                        onChange={e => router.get(route('admin.users.index'), { role: e.target.value }, { preserveState: true, replace: true })}
                        className="form-select w-full sm:w-44"
                    >
                        <option value="">All Roles</option>
                        {ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
                    </select>
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th className="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.map(user => (
                                <tr key={user.id}>
                                    <td>
                                        <div className="flex items-center gap-3">
                                            <div className="avatar-sm bg-indigo-600">{user.name.charAt(0)}</div>
                                            <span className="font-medium text-gray-900">{user.name}</span>
                                        </div>
                                    </td>
                                    <td className="text-gray-500">{user.email}</td>
                                    <td>
                                        <Badge color={ROLE_COLORS[user.role] ?? 'gray'}>
                                            {user.role_label}
                                        </Badge>
                                    </td>
                                    <td>
                                        <Badge color={'green'}>Active</Badge>
                                    </td>
                                    <td className="text-gray-400 text-xs">—</td>
                                    <td>
                                        <div className="flex items-center justify-end gap-1">
                                            <button onClick={() => openEdit(user)} className="btn-ghost btn-icon btn-sm" title="Edit">
                                                <PencilSquareIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={users} />
                </div>
            </div>

            {/* Create Modal */}
            <Modal isOpen={createOpen} onClose={() => setCreateOpen(false)} title="Create User" size="md">
                <form onSubmit={handleCreate} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Full Name</label>
                        <input className="form-input" value={createForm.data.name}
                               onChange={e => createForm.setData('name', e.target.value)} required />
                        {createForm.errors.name && <p className="form-error">{createForm.errors.name}</p>}
                    </div>
                    <div className="form-group">
                        <label className="form-label">Email Address</label>
                        <input type="email" className="form-input" value={createForm.data.email}
                               onChange={e => createForm.setData('email', e.target.value)} required />
                        {createForm.errors.email && <p className="form-error">{createForm.errors.email}</p>}
                    </div>
                    <div className="form-group">
                        <label className="form-label">Role</label>
                        <select className="form-select" value={createForm.data.role}
                                onChange={e => createForm.setData('role', e.target.value)}>
                            {ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </select>
                    </div>
                    <div className="form-group">
                        <label className="form-label">Password</label>
                        <input type="password" className="form-input" value={createForm.data.password}
                               onChange={e => createForm.setData('password', e.target.value)}
                               placeholder="Minimum 8 characters" required />
                        {createForm.errors.password && <p className="form-error">{createForm.errors.password}</p>}
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setCreateOpen(false)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={createForm.processing} className="btn-primary flex-1">
                            {createForm.processing ? 'Creating…' : 'Create User'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={!!editUser} onClose={() => setEditUser(null)} title="Edit User" size="md">
                <form onSubmit={handleEdit} className="space-y-4">
                    <div className="form-group">
                        <label className="form-label">Full Name</label>
                        <input className="form-input" value={editForm.data.name}
                               onChange={e => editForm.setData('name', e.target.value)} required />
                    </div>
                    <div className="form-group">
                        <label className="form-label">Role</label>
                        <select className="form-select" value={editForm.data.role}
                                onChange={e => editForm.setData('role', e.target.value)}>
                            {ROLES.map(r => <option key={r.value} value={r.value}>{r.label}</option>)}
                        </select>
                    </div>
                    <div className="form-group">
                        <label className="form-label">New Password (leave blank to keep)</label>
                        <input type="password" className="form-input" value={editForm.data.password}
                               onChange={e => editForm.setData('password', e.target.value)}
                               placeholder="Optional — min 8 chars" />
                    </div>
                    <div className="flex gap-3 pt-2">
                        <button type="button" onClick={() => setEditUser(null)} className="btn-secondary flex-1">Cancel</button>
                        <button type="submit" disabled={editForm.processing} className="btn-primary flex-1">
                            {editForm.processing ? 'Saving…' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
