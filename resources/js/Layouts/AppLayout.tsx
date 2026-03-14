import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import {
    HomeIcon, UsersIcon, AcademicCapIcon, ClipboardDocumentListIcon,
    ChartBarIcon, BellIcon, DocumentTextIcon, ShieldCheckIcon,
    BeakerIcon, ArchiveBoxIcon, UserGroupIcon, Bars3Icon, XMarkIcon,
    ArrowRightOnRectangleIcon, ChevronDownIcon, MagnifyingGlassIcon,
    CheckCircleIcon, ExclamationTriangleIcon, InformationCircleIcon, XCircleIcon,
    BookOpenIcon, ClipboardDocumentCheckIcon, HeartIcon, CubeIcon,
    EnvelopeIcon, CalendarIcon, BuildingLibraryIcon, IdentificationIcon,
    ArrowUpTrayIcon, SparklesIcon, PresentationChartLineIcon, LightBulbIcon,
    ListBulletIcon, CheckIcon, UserIcon, ChatBubbleLeftRightIcon,
} from '@heroicons/react/24/outline';
import axios from 'axios';
import type { PageProps, User } from '@/types';

type NavItem = {
    label: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    badge?: number;
    active?: boolean;
};

type NavGroup = {
    section?: string;
    items: NavItem[];
};

