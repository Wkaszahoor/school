import { TrainingCourse, CourseMaterial, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';

interface Props extends PageProps {
  course: TrainingCourse & {
    materials?: CourseMaterial[];
    enrolled?: boolean;
    learning_outcomes?: string[];
  };
}

export default function TeacherTrainingCoursesShow({ course }: Props) {
  const handleEnroll = () => {
    router.post(route('teacher.professional-development.training-courses.enroll', course.id), {});
  };

  const handleUnenroll = () => {
    if (confirm('Are you sure you want to unenroll from this course?')) {
      router.post(route('teacher.professional-development.training-courses.unenroll', course.id), {});
    }
  };

  const levelColors: Record<string, string> = {
    beginner: 'bg-green-100 text-green-800',
    intermediate: 'bg-blue-100 text-blue-800',
    advanced: 'bg-purple-100 text-purple-800',
  };

  return (
    <AppLayout>
      <Head title={course.title} />

      <div className="container mx-auto px-4 py-8">
        {/* Back Link */}
        <Link
          href={route('teacher.professional-development.training-courses.index')}
          className="text-blue-500 hover:text-blue-600 text-sm font-medium mb-4 block"
        >
          &larr; Back to Courses
        </Link>

        <div className="max-w-4xl mx-auto">
          {/* Header */}
          <div className="mb-8">
            <div className="flex items-start justify-between mb-4">
              <h1 className="text-4xl font-bold text-gray-900">{course.title}</h1>
              <div className="flex gap-2">
                <StatusBadge status={course.status} type="course" size="md" />
                <span className={`text-xs font-semibold px-3 py-1 rounded ${levelColors[course.level]}`}>
                  {course.level.charAt(0).toUpperCase() + course.level.slice(1)}
                </span>
              </div>
            </div>
            <p className="text-xl text-gray-600">{course.description}</p>
          </div>

          {/* Course Info Grid */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-6 mb-8">
            <div className="bg-white rounded-lg shadow-md p-4">
              <p className="text-xs text-gray-500 uppercase font-semibold">Duration</p>
              <p className="text-2xl font-bold text-gray-900 mt-2">{course.duration_hours}h</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-4">
              <p className="text-xs text-gray-500 uppercase font-semibold">Level</p>
              <p className="text-lg font-bold text-gray-900 mt-2 capitalize">{course.level}</p>
            </div>
            <div className="bg-white rounded-lg shadow-md p-4">
              <p className="text-xs text-gray-500 uppercase font-semibold">Status</p>
              <p className="mt-2"><StatusBadge status={course.status} type="course" size="sm" /></p>
            </div>
            {course.created_by && (
              <div className="bg-white rounded-lg shadow-md p-4">
                <p className="text-xs text-gray-500 uppercase font-semibold">Created By</p>
                <p className="text-sm font-medium text-gray-900 mt-2">{course.created_by.name}</p>
              </div>
            )}
          </div>

          {/* Enrollment Section */}
          {!course.enrolled && course.status === 'published' && (
            <div className="bg-blue-50 border-2 border-blue-200 rounded-lg p-6 mb-8">
              <p className="text-gray-700 mb-4">Enroll in this course to access materials and track your progress.</p>
              <button
                onClick={handleEnroll}
                className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
              >
                Enroll in Course
              </button>
            </div>
          )}

          {course.enrolled && (
            <div className="bg-green-50 border-2 border-green-200 rounded-lg p-6 mb-8">
              <p className="text-gray-700 mb-4">You are enrolled in this course.</p>
              <button
                onClick={handleUnenroll}
                className="px-6 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition"
              >
                Unenroll from Course
              </button>
            </div>
          )}

          {/* Learning Outcomes */}
          {course.learning_outcomes && course.learning_outcomes.length > 0 && (
            <div className="bg-white rounded-lg shadow-md p-6 mb-8">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Learning Outcomes</h2>
              <ul className="space-y-2">
                {course.learning_outcomes.map((outcome, idx) => (
                  <li key={idx} className="flex items-start gap-3">
                    <span className="text-green-500 font-bold mt-1">✓</span>
                    <span className="text-gray-700">{outcome}</span>
                  </li>
                ))}
              </ul>
            </div>
          )}

          {/* Course Materials */}
          {course.materials && course.materials.length > 0 && (
            <div className="bg-white rounded-lg shadow-md p-6">
              <h2 className="text-xl font-semibold text-gray-900 mb-4">Course Materials ({course.materials.length})</h2>
              {course.enrolled ? (
                <div className="space-y-2">
                  {course.materials.map((material) => (
                    <div key={material.id} className="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                      <div className="flex-1">
                        <p className="font-medium text-gray-900">{material.file_name}</p>
                        <p className="text-xs text-gray-500">{(material.file_size / 1024 / 1024).toFixed(2)} MB</p>
                      </div>
                      <a
                        href={`/storage/${material.file_path}`}
                        download
                        className="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium"
                      >
                        Download
                      </a>
                    </div>
                  ))}
                </div>
              ) : (
                <p className="text-gray-600 text-center py-8">Enroll in this course to access materials</p>
              )}
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
