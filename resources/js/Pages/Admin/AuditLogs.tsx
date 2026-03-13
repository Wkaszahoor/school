import React from 'react';
import { Head } from '@inertiajs/react';
import { ClipboardDocumentListIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import Pagination from '@/Components/Pagination';
import Badge from '@/Components/Badge';
import SearchInput from '@/Components/SearchInput';
import type { PageProps, PaginatedData, AuditLog } from '@/types';

interface Props extends PageProps {
    logs: PaginatedData<AuditLog>;
}

const ROLE_COLOR: Record<string, 'red' | 'purple' | 'blue' | 'green' | 'indigo' | 'orange' | 'yellow' | 'gray'> = {
    admin: 'red', principal: 'purple', teacher: 'blue',
    receptionist: 'green', principal_helper: 'indigo',
    inventory_manager: 'orange', doctor: 'yellow',
};

export default function AuditLogs({ logs }: Props) {
    return (
        <AppLayout title="Audit Logs">
            <Head title="Audit Logs" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Audit Logs</h1>
                    <p className="page-subtitle">{logs.total} actions recorded</p>
                </div>
            </div>

            <div className="card mb-5">
                <div className="card-body !py-4 flex flex-wrap gap-3 items-center">
                    <SearchInput placeholder="Search by action…" paramName="action" className="w-64" />
                    <input type="date" className="form-input w-44" placeholder="From"
                           onChange={e => {}} />
                    <input type="date" className="form-input w-44" placeholder="To"
                           onChange={e => {}} />
                </div>
            </div>

            <div className="card">
                <div className="table-wrapper">
                    <table className="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>IP Address</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7}>
                                        <div className="empty-state py-12">
                                            <ClipboardDocumentListIcon className="empty-state-icon" />
                                            <p className="empty-state-text">No audit logs found</p>
                                        </div>
                                    </td>
                                </tr>
                            ) : logs.data.map((log, i) => (
                                <tr key={log.id}>
                                    <td className="text-gray-400 text-xs">#{log.id}</td>
                                    <td>
                                        <div className="flex items-center gap-2">
                                            <div className="avatar-xs bg-indigo-600">
                                                {log.user?.name?.charAt(0) ?? '?'}
                                            </div>
                                            <span className="font-medium text-gray-900">{log.user?.name ?? 'System'}</span>
                                        </div>
                                    </td>
                                    <td>
                                        {log.user?.role && (
                                            <Badge color={ROLE_COLOR[log.user.role] ?? 'gray'}>
                                                {log.user.role_label}
                                            </Badge>
                                        )}
                                    </td>
                                    <td>
                                        <span className="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                            {log.action}
                                        </span>
                                    </td>
                                    <td className="text-gray-600">
                                        {log.resource}{log.resource_id ? ` #${log.resource_id}` : ''}
                                    </td>
                                    <td className="font-mono text-xs text-gray-400">{log.ip_address ?? '—'}</td>
                                    <td className="text-gray-400 text-xs">
                                        {new Date(log.created_at).toLocaleString('en-GB', {
                                            day: 'numeric', month: 'short', year: 'numeric',
                                            hour: '2-digit', minute: '2-digit',
                                        })}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                <div className="card-footer">
                    <Pagination data={logs} />
                </div>
            </div>
        </AppLayout>
    );
}
