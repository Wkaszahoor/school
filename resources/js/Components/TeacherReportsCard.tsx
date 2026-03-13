import React from 'react';
import { ExclamationCircleIcon, CheckCircleIcon, XMarkIcon } from '@heroicons/react/24/outline';
import Badge from './Badge';
import type { TeacherReport } from '@/types';

interface Props {
    reports: TeacherReport[];
    showDelete?: boolean;
    onDelete?: (id: number) => void;
}

const REPORT_TYPE_CONFIG = {
    general: { color: 'blue', icon: '📋' },
    performance: { color: 'amber', icon: '📊' },
    conduct: { color: 'red', icon: '👥' },
    attendance: { color: 'purple', icon: '📅' },
};

export default function TeacherReportsCard({ reports, showDelete = false, onDelete }: Props) {
    if (!reports || reports.length === 0) {
        return null;
    }

    return (
        <div className="card border-l-4 border-amber-500">
            <div className="card-header bg-amber-50/60">
                <div className="flex items-center gap-2">
                    <ExclamationCircleIcon className="w-5 h-5 text-amber-600" />
                    <p className="card-title text-amber-700">Reports Received</p>
                </div>
                <Badge color="amber">{reports.length}</Badge>
            </div>
            <div className="divide-y divide-gray-100">
                {reports.map(report => {
                    const typeConfig = REPORT_TYPE_CONFIG[report.report_type as keyof typeof REPORT_TYPE_CONFIG];
                    return (
                        <div key={report.id} className="p-4 hover:bg-gray-50 transition-colors">
                            <div className="flex items-start justify-between gap-3">
                                <div className="flex-1">
                                    <div className="flex items-center gap-2 mb-2">
                                        <span className="text-lg">{typeConfig.icon}</span>
                                        <Badge color={typeConfig.color as any}>
                                            {report.report_type.charAt(0).toUpperCase() + report.report_type.slice(1)}
                                        </Badge>
                                        <span className="text-xs text-gray-500">
                                            {new Date(report.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                    <p className="text-sm font-medium text-gray-900">
                                        From: {report.classTeacher?.name}
                                    </p>
                                    <p className="text-sm text-gray-500 mb-2">
                                        Class: {report.class?.class}{report.class?.section ? `-${report.class.section}` : ''}
                                    </p>
                                    <p className="text-sm text-gray-700 bg-gray-50 rounded p-2">
                                        "{report.notes}"
                                    </p>
                                </div>
                                {showDelete && onDelete && (
                                    <button
                                        onClick={() => onDelete(report.id)}
                                        className="text-gray-400 hover:text-red-600 transition-colors"
                                        title="Delete report"
                                    >
                                        <XMarkIcon className="w-5 h-5" />
                                    </button>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
