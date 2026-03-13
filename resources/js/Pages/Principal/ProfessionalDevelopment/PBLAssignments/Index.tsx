import { PBLAssignment, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';

interface Props extends PageProps {
  assignments: {
    data: PBLAssignment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: {
    status?: string;
    class_id?: string;
    academic_year?: string;
  };
}

export default function PBLAssignmentsIndex({ assignments, filters = {} }: Props) {
  const { flash } = usePage().props;
  const [statusFilter, setStatusFilter] = useState(filters.status || '');
  const [classFilter, setClassFilter] = useState(filters.class_id || '');
  const [yearFilter, setYearFilter] = useState(filters.academic_year || '');
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [assignmentToDelete, setAssignmentToDelete] = useState<PBLAssignment | null>(null);

  const handleFilter = () => {
    router.get(route('principal.professional-development.pbl-assignments.index'), {
      status: statusFilter,
      class_id: classFilter,
      academic_year: yearFilter,
    }, { preserveScroll: true });
  };

  const handleDelete = (assignment: PBLAssignment) => {
    setAssignmentToDelete(assignment);
    setDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (!assignmentToDelete) return;
    router.delete(route('principal.professional-development.pbl-assignments.destroy', assignmentToDelete.id), {
      onSuccess: () => {
        setDeleteModalOpen(false);
        setAssignmentToDelete(null);
      },
    });
  };

  return (
    <AppLayout>
      <Head title="PBL Assignments" />

      <div className="container mx-auto px-4 py-8">
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">PBL Assignments</h1>
            <p className="text-gray-600 mt-1">Project-Based Learning assignments management</p>
          </div>
          <Link
            href={route('principal.professional-development.pbl-assignments.create')}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            Create Assignment
          </Link>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Class</label>
              <select
                value={classFilter}
                onChange={(e) => setClassFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Classes</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Academic Year</label>
              <select
                value={yearFilter}
                onChange={(e) => setYearFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Years</option>
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={handleFilter}
                className="w-full px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
              >
                Filter
              </button>
            </div>
          </div>
        </div>

        {/* Assignments List */}
        {assignments.data.length > 0 ? (
          <>
            <div className="bg-white rounded-lg shadow-md overflow-hidden">
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead className="bg-gray-50 border-b-2 border-gray-300">
                    <tr>
                      <th className="text-left py-3 px-4 font-semibold text-gray-900">Title</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-900">Class</th>
                      <th className="text-left py-3 px-4 font-semibold text-gray-900">Teacher</th>
                      <th className="text-center py-3 px-4 font-semibold text-gray-900">Start Date</th>
                      <th className="text-center py-3 px-4 font-semibold text-gray-900">Due Date</th>
                      <th className="text-center py-3 px-4 font-semibold text-gray-900">Status</th>
                      <th className="text-center py-3 px-4 font-semibold text-gray-900">Groups</th>
                      <th className="text-right py-3 px-4 font-semibold text-gray-900">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {assignments.data.map((assignment, idx) => (
                      <tr key={assignment.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                        <td className="py-3 px-4">
                          <p className="font-medium text-gray-900">{assignment.title}</p>
                        </td>
                        <td className="py-3 px-4 text-gray-600">{assignment.class?.name}</td>
                        <td className="py-3 px-4 text-gray-600">{assignment.teacher?.name}</td>
                        <td className="text-center py-3 px-4 text-sm text-gray-600">
                          {new Date(assignment.start_date).toLocaleDateString('en-GB')}
                        </td>
                        <td className="text-center py-3 px-4 text-sm text-gray-600">
                          {new Date(assignment.due_date).toLocaleDateString('en-GB')}
                        </td>
                        <td className="text-center py-3 px-4">
                          <StatusBadge status={assignment.status} type="assignment" size="sm" />
                        </td>
                        <td className="text-center py-3 px-4">
                          <span className="text-sm font-medium text-gray-900">{assignment.student_groups_count || 0}</span>
                        </td>
                        <td className="text-right py-3 px-4">
                          <div className="flex gap-1 justify-end">
                            <Link
                              href={route('principal.professional-development.pbl-assignments.show', assignment.id)}
                              className="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-xs font-medium"
                            >
                              View
                            </Link>
                            <Link
                              href={route('principal.professional-development.pbl-assignments.edit', assignment.id)}
                              className="px-3 py-1 bg-amber-500 text-white rounded-md hover:bg-amber-600 transition text-xs font-medium"
                            >
                              Edit
                            </Link>
                            <button
                              onClick={() => handleDelete(assignment)}
                              className="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-xs font-medium"
                            >
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

            {assignments && (
              <div className="mt-8">
                <Pagination data={assignments} />
              </div>
            )}
          </>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-600 text-lg mb-4">No assignments found</p>
            <Link
              href={route('principal.professional-development.pbl-assignments.create')}
              className="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
            >
              Create First Assignment
            </Link>
          </div>
        )}

        {/* Delete Modal */}
        <Modal
          isOpen={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          title="Delete Assignment"
        >
          <div className="text-center py-4">
            <p className="text-gray-900 font-medium mb-4">
              Delete "{assignmentToDelete?.title}"?
            </p>
            <p className="text-sm text-gray-600 mb-6">
              This action cannot be undone.
            </p>
            <div className="flex gap-2 justify-center">
              <button
                onClick={() => setDeleteModalOpen(false)}
                className="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg font-medium hover:bg-gray-400 transition"
              >
                Cancel
              </button>
              <button
                onClick={confirmDelete}
                className="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition"
              >
                Delete
              </button>
            </div>
          </div>
        </Modal>
      </div>
    </AppLayout>
  );
}
