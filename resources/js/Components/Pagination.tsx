import React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/20/solid';
import type { PaginatedData } from '@/types';

interface PaginationProps<T> {
    data: PaginatedData<T>;
}

export default function Pagination<T>({ data }: PaginationProps<T>) {
    if (!data || !data.last_page || data.last_page <= 1) return null;

    return (
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-2 py-3">
            <p className="text-xs sm:text-sm text-gray-500 whitespace-nowrap">
                Showing <span className="font-medium">{data.from ?? 0}</span> to{' '}
                <span className="font-medium">{data.to ?? 0}</span> of{' '}
                <span className="font-medium">{data.total}</span>
            </p>
            <div className="flex items-center gap-1 overflow-x-auto">
                {data.prev_page_url && (
                    <Link href={data.prev_page_url}
                          className="btn-secondary btn-sm btn-icon"
                          preserveScroll>
                        <ChevronLeftIcon className="w-4 h-4" />
                    </Link>
                )}
                {data.links.map((link, i) => {
                    if (link.label.includes('Previous') || link.label.includes('Next')) return null;
                    const isEllipsis = link.label === '...';
                    return (
                        <React.Fragment key={i}>
                            {isEllipsis ? (
                                <span className="px-3 py-1.5 text-xs text-gray-400">…</span>
                            ) : link.url ? (
                                <Link
                                    href={link.url}
                                    className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
                                        link.active
                                            ? 'bg-indigo-600 text-white'
                                            : 'text-gray-600 hover:bg-gray-100'
                                    }`}
                                    preserveScroll
                                >
                                    {link.label}
                                </Link>
                            ) : (
                                <span className={`px-3 py-1.5 text-xs font-medium rounded-lg ${
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-300'
                                }`}>
                                    {link.label}
                                </span>
                            )}
                        </React.Fragment>
                    );
                })}
                {data.next_page_url && (
                    <Link href={data.next_page_url}
                          className="btn-secondary btn-sm btn-icon"
                          preserveScroll>
                        <ChevronRightIcon className="w-4 h-4" />
                    </Link>
                )}
            </div>
        </div>
    );
}
