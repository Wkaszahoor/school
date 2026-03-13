import { TrainingCourse, CourseEnrollment, CourseMaterial, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';
import Modal from '@/Components/Modal';

interface Props extends PageProps {
  course: TrainingCourse & {
    materials?: CourseMaterial[];
    enrollments?: CourseEnrollment[];
  };
}

export default function TrainingCoursesShow({ course }: Props) {
  const { flash } = usePage().props;
  const [enrollModalOpen, setEnrollModalOpen] = useState(false);
  const [selectedTeacher, setSelectedTeacher] = useState('');

  const handleEnroll = () => {
    if (!selectedTeacher) return;
    router.post(route('principal.professional-development.training-courses.enroll', course.id), {
      teacher_id: selectedTeacher,
    });
    setEnrollModalOpen(false);
    setSelectedTeacher('');
  };

  const handleRemoveEnrollment = (enrollmentId: number) => {
    if (confirm('Remove this enrollment?')) {
      router.delete(route('principal.enrollments.destroy', enrollmentId));
    }
  };

  return (
    <AppLayout>
      <Head title={course.title} />

      <div className="container mx-auto px-4 py-8">
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <div className="mb-8">
          <Link
            href={route('principal.professional-development.training-courses.index')}
            className="text-blue-500 hover:text-blue-600 text-sm font-medium mb-4 block"
          >
            &larr; Back to Courses
          </Link>
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h1 className="text-3xl font-bold text-gray-900">{course.title}</h1>
              <p className="text-gray-600 mt-2">{course.description}</p>
            </div>
            <StatusBadge status={course.status} type="course" size="md" />
          </div>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
          {/* Course Info Card */}
          <div className="lg:col-span-1 bg-white rounded-lg shadow-md p-6 h-fit">
            <h2 className="text-lg font-semibold text-gray-900 mb-4">Course Information</h2>
            <div className="space-y-4">
              <div>
                <p className="text-xs text-gray-500 uppercase font-semibold">Duration</p>
                <p className="text-lg font-medium text-gray-900">{course.duration_hours} hours</p>
              </div>
              <div>
                <p className="text-xs text-gray-500 uppercase font-semibold">Level</p>
                <p className="text-lg font-medium text-gray-900 capitalize">{course.level}</p>
              </div>
              <div>
                <p className="text-xs text-gray-500 uppercase font-semibold">Status</p>
                <p className="mt-1"><StatusBadge status={course.status} type="course" size="sm" /></p>
              </div>
              {course.created_by && (
                <div>
                  <p className="text-xs text-gray-500 uppercase font-semibold">Created By</p>
                  <p className="text-gray-900 font-medium">{course.created_by.name}</p>
                </div>
              )}
            </div>
          </div>

          {/* Main Content */}
          <div className="lg:col-span-2 space-y-8">
            {/* Materials Section */}
            {course.materials && course.materials.length > 0 && (
              <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-4">Course Materials ({course.materials.length})</h2>
                <div className="space-y-2">
                  {course.materials.map((material) => (
                    <div key={material.id} className="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                      <div className="flex-1">
                        <p className="font-medium text-gray-900">{material.file_name}</p>
                        <p className="text-xs text-gray-500">{(material.file_size / 1024 / 1024).toFixed(2)} MB</p>
                      </div>
                      <a
                        href={`/storage/${material.file_path}`}
                        download
                        className="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium"
                      >
                        Download
                      </a>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Enrollments Section */}
            <div className="bg-white rounded-lg shadow-md p-6">
              <div className="flex items-center justify-between mb-4">
                <h2 className="text-lg font-semibold text-gray-900">Enrolled Teachers ({course.enrollments?.length || 0})</h2>
                <button
                  onClick={() => setEnrollModalOpen(true)}
                  className="px-4 py-2 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition text-sm"
                >
                  Enroll Teacher
                </button>
              </div>

              {course.enrollments && course.enrollments.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="w-full text-sm">
                    <thead className="border-b-2 border-gray-300">
                      <tr>
                        <th className="text-left py-3 px-4 font-semibold text-gray-900">Teacher</th>
                        <th className="text-center py-3 px-4 font-semibold text-gray-900">Status</th>
                        <th className="text-center py-3 px-4 font-semibold text-gray-900">Progress</th>
                        <th className="text-center py-3 px-4 font-semibold text-gray-900">Enrolled</th>
                        <th className="text-right py-3 px-4 font-semibold text-gray-900">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {course.enrollments.map((enrollment) => (
                        <tr key={enrollment.id} className="border-b">
                          <td className="py-3 px-4">
                            <p className="font-medium text-gray-900">{enrollment.teacher?.name}</p>
                            <p className="text-xs text-gray-500">{enrollment.teacher?.email}</p>
                          </td>
                          <td className="text-center py-3 px-4">
                            <StatusBadge status={enrollment.status} type="course" size="sm" />
                          </td>
                          <td className="text-center py-3 px-4">{enrollment.progress_percentage}%</td>
                          <td className="text-center py-3 px-4 text-sm text-gray-600">
                            {new Date(enrollment.enrolled_at).toLocaleDateString('en-GB')}
                          </td>
                          <td className="text-right py-3 px-4">
                            <button
                              onClick={() => handleRemoveEnrollment(enrollment.id)}
                              className="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-xs font-medium"
                            >
                              Remove
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <p className="text-gray-600 text-center py-8">No teachers enrolled yet</p>
              )}
            </div>
          </div>
        </div>

        {/* Edit Button */}
        <Link
          href={route('principal.professional-development.training-courses.edit', course.id)}
          className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
        >
          Edit Course
        </Link>

        {/* Enroll Modal */}
        <Modal
          isOpen={enrollModalOpen}
          onClose={() => setEnrollModalOpen(false)}
          title="Enroll Teacher"
        >
          <div className="py-4">
            <p className="text-gray-700 mb-4">Select a teacher to enroll in this course:</p>
            <select
              value={selectedTeacher}
              onChange={(e) => setSelectedTeacher(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4"
            >
              <option value="">-- Select Teacher --</option>
            </select>
            <div className="flex gap-2 justify-end">
              <button
                onClick={() => setEnrollModalOpen(false)}
                className="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg font-medium hover:bg-gray-400 transition"
              >
                Cancel
              </button>
              <button
                onClick={handleEnroll}
                disabled={!selectedTeacher}
                className="px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition disabled:bg-gray-400"
              >
                Enroll
              </button>
            </div>
          </div>
        </Modal>
      </div>
    </AppLayout>
  );
}
