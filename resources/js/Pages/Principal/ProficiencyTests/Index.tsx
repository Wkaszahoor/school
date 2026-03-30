import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';

interface Test {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number;
    passing_score: number;
    total_marks: number;
    status: string;
    assignments_count: number;
    creator: { id: number; name: string };
    created_at: string;
}

interface Props extends PageProps {
    tests: { data: Test[]; current_page: number; last_page: number; total: number };
}

const statusColors: Record<string, string> = {
    active:   'bg-green-100 text-green-800',
    draft:    'bg-gray-100 text-gray-800',
    archived: 'bg-red-100 text-red-800',
};

export default function ProficiencyTestsIndex({ tests }: Props) {
    const { flash } = usePage().props as any;

    const deleteTest = (id: number, title: string) => {
        if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
        router.delete(route('principal.professional-development.proficiency-tests.destroy', id));
    };

    return (
        <AppLayout title="English Proficiency Tests">
            <Head title="Proficiency Tests" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">English Proficiency Tests</h1>
                    <p className="page-subtitle">Create and manage IELTS-style proficiency tests for teachers and students</p>
                </div>
                <Link href={route('principal.professional-development.proficiency-tests.create')} className="btn btn-primary">
                    + Create Test
                </Link>
            </div>

            {flash?.success && (
                <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">{flash.success}</div>
            )}

            {tests.data.length > 0 ? (
                <div className="card overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-gray-50 border-b-2 border-gray-200">
                                <tr>
                                    <th className="text-left py-3 px-4 font-semibold text-gray-700">Title</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Duration</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Marks</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Pass %</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Assigned</th>
                                    <th className="text-center py-3 px-4 font-semibold text-gray-700">Status</th>
                                    <th className="text-right py-3 px-4 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {tests.data.map((test, idx) => (
                                    <tr key={test.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                                        <td className="py-3 px-4">
                                            <p className="font-medium text-gray-900">{test.title}</p>
                                            {test.description && <p className="text-xs text-gray-500 mt-0.5 line-clamp-1">{test.description}</p>}
                                            <p className="text-xs text-gray-400 mt-0.5">By {test.creator.name}</p>
                                        </td>
                                        <td className="text-center py-3 px-4 text-gray-600">{test.duration_minutes} min</td>
                                        <td className="text-center py-3 px-4 text-gray-600">{test.total_marks}</td>
                                        <td className="text-center py-3 px-4 text-gray-600">{test.passing_score}%</td>
                                        <td className="text-center py-3 px-4">
                                            <span className="font-medium text-gray-900">{test.assignments_count}</span>
                                        </td>
                                        <td className="text-center py-3 px-4">
                                            <span className={`px-2 py-1 rounded-full text-xs font-medium capitalize ${statusColors[test.status] ?? 'bg-gray-100 text-gray-700'}`}>
                                                {test.status}
                                            </span>
                                        </td>
                                        <td className="text-right py-3 px-4">
                                            <div className="flex gap-1 justify-end flex-wrap">
                                                <Link href={route('principal.professional-development.proficiency-tests.show', test.id)}
                                                    className="px-3 py-1 bg-blue-500 text-white rounded text-xs font-medium hover:bg-blue-600">
                                                    View
                                                </Link>
                                                <Link href={route('principal.professional-development.proficiency-tests.assign', test.id)}
                                                    className="px-3 py-1 bg-purple-500 text-white rounded text-xs font-medium hover:bg-purple-600">
                                                    Assign
                                                </Link>
                                                <Link href={route('principal.professional-development.proficiency-tests.edit', test.id)}
                                                    className="px-3 py-1 bg-amber-500 text-white rounded text-xs font-medium hover:bg-amber-600">
                                                    Edit
                                                </Link>
                                                <button onClick={() => deleteTest(test.id, test.title)}
                                                    className="px-3 py-1 bg-red-500 text-white rounded text-xs font-medium hover:bg-red-600">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            ) : (
                <div className="text-center py-16 bg-gray-50 rounded-lg">
                    <p className="text-4xl mb-4">📝</p>
                    <h2 className="text-xl font-semibold text-gray-700 mb-2">No proficiency tests yet</h2>
                    <p className="text-gray-500 mb-6">Create your first IELTS-style English proficiency test</p>
                    <Link href={route('principal.professional-development.proficiency-tests.create')} className="btn btn-primary">
                        Create First Test
                    </Link>
                </div>
            )}
        </AppLayout>
    );
}