function getNavigation(user: User, pageProps: any): NavGroup[] {
    const flash = pageProps.flash ?? {};

    switch (user.role) {
        case 'admin':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('admin.dashboard'), icon: HomeIcon },
                    ],
                },
                {
                    section: 'Management',
                    items: [
                        { label: 'Students',   href: route('admin.students.index'),  icon: UsersIcon },
                        { label: 'Teachers',   href: route('admin.teachers.index'),  icon: AcademicCapIcon },
                        { label: 'Import Teachers',  href: route('admin.import-teachers.index'), icon: ArrowUpTrayIcon },
                        { label: 'Classes',    href: route('admin.classes.index'),   icon: BookOpenIcon },
                        { label: 'Subject Management', href: route('admin.subject-management.index'), icon: BeakerIcon },
                        { label: 'University', href: route('admin.university-students.index'), icon: BuildingLibraryIcon },
                        { label: 'Staff Users',href: route('admin.users.index'),     icon: ShieldCheckIcon },
                    ],
                },
                {
                    section: 'Calendar & Holidays',
                    items: [
                        { label: 'Holidays', href: route('admin.holidays.index'), icon: CalendarIcon },
                        { label: 'Holiday Calendar', href: route('admin.holidays.calendar'), icon: CalendarIcon },
                        { label: 'Holiday Types', href: route('admin.holiday-types.index'), icon: CubeIcon },
                    ],
                },
                {
                    section: 'Reports',
                    items: [
                        { label: 'Audit Logs', href: route('admin.audit-logs'), icon: ClipboardDocumentListIcon },
                    ],
                },
            ];

        case 'principal':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('principal.dashboard'), icon: HomeIcon },
                    ],
                },
                {
                    section: 'Academic',
                    items: [
                        { label: 'Students',              href: route('principal.students.index'), icon: UsersIcon },
                        { label: 'Attendance Report',     href: route('principal.attendance-report.index'), icon: ChartBarIcon },
                        { label: 'Attendance Performance',href: route('principal.attendance-performance.index'), icon: ChartBarIcon },
                        { label: 'Attendance Criteria',   href: route('principal.attendance-criteria.index'), icon: ClipboardDocumentCheckIcon },
                        { label: 'Teacher Management',    href: route('principal.teachers.index'), icon: AcademicCapIcon },
                        { label: 'Teacher Assignments',   href: route('principal.teacher-assignments.index'), icon: AcademicCapIcon },
                        { label: 'Subject Groups',        href: route('principal.subject-groups.index'), icon: BookOpenIcon },
                        { label: 'Student Selections',    href: route('principal.student-selections.index'), icon: CheckIcon },
                        { label: 'Datesheets',            href: route('principal.datesheets.index'), icon: CalendarIcon },
                        { label: 'Admission Cards',       href: route('principal.admission-cards.index'), icon: IdentificationIcon },
                        { label: 'Results',               href: route('principal.results.index'), icon: ChartBarIcon },
                        { label: 'Lesson Plans',          href: route('principal.lesson-plans.index'), icon: DocumentTextIcon },
                        { label: 'Academic Calendar',     href: route('principal.academic-calendars.index'), icon: CalendarIcon },
                    ],
                },
                {
                    section: 'Administration',
                    items: [
                        { label: 'Messages',       href: route('chat.index'),                    icon: ChatBubbleLeftRightIcon },
                        { label: 'Notices',        href: route('principal.notices.index'),       icon: BellIcon },
                        { label: 'Leave Requests', href: route('principal.leave.index'),         icon: CalendarIcon },
                        { label: 'Discipline',     href: route('principal.discipline.index'),    icon: ExclamationTriangleIcon },
                        { label: 'To-Do List',     href: route('todos.index'),                   icon: ClipboardDocumentListIcon },
                    ],
                },
                {
                    section: 'Timetable Management',
                    items: [
                        { label: 'Timetables', href: route('principal.timetables.index'), icon: SparklesIcon },
                        { label: 'Time Slots', href: route('principal.time-slots.index'), icon: CalendarIcon },
                        { label: 'Rooms', href: route('principal.rooms.index'), icon: BuildingLibraryIcon },
                        { label: 'Teacher Availabilities', href: route('principal.teacher-availabilities.index'), icon: AcademicCapIcon },
                    ],
                },
                {
                    section: 'Professional Development',
                    items: [
                        { label: 'Training Courses', href: route('principal.professional-development.training-courses.index'), icon: AcademicCapIcon },
                        { label: 'PBL Assignments', href: route('principal.professional-development.pbl-assignments.index'), icon: LightBulbIcon },
                        { label: 'Certifications', href: route('principal.professional-development.certifications.index'), icon: CheckCircleIcon },
                    ],
                },
            ];

        case 'teacher':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('teacher.dashboard'), icon: HomeIcon },
                    ],
                },
                {
                    section: 'My Work',
                    items: [
                        { label: 'Class Management',  href: route('teacher.class-management.index'), icon: UserGroupIcon },
                        { label: 'Mark Attendance', href: route('teacher.attendance.index'), icon: ClipboardDocumentCheckIcon },
                        { label: 'Attendance Report', href: route('teacher.attendance.report'), icon: ChartBarIcon },
                        { label: 'Attendance Criteria', href: route('teacher.attendance-criteria.index'), icon: ClipboardDocumentCheckIcon },
                        { label: 'Results',          href: route('teacher.results.index'),     icon: AcademicCapIcon },
                        { label: 'Lesson Plans',     href: route('teacher.lesson-plans.index'), icon: DocumentTextIcon },
                        { label: 'Leave Requests',   href: route('teacher.leave.index'), icon: CalendarIcon },
                        { label: 'Messages',         href: route('chat.index'),                icon: ChatBubbleLeftRightIcon },
                        { label: 'To-Do List',       href: route('todos.index'), icon: ClipboardDocumentListIcon },
                    ],
                },
                {
                    section: 'Professional Development',
                    items: [
                        { label: 'Training Courses', href: route('teacher.professional-development.training-courses.index'), icon: AcademicCapIcon },
                        { label: 'PBL Assignments', href: route('teacher.professional-development.pbl-assignments.index'), icon: LightBulbIcon },
                        { label: 'Teaching Resources', href: route('teacher.professional-development.resources.index'), icon: BookOpenIcon },
                        { label: 'My Certifications', href: route('teacher.professional-development.certifications.index'), icon: CheckCircleIcon },
                    ],
                },
                {
                    section: 'Account',
                    items: [
                        { label: 'My Profile', href: route('teacher.profile.edit'), icon: UserIcon },
                    ],
                },
            ];

        case 'doctor':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('doctor.dashboard'), icon: HomeIcon },
                        { label: 'Medical Records', href: route('doctor.records'), icon: HeartIcon },
                    ],
                },
            ];

        case 'receptionist':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('receptionist.dashboard'), icon: HomeIcon },
                        { label: 'Students',  href: route('receptionist.students'),  icon: UsersIcon },
                    ],
                },
            ];

        case 'principal_helper':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('helper.dashboard'), icon: HomeIcon },
                        { label: 'Students',  href: route('helper.students'),  icon: UsersIcon },
                    ],
                },
            ];

        case 'inventory_manager':
            return [
                {
                    items: [
                        { label: 'Dashboard', href: route('inventory.dashboard'), icon: HomeIcon },
                        { label: 'Items',     href: route('inventory.items'),     icon: CubeIcon },
                    ],
                },
            ];

        default:
            return [];
    }
}

