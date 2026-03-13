import { PBLAssignment, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link } from '@inertiajs/react';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';

interface Props extends PageProps {
  assignments: PBLAssignment[];
}

export default function TeacherPBLAssignmentsIndex({ assignments }: Props) {
  return (
    <AppLayout>
      <Head title="PBL Assignments" />

      <div className="container mx-auto px-4 py-8">
        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">PBL Assignments</h1>
            <p className="text-gray-600 mt-1">View and manage assignments for your classes</p>
          </div>
          <Link
            href={route('teacher.professional-development.pbl-assignments.create')}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            Create Assignment
          </Link>
        </div>

        {/* Assignments Grid */}
        {assignments.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {assignments.map((assignment) => (
              <div key={assignment.id} className="bg-white rounded-lg shadow-md p-6 border border-gray-200 hover:shadow-lg transition">
                <div className="flex items-start justify-between mb-4">
                  <h3 className="text-lg font-semibold text-gray-900 flex-1">{assignment.title}</h3>
                  <StatusBadge status={assignment.status} type="assignment" size="sm" />
                </div>

                <p className="text-sm text-gray-600 mb-4 line-clamp-2">{assignment.description}</p>

                <div className="space-y-2 text-sm mb-4 pb-4 border-b">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Class:</span>
                    <span className="font-medium text-gray-900">{assignment.class?.name}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Start Date:</span>
                    <span className="font-medium text-gray-900">
                      {new Date(assignment.start_date).toLocaleDateString('en-GB')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Due Date:</span>
                    <span className="font-medium text-gray-900">
                      {new Date(assignment.due_date).toLocaleDateString('en-GB')}
                    </span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Student Groups:</span>
                    <span className="font-medium text-gray-900">{assignment.student_groups_count || 0}</span>
                  </div>
                </div>

                <div className="flex gap-2">
                  <Link
                    href={route('teacher.professional-development.pbl-assignments.show', assignment.id)}
                    className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium text-center"
                  >
                    View
                  </Link>
                  <Link
                    href={route('teacher.professional-development.pbl-assignments.edit', assignment.id)}
                    className="flex-1 px-3 py-2 bg-amber-500 text-white rounded-md hover:bg-amber-600 transition text-sm font-medium text-center"
                  >
                    Edit
                  </Link>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-600 text-lg mb-4">No assignments yet</p>
            <Link
              href={route('teacher.professional-development.pbl-assignments.create')}
              className="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
            >
              Create First Assignment
            </Link>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
