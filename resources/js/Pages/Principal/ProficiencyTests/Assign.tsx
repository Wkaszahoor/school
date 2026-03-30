import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface User { id: number; name: string; email: string; }
interface Props extends PageProps {
    test: { id: number; title: string };
    teachers: User[];
    students: User[];
    assigned: number[];
}

export default function AssignTest({ test, teachers, students, assigned }: Props) {
    const { flash } = usePage().props as any;
    const [tab, setTab] = useState<'teachers' | 'students'>('teachers');
    const [search, setSearch] = useState('');
    const [selectedIds, setSelectedIds] = useState<number[]>([]);

    const { data, setData, post, processing, errors } = useForm({
        user_ids: [] as number[],
        due_date: '',
    });

    const list = (tab === 'teachers' ? teachers : students).filter(u =>
        u.name.toLowerCase().includes(search.toLowerCase()) ||
        u.email.toLowerCase().includes(search.toLowerCase())
    );

    const toggle = (id: number) => {
        const next = selectedIds.includes(id)
            ? selectedIds.filter(x => x !== id)
            : [...selectedIds, id];
        setSelectedIds(next);
        setData('user_ids', next);
    };

    const selectAll = () => {
        const ids = list.filter(u => !assigned.includes(u.id)).map(u => u.id);
        const next = [...new Set([...selectedIds, ...ids])];
        setSelectedIds(next);
        setData('user_ids', next);
    };

    const clearAll = () => { setSelectedIds([]); setData('user_ids', []); };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('principal.professional-development.proficiency-tests.store-assignment', test.id));
    };

    return (
        <AppLayout title={`Assign – ${test.title}`}>
            <Head title={`Assign: ${test.title}`} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Assign Test</h1>
                    <p className="page-subtitle">{test.title}</p>
                </div>
                <a href={route('principal.professional-development.proficiency-tests.show', test.id)} className="btn btn-secondary">
                    Back to Test
                </a>
            </div>

            {flash?.success && (
                <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">{flash.success}</div>
            )}

            <form onSubmit={submit} className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* User Selector */}
                <div className="lg:col-span-2 card">
                    {/* Tabs */}
                    <div className="flex border-b border-gray-200 mb-4">
                        {(['teachers', 'students'] as const).map(t => (
                            <button key={t} type="button" onClick={() => setTab(t)}
                                className={`px-5 py-3 text-sm font-medium border-b-2 -mb-px capitalize transition ${
                                    tab === t ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500'
                                }`}>
                                {t} ({(t === 'teachers' ? teachers : students).length})
                            </button>
                        ))}
                    </div>

                    <div className="flex gap-2 mb-4">
                        <input type="text" value={search} onChange={e => setSearch(e.target.value)}
                            className="form-input flex-1" placeholder="Search name or email..." />
                        <button type="button" onClick={selectAll} className="btn btn-secondary text-sm">Select All</button>
                        <button type="button" onClick={clearAll} className="btn btn-secondary text-sm">Clear</button>
                    </div>

                    <div className="space-y-2 max-h-96 overflow-y-auto">
                        {list.map(user => {
                            const isAssigned = assigned.includes(user.id);
                            const isSelected = selectedIds.includes(user.id);
                            return (
                                <label key={user.id}
                                    className={`flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition ${
                                        isAssigned ? 'bg-gray-50 border-gray-200 opacity-60 cursor-not-allowed' :
                                        isSelected ? 'bg-blue-50 border-blue-300' : 'bg-white border-gray-200 hover:border-blue-200'
                                    }`}>
                                    <input type="checkbox"
                                        checked={isSelected || isAssigned}
                                        disabled={isAssigned}
                                        onChange={() => !isAssigned && toggle(user.id)}
                                        className="w-4 h-4" />
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium text-gray-900 text-sm">{user.name}</p>
                                        <p className="text-xs text-gray-500 truncate">{user.email}</p>
                                    </div>
                                    {isAssigned && (
                                        <span className="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Assigned</span>
                                    )}
                                </label>
                            );
                        })}
                        {list.length === 0 && (
                            <p className="text-center text-gray-500 py-6">No {tab} found.</p>
                        )}
                    </div>
                </div>

                {/* Assignment Options */}
                <div className="space-y-4">
                    <div className="card">
                        <h2 className="font-semibold text-gray-900 mb-4">Assignment Options</h2>
                        <div className="mb-4">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Due Date (optional)</label>
                            <input type="date" value={data.due_date}
                                onChange={e => setData('due_date', e.target.value)}
                                className="form-input" />
                            {errors.due_date && <p className="text-sm text-red-600 mt-1">{errors.due_date}</p>}
                        </div>

                        <div className="p-3 bg-blue-50 rounded-lg mb-4">
                            <p className="text-sm font-medium text-blue-800">
                                {selectedIds.length} user{selectedIds.length !== 1 ? 's' : ''} selected
                            </p>
                            {errors.user_ids && <p className="text-sm text-red-600 mt-1">{errors.user_ids}</p>}
                        </div>

                        <button type="submit" className="btn btn-primary w-full" disabled={processing || selectedIds.length === 0}>
                            {processing ? 'Assigning…' : `Assign to ${selectedIds.length} User(s)`}
                        </button>
                    </div>

                    <div className="card text-sm text-gray-600 space-y-2">
                        <p className="font-medium text-gray-800">ℹ️ How it works</p>
                        <p>Assigned users will see this test in their <strong>My Tests</strong> dashboard.</p>
                        <p>They can start the timed test at any time before the due date.</p>
                        <p>Results are automatically calculated. Essay questions require manual grading.</p>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