function avatarBg(role: string): string {
    const map: Record<string, string> = {
        admin:             'bg-red-500',
        principal:         'bg-purple-600',
        teacher:           'bg-blue-600',
        receptionist:      'bg-emerald-600',
        principal_helper:  'bg-teal-600',
        inventory_manager: 'bg-orange-500',
        doctor:            'bg-pink-600',
    };
    return map[role] ?? 'bg-gray-500';
}

function Flash({ flash }: { flash: { success?: string; error?: string; warning?: string } }) {
    const [visible, setVisible] = useState(true);
    const message = flash.success || flash.error || flash.warning;
    const type = flash.success ? 'success' : flash.error ? 'error' : 'warning';

    useEffect(() => { setVisible(true); }, [message]);

    if (!message || !visible) return null;

    const configs = {
        success: { cls: 'alert-success', Icon: CheckCircleIcon },
        error:   { cls: 'alert-error',   Icon: XCircleIcon },
        warning: { cls: 'alert-warning',  Icon: ExclamationTriangleIcon },
    };
    const { cls, Icon } = configs[type];

    return (
        <div className={`${cls} relative`}>
            <Icon className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span className="flex-1">{message}</span>
            <button onClick={() => setVisible(false)} className="ml-2 opacity-60 hover:opacity-100">
                <XMarkIcon className="w-4 h-4" />
            </button>
        </div>
    );
}

interface AppLayoutProps {
    children: React.ReactNode;
    title?: string;
}

