import React, { useState, useEffect } from 'react';
import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import {
    PlusIcon, EnvelopeIcon, ChatBubbleLeftRightIcon,
} from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
    role_label: string;
}

interface Conversation {
    other_user_id: number;
    other_user: User & { phone: string | null };
    last_message: string;
    last_message_time: string;
    unread_count: number;
}

interface ChatIndexProps extends PageProps {
    conversations: Conversation[];
}

export default function ChatIndex({ conversations }: ChatIndexProps) {
    const [searchTerm, setSearchTerm] = useState('');
    const [users, setUsers] = useState<User[]>([]);
    const [showNewMessage, setShowNewMessage] = useState(false);
    const [searchUsers, setSearchUsers] = useState('');

    useEffect(() => {
        if (showNewMessage) {
            fetch(route('chat.users'))
                .then(r => r.json())
                .then(data => setUsers(data));
        }
    }, [showNewMessage]);

    const filteredConversations = conversations.filter(c =>
        c.other_user.name.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const filteredUsers = users.filter(u =>
        u.name.toLowerCase().includes(searchUsers.toLowerCase())
    );

    const formatTime = (date: string) => {
        const d = new Date(date);
        const today = new Date();
        if (d.toDateString() === today.toDateString()) {
            return d.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        }
        return d.toLocaleDateString('en-GB', { month: 'short', day: 'numeric' });
    };

    const openWhatsApp = (phone: string | null) => {
        if (!phone) return;
        const digits = phone.replace(/\D/g, '');
        window.open(`https://wa.me/${digits}?text=Hi from KORT School`, '_blank');
    };

    return (
        <AppLayout title="Messages">
            <Head title="Messages" />
            <div className="p-6">
                <div className="flex items-center justify-between mb-6">
                    <h1 className="text-3xl font-bold">Messages</h1>
                    <button onClick={() => setShowNewMessage(!showNewMessage)} className="btn-primary">
                        <PlusIcon className="w-5 h-5" /> New Message
                    </button>
                </div>

                {showNewMessage && (
                    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div className="bg-white rounded-2xl p-6 max-w-md w-full">
                            <h2 className="text-xl font-bold mb-4">Start New Message</h2>
                            <input type="text" placeholder="Search users..." value={searchUsers}
                                onChange={(e) => setSearchUsers(e.target.value)} className="form-input mb-4" />
                            <div className="space-y-2 max-h-64 overflow-y-auto">
                                {filteredUsers.map(user => (
                                    <Link key={user.id} href={route('chat.show', user.id)}
                                        className="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-100">
                                        <div className="w-10 h-10 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold">
                                            {user.name.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="font-medium">{user.name}</p>
                                            <p className="text-xs text-gray-500">{user.role_label}</p>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                            <button onClick={() => setShowNewMessage(false)} className="btn-secondary w-full mt-4">
                                Cancel
                            </button>
                        </div>
                    </div>
                )}

                <div className="mb-6">
                    <input type="text" placeholder="Search conversations..." value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)} className="form-input" />
                </div>

                {filteredConversations.length === 0 ? (
                    <div className="text-center py-12">
                        <ChatBubbleLeftRightIcon className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500">No conversations yet</p>
                    </div>
                ) : (
                    <div className="space-y-2">
                        {filteredConversations.map(conv => (
                            <Link key={conv.other_user_id} href={route('chat.show', conv.other_user_id)}
                                className="flex items-center justify-between p-4 rounded-xl hover:bg-gray-50 border">
                                <div className="flex items-center gap-4 flex-1 min-w-0">
                                    <div className="w-12 h-12 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">
                                        {conv.other_user.name.charAt(0)}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="font-semibold">{conv.other_user.name}</p>
                                        <p className="text-sm text-gray-500">{conv.last_message}</p>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2 flex-shrink-0 ml-2">
                                    <button onClick={(e) => {
                                        e.preventDefault();
                                        openWhatsApp(conv.other_user.phone);
                                    }} className="p-2 text-green-600 hover:bg-green-50 rounded-lg">
                                        <ChatBubbleLeftRightIcon className="w-5 h-5" />
                                    </button>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
