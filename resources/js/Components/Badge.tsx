import React from 'react';

type Color = 'green' | 'red' | 'yellow' | 'blue' | 'purple' | 'gray' | 'indigo' | 'orange';

interface BadgeProps {
    children: React.ReactNode;
    color?: Color;
    dot?: boolean;
}

const colorMap: Record<Color, string> = {
    green:  'badge-green',
    red:    'badge-red',
    yellow: 'badge-yellow',
    blue:   'badge-blue',
    purple: 'badge-purple',
    gray:   'badge-gray',
    indigo: 'badge-indigo',
    orange: 'badge-orange',
};

export default function Badge({ children, color = 'gray', dot }: BadgeProps) {
    return (
        <span className={colorMap[color]}>
            {dot && (
                <span className={`w-1.5 h-1.5 rounded-full bg-current`} />
            )}
            {children}
        </span>
    );
}
