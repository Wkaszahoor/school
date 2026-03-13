import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { EyeIcon, EyeSlashIcon, LockClosedIcon, EnvelopeIcon } from '@heroicons/react/24/outline';

export default function Login() {
    const [showPassword, setShowPassword] = React.useState(false);

    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('login.post'));
    };

    return (
        <>
            <Head title="Sign In" />
            <div className="min-h-screen flex">
                {/* Left — Branding */}
                <div className="hidden lg:flex lg:w-1/2 relative overflow-hidden"
                     style={{ background: 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 40%, #312e81 100%)' }}>
                    {/* Decorative blobs */}
                    <div className="absolute top-1/4 -left-16 w-72 h-72 bg-indigo-600/30 rounded-full blur-3xl" />
                    <div className="absolute bottom-1/4 right-8 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl" />
                    <div className="absolute top-3/4 left-1/4 w-40 h-40 bg-purple-600/20 rounded-full blur-2xl" />

                    <div className="relative z-10 flex flex-col items-start justify-center px-16 text-white">
                        {/* Logo */}
                        <div className="flex items-center gap-4 mb-12">
                            <div className="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-3xl font-black shadow-xl">
                                K
                            </div>
                            <div>
                                <h1 className="text-2xl font-bold">KORT School</h1>
                                <p className="text-slate-400 text-sm">Management System</p>
                            </div>
                        </div>

                        <h2 className="text-4xl font-extrabold leading-tight mb-4 text-balance">
                            Empowering Education<br />Through Technology
                        </h2>
                        <p className="text-slate-400 text-base leading-relaxed max-w-md">
                            A comprehensive platform to manage students, academics, attendance, discipline, medical records, and more — all in one place.
                        </p>

                        {/* Feature pills */}
                        <div className="flex flex-wrap gap-2 mt-10">
                            {['Students', 'Attendance', 'Results', 'Discipline', 'Inventory', 'Medical'].map(f => (
                                <span key={f} className="px-3 py-1 bg-white/10 rounded-full text-xs font-medium text-slate-300">
                                    {f}
                                </span>
                            ))}
                        </div>

                        {/* Role cards */}
                        <div className="grid grid-cols-2 gap-3 mt-10 w-full max-w-sm">
                            {[
                                { role: 'Admin', color: 'bg-red-500/20 border-red-500/30' },
                                { role: 'Principal', color: 'bg-purple-500/20 border-purple-500/30' },
                                { role: 'Teacher', color: 'bg-blue-500/20 border-blue-500/30' },
                                { role: 'Doctor', color: 'bg-pink-500/20 border-pink-500/30' },
                            ].map(({ role, color }) => (
                                <div key={role}
                                     className={`${color} border rounded-xl px-3 py-2 text-xs font-medium text-slate-300`}>
                                    {role}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Right — Login form */}
                <div className="flex-1 flex items-center justify-center bg-gray-50 px-6 py-12">
                    <div className="w-full max-w-sm">
                        {/* Mobile logo */}
                        <div className="flex items-center gap-3 mb-8 lg:hidden">
                            <div className="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center text-2xl font-black text-white">
                                K
                            </div>
                            <span className="text-xl font-bold text-gray-900">KORT School</span>
                        </div>

                        <div className="mb-8">
                            <h2 className="text-2xl font-bold text-gray-900">Welcome back</h2>
                            <p className="text-gray-500 text-sm mt-1">Sign in to your account to continue</p>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-5">
                            {/* Email */}
                            <div className="form-group">
                                <label className="form-label">Email address</label>
                                <div className="relative">
                                    <EnvelopeIcon className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-gray-400 pointer-events-none" />
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={e => setData('email', e.target.value)}
                                        className={`form-input pl-10 ${errors.email ? 'border-red-400 focus:border-red-400' : ''}`}
                                        placeholder="name@school.edu"
                                        autoComplete="email"
                                        autoFocus
                                        required
                                    />
                                </div>
                                {errors.email && <p className="form-error">{errors.email}</p>}
                            </div>

                            {/* Password */}
                            <div className="form-group">
                                <label className="form-label">Password</label>
                                <div className="relative">
                                    <LockClosedIcon className="absolute left-3.5 top-1/2 -translate-y-1/2 w-4.5 h-4.5 text-gray-400 pointer-events-none" />
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={e => setData('password', e.target.value)}
                                        className={`form-input pl-10 pr-10 ${errors.password ? 'border-red-400' : ''}`}
                                        placeholder="Enter your password"
                                        autoComplete="current-password"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                                    >
                                        {showPassword
                                            ? <EyeSlashIcon className="w-4.5 h-4.5" />
                                            : <EyeIcon className="w-4.5 h-4.5" />
                                        }
                                    </button>
                                </div>
                                {errors.password && <p className="form-error">{errors.password}</p>}
                            </div>

                            {/* Remember */}
                            <div className="flex items-center">
                                <label className="flex items-center gap-2 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={e => setData('remember', e.target.checked)}
                                        className="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <span className="text-sm text-gray-600">Remember me</span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="btn-primary w-full btn-lg"
                            >
                                {processing ? (
                                    <>
                                        <span className="spinner w-4 h-4 border-white/30 border-t-white" />
                                        Signing in…
                                    </>
                                ) : (
                                    'Sign in'
                                )}
                            </button>
                        </form>

                        <p className="text-center text-xs text-gray-400 mt-8">
                            KORT School Management System &copy; {new Date().getFullYear()}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
