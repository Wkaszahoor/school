import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { UserIcon, LockClosedIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Props extends PageProps {
    user: User;
}

export default function ProfileEdit({ user }: Props) {
    const [currentTime, setCurrentTime] = useState<string>('');

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

    const getGreeting = () => {
        const hour = new Date().getHours();
        if (hour < 12) return 'Good Morning';
        if (hour < 17) return 'Good Afternoon';
        return 'Good Evening';
    };

    const { data: nameData, setData: setNameData, post: postName, processing: nameProcessing, errors: nameErrors, reset: resetName } = useForm({
        name: user.name,
    });

    const { data: passwordData, setData: setPasswordData, post: postPassword, processing: passwordProcessing, errors: passwordErrors, reset: resetPassword } = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleNameSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postName(route('teacher.profile.update-name'), {
            onSuccess: () => {
                resetName();
            },
        });
    };

    const handlePasswordSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        postPassword(route('teacher.profile.update-password'), {
            onSuccess: () => {
                resetPassword();
            },
        });
    };

    return (
        <AppLayout title="My Profile">
            <Head title="My Profile" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">
                        {getGreeting()}, {user.name.split(' ')[0]}!
                    </h1>
                    <div className="flex items-center gap-3 mt-1">
                        <p className="page-subtitle">
                            {new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
                        </p>
                        <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-700">
                            🕐 {currentTime}
                        </span>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Profile Info Card */}
                <div className="card">
                    <div className="card-body">
                        <div className="flex items-center justify-center mb-4">
                            <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                <UserIcon className="w-8 h-8 text-blue-600" />
                            </div>
                        </div>
                        <h2 className="text-center text-xl font-bold text-gray-900">{user.name}</h2>
                        <p className="text-center text-sm text-gray-600 mt-2">{user.email}</p>
                        <div className="mt-4 pt-4 border-t border-gray-200">
                            <p className="text-xs font-semibold text-gray-500 uppercase">Account Status</p>
                            <p className="text-sm text-green-600 font-semibold mt-1">✓ Active</p>
                        </div>
                    </div>
                </div>

                {/* Update Name & Password Forms */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Update Name */}
                    <div className="card">
                        <div className="card-header">
                            <div className="flex items-center gap-2">
                                <UserIcon className="w-5 h-5 text-blue-600" />
                                <p className="card-title">Update Name</p>
                            </div>
                        </div>
                        <form onSubmit={handleNameSubmit} className="card-body space-y-4">
                            <div>
                                <label className="form-label">Full Name *</label>
                                <input
                                    type="text"
                                    value={nameData.name}
                                    onChange={(e) => setNameData('name', e.target.value)}
                                    className="form-input"
                                    placeholder="Enter your full name"
                                    required
                                />
                                {nameErrors.name && <p className="text-red-500 text-xs mt-1">{nameErrors.name}</p>}
                            </div>

                            <div className="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    disabled={nameProcessing}
                                    className="btn-primary"
                                >
                                    {nameProcessing ? 'Updating...' : 'Update Name'}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Update Password */}
                    <div className="card">
                        <div className="card-header">
                            <div className="flex items-center gap-2">
                                <LockClosedIcon className="w-5 h-5 text-amber-600" />
                                <p className="card-title">Change Password</p>
                            </div>
                        </div>
                        <form onSubmit={handlePasswordSubmit} className="card-body space-y-4">
                            <div>
                                <label className="form-label">Current Password *</label>
                                <input
                                    type="password"
                                    value={passwordData.current_password}
                                    onChange={(e) => setPasswordData('current_password', e.target.value)}
                                    className="form-input"
                                    placeholder="Enter current password"
                                    required
                                />
                                {passwordErrors.current_password && (
                                    <p className="text-red-500 text-xs mt-1">{passwordErrors.current_password}</p>
                                )}
                            </div>

                            <div>
                                <label className="form-label">New Password *</label>
                                <input
                                    type="password"
                                    value={passwordData.password}
                                    onChange={(e) => setPasswordData('password', e.target.value)}
                                    className="form-input"
                                    placeholder="Enter new password (minimum 8 characters)"
                                    required
                                />
                                {passwordErrors.password && (
                                    <p className="text-red-500 text-xs mt-1">{passwordErrors.password}</p>
                                )}
                            </div>

                            <div>
                                <label className="form-label">Confirm Password *</label>
                                <input
                                    type="password"
                                    value={passwordData.password_confirmation}
                                    onChange={(e) => setPasswordData('password_confirmation', e.target.value)}
                                    className="form-input"
                                    placeholder="Confirm new password"
                                    required
                                />
                                {passwordErrors.password_confirmation && (
                                    <p className="text-red-500 text-xs mt-1">{passwordErrors.password_confirmation}</p>
                                )}
                            </div>

                            <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p className="text-xs text-blue-900">
                                    <strong>Security tip:</strong> Use a strong password with mix of uppercase, lowercase, numbers, and symbols.
                                </p>
                            </div>

                            <div className="flex gap-3 pt-2">
                                <button
                                    type="submit"
                                    disabled={passwordProcessing}
                                    className="btn-primary"
                                >
                                    {passwordProcessing ? 'Updating...' : 'Change Password'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
