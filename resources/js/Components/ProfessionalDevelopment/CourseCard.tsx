import { TrainingCourse } from '@/types/professional-development';
import StatusBadge from './StatusBadge';
import { Link } from '@inertiajs/react';

interface Props {
  course: TrainingCourse;
  actions?: React.ReactNode;
  canEnroll?: boolean;
  onEnroll?: () => void;
}

const levelColors: Record<string, string> = {
  beginner: 'bg-green-100 text-green-800',
  intermediate: 'bg-blue-100 text-blue-800',
  advanced: 'bg-purple-100 text-purple-800',
};

export default function CourseCard({ course, actions, canEnroll = false, onEnroll }: Props) {
  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition border border-gray-200">
      <div className="bg-gradient-to-r from-blue-500 to-blue-600 h-32 relative">
        <div className="absolute inset-0 flex items-center justify-center text-white text-5xl opacity-20">
          📚
        </div>
      </div>

      <div className="p-6">
        <div className="flex items-start justify-between mb-2">
          <h3 className="text-lg font-bold text-gray-900 flex-1 line-clamp-2">{course.course_name}</h3>
          <StatusBadge status={course.status} type="course" size="sm" />
        </div>

        <p className="text-sm text-gray-600 mb-4 line-clamp-2">{course.description}</p>

        <div className="flex flex-wrap gap-2 mb-4">
          <span className={`text-xs font-semibold px-2 py-1 rounded ${levelColors[course.level] || 'bg-gray-100 text-gray-800'}`}>
            {course.level.charAt(0).toUpperCase() + course.level.slice(1)}
          </span>
          <span className="text-xs font-semibold px-2 py-1 rounded bg-gray-100 text-gray-800">
            {course.duration_hours}h
          </span>
          <span className="text-xs font-semibold px-2 py-1 rounded bg-amber-100 text-amber-800">
            {course.course_type}
          </span>
        </div>

        {course.instructor && (
          <p className="text-xs text-gray-500 mb-4 pb-4 border-b">
            Instructor: <span className="font-medium">{course.instructor.name}</span>
          </p>
        )}

        <div className="flex gap-2 flex-wrap">
          {canEnroll && onEnroll ? (
            <button
              onClick={onEnroll}
              className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium"
            >
              Enroll
            </button>
          ) : (
            <Link
              href={route('principal.professional-development.training-courses.show', course.id)}
              className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium text-center"
            >
              View
            </Link>
          )}
          {actions}
        </div>
      </div>
    </div>
  );
}
