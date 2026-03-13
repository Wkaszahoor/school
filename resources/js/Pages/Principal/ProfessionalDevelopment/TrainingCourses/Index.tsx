import { TrainingCourse, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CourseCard from '@/Components/ProfessionalDevelopment/CourseCard';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';

interface Props extends PageProps {
  courses: {
    data: TrainingCourse[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: {
    status?: string;
    level?: string;
    search?: string;
  };
}

export default function TrainingCoursesIndex({ courses, filters = {} }: Props) {
  const { flash } = usePage().props;
  const [searchTerm, setSearchTerm] = useState(filters.search || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || '');
  const [levelFilter, setLevelFilter] = useState(filters.level || '');
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [courseToDelete, setCourseToDelete] = useState<TrainingCourse | null>(null);

  const handleSearch = () => {
    router.get(route('principal.professional-development.training-courses.index'), {
      search: searchTerm,
      status: statusFilter,
      level: levelFilter,
    }, { preserveScroll: true });
  };

  const handleDelete = (course: TrainingCourse) => {
    setCourseToDelete(course);
    setDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (!courseToDelete) return;
    router.delete(route('principal.professional-development.training-courses.destroy', courseToDelete.id), {
      onSuccess: () => {
        setDeleteModalOpen(false);
        setCourseToDelete(null);
      },
    });
  };

  return (
    <AppLayout>
      <Head title="Training Courses" />

      <div className="container mx-auto px-4 py-8">
        {/* Flash Message */}
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Training Courses</h1>
            <p className="text-gray-600 mt-1">Manage professional development training courses</p>
          </div>
          <Link
            href={route('principal.professional-development.training-courses.create')}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            Create Course
          </Link>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
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
              <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Statuses</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
              </select>
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
        {courses.data.length > 0 ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              {courses.data.map((course) => (
                <CourseCard
                  key={course.id}
                  course={course}
                  actions={
                    <div className="flex gap-2 mt-4 pt-4 border-t">
                      <Link
                        href={route('principal.professional-development.training-courses.show', course.id)}
                        className="flex-1 px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition text-sm font-medium text-center"
                      >
                        View
                      </Link>
                      <Link
                        href={route('principal.professional-development.training-courses.edit', course.id)}
                        className="px-3 py-2 bg-amber-500 text-white rounded-md hover:bg-amber-600 transition text-sm font-medium"
                      >
                        Edit
                      </Link>
                      <button
                        onClick={() => handleDelete(course)}
                        className="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-sm font-medium"
                      >
                        Delete
                      </button>
                    </div>
                  }
                />
              ))}
            </div>

            {/* Pagination */}
            {courses && (
              <Pagination data={courses} />
            )}
          </>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-600 text-lg mb-4">No training courses found</p>
            <Link
              href={route('principal.professional-development.training-courses.create')}
              className="px-6 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
            >
              Create First Course
            </Link>
          </div>
        )}

        {/* Delete Confirmation Modal */}
        <Modal
          isOpen={deleteModalOpen}
          onClose={() => setDeleteModalOpen(false)}
          title="Delete Course"
        >
          <div className="text-center py-4">
            <p className="text-gray-900 font-medium mb-4">
              Delete "{courseToDelete?.title}"?
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
