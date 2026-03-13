import React, { useState, useRef, useEffect } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { ArrowLeftIcon, EnvelopeIcon, ChatBubbleLeftRightIcon, PaperAirplaneIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Message {
    id: number;
    sender_id: number;
    recipient_id: number;
    subject: string;
    message_body: string;
    created_at: string;
    sender_name: string;
    is_sent: boolean;
}

interface User {
    id: number;
    name: string;
    email: string;
    role_label: string;
    phone: string | null;
}

interface ChatShowProps extends PageProps {
    other_user: User;
    messages: Message[];
}

export default function ChatShow({ other_user, messages }: ChatShowProps) {
    const { data, setData, post, processing } = useForm({
        recipient_id: other_user.id,
        subject: 'Message',
        message_body: '',
    });
    const [localMessages, setLocalMessages] = useState(messages);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [localMessages]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.message_body.trim()) return;

        post(route('chat.store'), {
            onSuccess: () => {
                setLocalMessages([...localMessages, {
                    id: Date.now(),
                    sender_id: 0,
                    recipient_id: other_user.id,
                    subject: data.subject,
                    message_body: data.message_body,
                    created_at: new Date().toISOString(),
                    sender_name: 'You',
                    is_sent: true,
                }]);
                setData('message_body', '');
            },
        });
    };

    const openWhatsApp = () => {
        if (!other_user.phone) return;
        const digits = other_user.phone.replace(/\D/g, '');
        window.open(`https://wa.me/${digits}?text=Hi from KORT School`, '_blank');
    };

    const openEmail = () => {
        window.location.href = `mailto:${other_user.email}?subject=Message from KORT School`;
    };

    return (
        <AppLayout title={`Chat with ${other_user.name}`}>
            <Head title={`Chat with ${other_user.name}`} />

            <div className="h-full flex flex-col">
                <div className="border-b border-gray-200 p-4 flex items-center justify-between">
                    <Link href={route('chat.index')} className="flex items-center gap-2 text-indigo-600 hover:text-indigo-700">
                        <ArrowLeftIcon className="w-5 h-5" />
                        Back
                    </Link>
                    <div className="text-center flex-1">
                        <p className="font-semibold text-gray-900">{other_user.name}</p>
                        <p className="text-xs text-gray-500">{other_user.role_label}</p>
                    </div>
                    <div className="flex items-center gap-2">
                        {other_user.phone && (
                            <button onClick={openWhatsApp} className="p-2 text-green-600 hover:bg-green-50 rounded-lg" title="WhatsApp">
                                <ChatBubbleLeftRightIcon className="w-5 h-5" />
                            </button>
                        )}
                        <button onClick={openEmail} className="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Email">
                            <EnvelopeIcon className="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <div className="flex-1 overflow-y-auto p-4 space-y-3">
                    {localMessages.length === 0 ? (
                        <div className="text-center py-12">
                            <p className="text-gray-500">No messages yet. Start the conversation!</p>
                        </div>
                    ) : (
                        localMessages.map(msg => (
                            <div key={msg.id} className={`flex ${msg.is_sent ? 'justify-end' : 'justify-start'}`}>
                                <div className={`max-w-xs px-4 py-2 rounded-lg ${msg.is_sent ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'}`}>
                                    <p className="text-sm">{msg.message_body}</p>
                                    <p className={`text-xs mt-1 ${msg.is_sent ? 'text-indigo-100' : 'text-gray-500'}`}>
                                        {new Date(msg.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' })}
                                    </p>
                                </div>
                            </div>
                        ))
                    )}
                    <div ref={messagesEndRef} />
                </div>

                <div className="border-t border-gray-200 p-4">
                    <form onSubmit={handleSubmit} className="flex gap-2">
                        <textarea value={data.message_body} onChange={(e) => setData('message_body', e.target.value)}
                            placeholder="Type a message..." className="form-input flex-1 resize-none" rows={3} />
                        <button type="submit" disabled={processing || !data.message_body.trim()} className="btn-primary h-fit">
                            <PaperAirplaneIcon className="w-5 h-5" />
                        </button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