export default function AppLayout({ children, title }: AppLayoutProps) {
    const pageProps = usePage<PageProps>().props;
    const { auth, flash, school, unread_notices_count, unread_messages_count } = pageProps;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [noticesOpen, setNoticesOpen] = useState(false);
    const [notices, setNotices] = useState<any[]>([]);
    const [loadingNotices, setLoadingNotices] = useState(false);
    const [todosOpen, setTodosOpen] = useState(false);
    const [todos, setTodos] = useState<any[]>([]);
    const [loadingTodos, setLoadingTodos] = useState(false);
    const [pendingTodosCount, setPendingTodosCount] = useState(0);
    const [unreadChat, setUnreadChat] = useState(unread_messages_count || 0);
    const [currentTime, setCurrentTime] = useState<string>('');
    const [searchOpen, setSearchOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<any[]>([]);
    const [searchLoading, setSearchLoading] = useState(false);
    const [expandedSections, setExpandedSections] = useState<Set<string>>(() => {
        const saved = localStorage.getItem('expandedSections');
        return saved ? new Set(JSON.parse(saved)) : new Set(['Academic', 'Timetable Management']);
    });
    const user = auth.user!;
    const currentPath = window.location.pathname;

    useEffect(() => {
        const fetchPendingTodos = async () => {
            try {
                const response = await fetch(route('todos.pending-count'));
                const data = await response.json();
                setPendingTodosCount(data.count || 0);
            } catch (error) {
                console.error('Failed to fetch pending todos:', error);
            }
        };
        fetchPendingTodos();
    }, []);

    useEffect(() => {
        const updateTime = () => {
            const now = new Date();
            setCurrentTime(now.toLocaleTimeString('en-GB', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            }));
        };

        updateTime();
        const interval = setInterval(updateTime, 1000);
        return () => clearInterval(interval);
    }, []);

    // Poll for unread messages every 15 seconds
    useEffect(() => {
        const fetchUnreadMessages = async () => {
            try {
                const response = await fetch(route('chat.unread'));
                const data = await response.json();
                setUnreadChat(data.count || 0);
            } catch (error) {
                console.error('Failed to fetch unread messages:', error);
            }
        };

        fetchUnreadMessages();
        const interval = setInterval(fetchUnreadMessages, 15000);
        return () => clearInterval(interval);
    }, []);

    const navGroups = getNavigation(user, pageProps);

    const isActive = (href: string) =>
        currentPath === new URL(href, window.location.origin).pathname;

    const toggleSection = (section: string) => {
        const newExpanded = new Set(expandedSections);
        if (newExpanded.has(section)) {
            newExpanded.delete(section);
        } else {
            newExpanded.add(section);
        }
        setExpandedSections(newExpanded);
        localStorage.setItem('expandedSections', JSON.stringify(Array.from(newExpanded)));
    };

    const handleLogout = () => {
        router.post(route('logout'));
    };

    const fetchNotices = async () => {
        if (loadingNotices) return;
        setLoadingNotices(true);
        try {
            const response = await fetch(route('notices.my'));
            const data = await response.json();
            setNotices(data.notices || []);
        } catch (error) {
            console.error('Failed to fetch notices:', error);
        }
        setLoadingNotices(false);
    };

    const handleMarkRead = async (noticeId: number) => {
        try {
            await fetch(route('notices.mark-read', noticeId), { method: 'POST' });
            setNotices(notices.filter(n => n.id !== noticeId));
        } catch (error) {
            console.error('Failed to mark notice as read:', error);
        }
    };

    const handleMarkAllRead = async () => {
        try {
            await fetch(route('notices.mark-all-read'), { method: 'POST' });
            setNotices([]);
            setNoticesOpen(false);
        } catch (error) {
            console.error('Failed to mark all notices as read:', error);
        }
    };

    const handleNoticesClick = () => {
        if (!noticesOpen) {
            fetchNotices();
        }
        setNoticesOpen(!noticesOpen);
    };

    const fetchTodos = async () => {
        if (loadingTodos) return;
        setLoadingTodos(true);
        try {
            const response = await fetch(route('todos.pending'));
            const data = await response.json();
            setTodos(data.todos || []);
        } catch (error) {
            console.error('Failed to fetch todos:', error);
            setTodos([]);
        }
        setLoadingTodos(false);
    };

    const handleTodosClick = () => {
        if (!todosOpen) {
            fetchTodos();
        }
        setTodosOpen(!todosOpen);
    };

    const handleMarkTodoComplete = async (todoId: number) => {
        try {
            await fetch(route('todos.mark-complete', todoId), { method: 'POST' });
            setTodos(todos.filter(t => t.id !== todoId));
            setPendingTodosCount(Math.max(0, pendingTodosCount - 1));
        } catch (error) {
            console.error('Failed to mark todo as complete:', error);
        }
    };

    const handleSearch = async (query: string) => {
        setSearchQuery(query);
        if (!query.trim()) {
            setSearchResults([]);
            return;
        }

        setSearchLoading(true);
        try {
            const response = await axios.get(route('search.global'), {
                params: { q: query }
            });
            setSearchResults(response.data.results || []);
        } catch (error) {
            console.error('Search failed:', error);
            setSearchResults([]);
        } finally {
            setSearchLoading(false);
        }
    };

    return (
        <div className="flex h-full w-full bg-gray-50">
            {/* Sidebar Overlay (mobile) */}
            {mobileOpen && (
                <div
                    className="fixed inset-0 z-20 bg-black/50 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside className={`sidebar transition-transform duration-300 ${mobileOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0`}>
                {/* Logo */}
                <div className="sidebar-logo">
                    <div className="w-9 h-9 bg-indigo-600 rounded-xl flex items-center justify-center text-white font-black text-lg">
                        K
                    </div>
                    <div>
                        <p className="text-white font-bold text-sm leading-tight">{school?.name ?? 'KORT School'}</p>
                        <p className="text-slate-500 text-[10px] leading-tight">Management System</p>
                    </div>
                    <button
                        className="ml-auto lg:hidden text-slate-400 hover:text-white"
                        onClick={() => setMobileOpen(false)}
                    >
                        <XMarkIcon className="w-5 h-5" />
                    </button>
                </div>

                {/* Navigation */}
                <nav className="flex-1 py-3 space-y-0.5">
                    {navGroups.map((group, gi) => {
                        const isExpanded = !group.section || expandedSections.has(group.section);
                        return (
                            <div key={gi}>
                                {group.section ? (
                                    <button
                                        onClick={() => toggleSection(group.section!)}
                                        className="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider hover:text-white transition-colors"
                                    >
                                        <span>{group.section}</span>
                                        <ChevronDownIcon
                                            className={`w-4 h-4 transition-transform ${
                                                isExpanded ? 'rotate-180' : ''
                                            }`}
                                        />
                                    </button>
                                ) : null}
                                {isExpanded && (
                                    <>
                                        {group.items.map((item) => {
                                            const active = isActive(item.href);
                                            return (
                                                <Link
                                                    key={item.href}
                                                    href={item.href}
                                                    className={`sidebar-link ${active ? 'active' : ''}`}
                                                    onClick={() => setMobileOpen(false)}
                                                >
                                                    <item.icon className="icon" />
                                                    <span>{item.label}</span>
                                                    {item.badge != null && item.badge > 0 && (
                                                        <span className="sidebar-badge">{item.badge}</span>
                                                    )}
                                                </Link>
                                            );
                                        })}
                                    </>
                                )}
                            </div>
                        );
                    })}
                </nav>

                {/* User section */}
                <div className="p-3 border-t border-white/5">
                    <div className="flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-white/5 transition-colors">
                        <span className={`avatar-sm ${avatarBg(user.role)}`}>
                            {user.name.charAt(0).toUpperCase()}
                        </span>
                        <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-white truncate">{user.name}</p>
                            <p className="text-[11px] text-slate-500 truncate">{user.role_label}</p>
                        </div>
                        <button
                            onClick={handleLogout}
                            className="text-slate-500 hover:text-white transition-colors"
                            title="Logout"
                        >
                            <ArrowRightOnRectangleIcon className="w-4.5 h-4.5" />
                        </button>
                    </div>
                </div>
            </aside>

            {/* Main */}
            <div className="main-content">
                {/* Topbar */}
                <header className="topbar no-print">
                    <div className="flex items-center gap-3">
                        <button
                            className="lg:hidden btn-ghost btn-icon"
                            onClick={() => setMobileOpen(true)}
                        >
                            <Bars3Icon className="w-5 h-5" />
                        </button>
                        {title && (
                            <h1 className="text-base font-semibold text-gray-800 hidden sm:block">{title}</h1>
                        )}
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Global Search - Only for principal and teachers */}
                        {(user.role === 'principal' || user.role === 'teacher') && (
                            <div className="relative hidden sm:block w-64">
                                <div className="relative">
                                    <input
                                        type="text"
                                        placeholder="Search students, teachers, classes..."
                                        value={searchQuery}
                                        onChange={(e) => handleSearch(e.target.value)}
                                        onFocus={() => setSearchOpen(true)}
                                        className="w-full px-3 py-2 pl-9 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    />
                                    <MagnifyingGlassIcon className="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" />
                                </div>

                                {/* Search Results Dropdown */}
                                {searchOpen && (
                                    <>
                                        <div className="fixed inset-0 z-10" onClick={() => setSearchOpen(false)} />
                                        <div className="absolute top-full left-0 right-0 mt-2 bg-white rounded-lg shadow-lg border border-gray-100 z-20 max-h-96 overflow-y-auto">
                                            {searchLoading ? (
                                                <div className="flex items-center justify-center py-8">
                                                    <div className="spinner" />
                                                </div>
                                            ) : searchResults.length === 0 && searchQuery ? (
                                                <div className="px-4 py-8 text-center text-gray-500 text-sm">
                                                    No results found for "{searchQuery}"
                                                </div>
                                            ) : searchResults.length === 0 ? (
                                                <div className="px-4 py-8 text-center text-gray-400 text-sm">
                                                    Start typing to search...
                                                </div>
                                            ) : (
                                                <div>
                                                    {/* Group results by type */}
                                                    {searchResults.filter(r => r.type === 'student').length > 0 && (
                                                        <div>
                                                            <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                                                👥 Students ({searchResults.filter(r => r.type === 'student').length})
                                                            </div>
                                                            {searchResults.filter(r => r.type === 'student').map((result) => (
                                                                <Link
                                                                    key={`${result.type}-${result.id}`}
                                                                    href={result.url}
                                                                    className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-b-0"
                                                                    onClick={() => setSearchOpen(false)}
                                                                >
                                                                    <span className="avatar-sm bg-blue-100 text-blue-600 text-xs font-semibold">
                                                                        {result.name.charAt(0).toUpperCase()}
                                                                    </span>
                                                                    <div className="flex-1 min-w-0">
                                                                        <p className="text-sm font-medium text-gray-900">{result.name}</p>
                                                                        <p className="text-xs text-gray-500">{result.class}</p>
                                                                    </div>
                                                                </Link>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {searchResults.filter(r => r.type === 'teacher').length > 0 && (
                                                        <div>
                                                            <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                                                🎓 Teachers ({searchResults.filter(r => r.type === 'teacher').length})
                                                            </div>
                                                            {searchResults.filter(r => r.type === 'teacher').map((result) => (
                                                                <Link
                                                                    key={`${result.type}-${result.id}`}
                                                                    href={result.url}
                                                                    className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-b-0"
                                                                    onClick={() => setSearchOpen(false)}
                                                                >
                                                                    <span className="avatar-sm bg-purple-100 text-purple-600 text-xs font-semibold">
                                                                        {result.name.charAt(0).toUpperCase()}
                                                                    </span>
                                                                    <div className="flex-1 min-w-0">
                                                                        <p className="text-sm font-medium text-gray-900">{result.name}</p>
                                                                        <p className="text-xs text-gray-500">{result.subject}</p>
                                                                    </div>
                                                                </Link>
                                                            ))}
                                                        </div>
                                                    )}

                                                    {searchResults.filter(r => r.type === 'class').length > 0 && (
                                                        <div>
                                                            <div className="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide bg-gray-50 border-b border-gray-100">
                                                                🏫 Classes ({searchResults.filter(r => r.type === 'class').length})
                                                            </div>
                                                            {searchResults.filter(r => r.type === 'class').map((result) => (
                                                                <Link
                                                                    key={`${result.type}-${result.id}`}
                                                                    href={result.url}
                                                                    className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-b-0"
                                                                    onClick={() => setSearchOpen(false)}
                                                                >
                                                                    <span className="avatar-sm bg-green-100 text-green-600 text-xs font-semibold">
                                                                        {result.name.charAt(0).toUpperCase()}
                                                                    </span>
                                                                    <div className="flex-1 min-w-0">
                                                                        <p className="text-sm font-medium text-gray-900">{result.name}</p>
                                                                        <p className="text-xs text-gray-500">{result.info}</p>
                                                                    </div>
                                                                </Link>
                                                            ))}
                                                        </div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </>
                                )}
                            </div>
                        )}

                        {/* Todo List */}
                        <div className="relative">
                            <button
                                onClick={handleTodosClick}
                                className="btn-ghost btn-icon relative"
                                title="To-Do List"
                            >
                                <ListBulletIcon className="w-5 h-5 text-gray-500" />
                                {pendingTodosCount > 0 && (
                                    <span className="absolute top-1 right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-[10px] font-bold text-white bg-amber-500 rounded-full">
                                        {pendingTodosCount > 99 ? '99+' : pendingTodosCount}
                                    </span>
                                )}
                            </button>

                            {/* Todos Dropdown */}
                            {todosOpen && (
                                <>
                                    <div className="fixed inset-0 z-10" onClick={() => setTodosOpen(false)} />
                                    <div className="absolute right-0 mt-1.5 w-96 bg-white rounded-xl shadow-lg border border-gray-100 z-20 max-h-96 flex flex-col">
                                        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                            <h3 className="font-semibold text-gray-900">Pending Tasks</h3>
                                            <Link
                                                href={route('todos.index')}
                                                className="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
                                                onClick={() => setTodosOpen(false)}
                                            >
                                                View All
                                            </Link>
                                        </div>

                                        {loadingTodos ? (
                                            <div className="flex items-center justify-center py-8">
                                                <div className="spinner" />
                                            </div>
                                        ) : todos.length === 0 ? (
                                            <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                                                <ListBulletIcon className="w-8 h-8 mb-2 opacity-50" />
                                                <p className="text-sm">No pending tasks</p>
                                            </div>
                                        ) : (
                                            <div className="overflow-y-auto">
                                                {todos.map((todo) => (
                                                    <div
                                                        key={todo.id}
                                                        className="px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors flex items-start gap-3 group"
                                                    >
                                                        <div className="flex-1 min-w-0 pt-0.5">
                                                            <p className="text-sm font-medium text-gray-900 line-clamp-2">{todo.title}</p>
                                                            <p className="text-xs text-gray-500 mt-0.5">
                                                                {todo.due_date && `Due: ${todo.due_date}`}
                                                                {todo.priority && ` • ${todo.priority}`}
                                                            </p>
                                                        </div>
                                                        <button
                                                            onClick={() => handleMarkTodoComplete(todo.id)}
                                                            className="opacity-0 group-hover:opacity-100 flex-shrink-0 text-xs text-emerald-600 hover:text-emerald-700 font-medium transition-opacity whitespace-nowrap"
                                                        >
                                                            Complete
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        <div className="px-4 py-3 border-t border-gray-100 bg-gray-50/50">
                                            <Link
                                                href={route('todos.create')}
                                                className="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
                                                onClick={() => setTodosOpen(false)}
                                            >
                                                + Add New Task
                                            </Link>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>

                        {/* Notifications with Teacher Info */}
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                {/* Chat Icon */}
                                <Link
                                    href={route('chat.index')}
                                    className="btn-ghost btn-icon relative"
                                    title="Messages"
                                >
                                    <ChatBubbleLeftRightIcon className="w-5 h-5 text-gray-500" />
                                    {unreadChat > 0 && (
                                        <span className="absolute top-1 right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                            {unreadChat > 99 ? '99+' : unreadChat}
                                        </span>
                                    )}
                                </Link>

                                {/* Notifications Icon */}
                                <div className="relative">
                                <button
                                    onClick={handleNoticesClick}
                                    className="btn-ghost btn-icon relative"
                                    title="Notifications"
                                >
                                    <BellIcon className="w-5 h-5 text-gray-500" />
                                    {unread_notices_count > 0 && (
                                        <span className="absolute top-1 right-1 flex items-center justify-center min-w-[20px] h-5 px-1 text-[10px] font-bold text-white bg-red-500 rounded-full">
                                            {unread_notices_count > 99 ? '99+' : unread_notices_count}
                                        </span>
                                    )}
                                </button>

                            {/* Notices Dropdown */}
                            {noticesOpen && (
                                <>
                                    <div className="fixed inset-0 z-10" onClick={() => setNoticesOpen(false)} />
                                    <div className="absolute right-0 mt-1.5 w-96 bg-white rounded-xl shadow-lg border border-gray-100 z-20 max-h-96 flex flex-col">
                                        <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                                            <h3 className="font-semibold text-gray-900">Notifications</h3>
                                            {notices.length > 0 && (
                                                <button
                                                    onClick={handleMarkAllRead}
                                                    className="text-xs text-indigo-600 hover:text-indigo-700 font-medium"
                                                >
                                                    Mark all read
                                                </button>
                                            )}
                                        </div>

                                        {loadingNotices ? (
                                            <div className="flex items-center justify-center py-8">
                                                <div className="spinner" />
                                            </div>
                                        ) : notices.length === 0 ? (
                                            <div className="flex flex-col items-center justify-center py-8 text-gray-400">
                                                <BellIcon className="w-8 h-8 mb-2 opacity-50" />
                                                <p className="text-sm">No new notifications</p>
                                            </div>
                                        ) : (
                                            <div className="overflow-y-auto">
                                                {notices.map(notice => (
                                                    <div
                                                        key={notice.id}
                                                        className="px-4 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors flex items-start gap-3 group"
                                                    >
                                                        <div className="flex-1 min-w-0 pt-0.5">
                                                            <p className="text-sm font-medium text-gray-900">{notice.title}</p>
                                                            <p className="text-xs text-gray-500 mt-0.5 line-clamp-2">{notice.body}</p>
                                                            <p className="text-xs text-gray-400 mt-1">
                                                                {notice.posted_by} • {notice.created_at}
                                                            </p>
                                                        </div>
                                                        <button
                                                            onClick={() => handleMarkRead(notice.id)}
                                                            className="opacity-0 group-hover:opacity-100 flex-shrink-0 text-xs text-indigo-600 hover:text-indigo-700 font-medium transition-opacity whitespace-nowrap"
                                                        >
                                                            Mark read
                                                        </button>
                                                    </div>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                            </div>
                            </div>

                            {/* Subject/Role and Clock - Show for teachers */}
                            {user.role === 'teacher' && (
                                <div className="hidden sm:flex items-center gap-2 px-3 py-2">
                                    <p className="text-sm font-medium text-gray-700">{user.role_label}</p>
                                    <p className="text-sm font-mono text-gray-600">🕐 {currentTime}</p>
                                </div>
                            )}
                        </div>

                        {/* User menu */}
                        <div className="relative">
                            <button
                                onClick={() => setUserMenuOpen(!userMenuOpen)}
                                className="flex items-center gap-2 pl-2 pr-3 py-1.5 rounded-xl hover:bg-gray-100 transition-colors"
                            >
                                <span className={`avatar-sm ${avatarBg(user.role)}`}>
                                    {user.name.charAt(0).toUpperCase()}
                                </span>
                                <div className="hidden sm:block text-left">
                                    <p className="text-sm font-semibold text-gray-800 leading-tight">{user.name}</p>
                                    <p className="text-[11px] text-gray-400">{user.role_label}</p>
                                </div>
                                <ChevronDownIcon className={`w-3.5 h-3.5 text-gray-400 transition-transform ${userMenuOpen ? 'rotate-180' : ''}`} />
                            </button>

                            {userMenuOpen && (
                                <>
                                    <div className="fixed inset-0 z-10" onClick={() => setUserMenuOpen(false)} />
                                    <div className="absolute right-0 mt-1.5 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-20">
                                        <button
                                            onClick={handleLogout}
                                            className="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                        >
                                            <ArrowRightOnRectangleIcon className="w-4 h-4" />
                                            Sign out
                                        </button>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </header>

                {/* Content */}
                <main className="content-area">
                    {(flash?.success || flash?.error || flash?.warning) && (
                        <div className="mb-5">
                            <Flash flash={flash} />
                        </div>
                    )}
                    {children}
                </main>
            </div>
        </div>
    );
}
