import React from 'react';

interface StatCardProps {
    label: string;
    value: number | string;
    icon: React.ComponentType<{ className?: string }>;
    iconBg?: string;
    iconColor?: string;
    change?: { value: number; label?: string };
    suffix?: string;
    href?: string;
    loading?: boolean;
}

export default function StatCard({
    label, value, icon: Icon, iconBg = 'bg-indigo-100', iconColor = 'text-indigo-600',
    change, suffix, loading,
}: StatCardProps) {
    return (
        <div className="stat-card group">
            <div className={`stat-card-icon ${iconBg}`}>
                <Icon className={`w-6 h-6 ${iconColor}`} />
            </div>
            <p className="stat-card-label">{label}</p>
            {loading ? (
                <div className="h-8 w-20 bg-gray-200 rounded animate-pulse mt-2" />
            ) : (
                <p className="stat-card-value">
                    {typeof value === 'number' ? value.toLocaleString() : value}
                    {suffix && <span className="text-lg text-gray-400 ml-1">{suffix}</span>}
                </p>
            )}
            {change && (
                <div className={`stat-card-change ${change.value >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>
                    <span>{change.value >= 0 ? '↑' : '↓'} {Math.abs(change.value)}</span>
                    {change.label && <span className="text-gray-400">{change.label}</span>}
                </div>
            )}
        </div>
    );
}
