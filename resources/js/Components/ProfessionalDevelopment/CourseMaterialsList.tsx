import React from 'react';
import { ArrowDownTrayIcon, DocumentIcon } from '@heroicons/react/24/outline';

interface Material {
    id: number;
    file_name: string;
    file_size: number;
    file_path: string;
    uploaded_at: string;
}

interface CourseMaterialsListProps {
    materials: Material[];
    allowDownload?: boolean;
}

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};

export default function CourseMaterialsList({ materials, allowDownload = true }: CourseMaterialsListProps) {
    if (materials.length === 0) {
        return (
            <div className="empty-state py-8">
                <DocumentIcon className="empty-state-icon w-12 h-12" />
                <p className="empty-state-text">No materials uploaded yet</p>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            {materials.map(material => (
                <div key={material.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                    <div className="flex items-center gap-3 flex-1 min-w-0">
                        <DocumentIcon className="w-5 h-5 text-blue-600 flex-shrink-0" />
                        <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-900 truncate">{material.file_name}</p>
                            <p className="text-xs text-gray-500">
                                {formatFileSize(material.file_size)} • {new Date(material.uploaded_at).toLocaleDateString('en-GB')}
                            </p>
                        </div>
                    </div>
                    {allowDownload && (
                        <a href={material.file_path} download className="btn-ghost btn-icon btn-sm flex-shrink-0 ml-2">
                            <ArrowDownTrayIcon className="w-4 h-4" />
                        </a>
                    )}
                </div>
            ))}
        </div>
    );
}
