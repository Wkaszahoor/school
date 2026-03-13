import { TrainingCourse, CourseEnrollment, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CourseCard from '@/Components/ProfessionalDevelopment/CourseCard';
import EnrollmentProgressBar from '@/Components/ProfessionalDevelopment/EnrollmentProgressBar';
import Pagination from '@/Components/Pagination';

interface Props extends PageProps {
  availableCourses: {
    data: TrainingCourse[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  enrolledCourses: CourseEnrollment[];
  filters?: {
    level?: string;
    search?: string;
  };
}

export default function TeacherCoursesIndex({ availableCourses, enrolledCourses, filters = {} }: Props) {
  const { flash } = usePage().props;
  const [levelFilter, setLevelFilter] = useState(filters.level || '');
  const [searchTerm, setSearchTerm] = useState(filters.search || '');

  const handleSearch = () => {
    router.get(route('teacher.professional-development.training-courses.index'), {
      search: searchTerm,
      level: levelFilter,
    }, { preserveScroll: true });
  };

  const handleEnroll = (courseId: number) => {
    router.post(route('teacher.professional-development.training-courses.enroll', courseId), {});
  };

  const handleUnenroll = (courseId: number) => {
    if (confirm('Are you sure you want to unenroll from this course?')) {
      router.post(route('teacher.professional-development.training-courses.unenroll', courseId), {});
    }
  };

  const enrolledCourseIds = new Set(enrolledCourses.map(e => e.training_course_id));

  return (
    <AppLayout>
      <Head title="Training Courses" />

      <div className="container mx-auto px-4 py-8">
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <h1 className="text-3xl font-bold text-gray-900 mb-8">Professional Development Courses</h1>

        {/* My Enrolled Courses */}
        {enrolledCourses.length > 0 && (
          <div className="mb-12">
            <h2 className="text-2xl font-bold text-gray-900 mb-6">My Enrolled Courses</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {enrolledCourses.map((enrollment) => (
                <div key={enrollment.id} className="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                  <h3 className="text-lg font-semibold text-gray-900 mb-2">{enrollment.course?.title}</h3>
                  <p className="text-sm text-gray-600 mb-4 line-clamp-2">{enrollment.course?.description}</p>

                  <EnrollmentProgressBar enrollment={enrollment} />

                  <div className="mt-4 flex gap-2">
                    <button
                      onClick={() => handleUnenroll(enrollment.training_course_id)}
                      className="flex-1 px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-sm font-medium"
                    >
                      Unenroll
                    </button>
                  </div>
                </div>
              ))}
            </div>
            <hr className="my-8" />
          </div>
        )}

        {/* Available Courses */}
        <div>
          <h2 className="text-2xl font-bold text-gray-900 mb-6">Available Courses</h2>

          {/* Filters */}
          <div className="bg-white rounded-lg shadow-md p-6 mb-8">
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                  placeholder="Search by title..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Level</label>
                <select
                  value={levelFilter}
                  onChange={(e) => setLevelFilter(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Levels</option>
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>
              <div className="flex items-end">
                <button
                  onClick={handleSearch}
                  className="w-full px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
                >
                  Search
                </button>
              </div>
            </div>
          </div>

          {/* Courses Grid */}
          {availableCourses.data.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {availableCourses.data.map((course) => (
                  <CourseCard
                    key={course.id}
                    course={course}
                    canEnroll={!enrolledCourseIds.has(course.id) && course.status === 'published'}
                    onEnroll={() => handleEnroll(course.id)}
                  />
                ))}
              </div>

              {availableCourses && (
                <Pagination data={availableCourses} />
              )}
            </>
          ) : (
            <div className="text-center py-12 bg-gray-50 rounded-lg">
              <p className="text-gray-600 text-lg">No courses found matching your search</p>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
