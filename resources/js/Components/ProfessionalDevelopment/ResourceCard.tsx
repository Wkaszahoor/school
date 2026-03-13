import { TeachingResource } from '@/types/professional-development';
import StatusBadge from './StatusBadge';
import { Link } from '@inertiajs/react';

interface Props {
  resource: TeachingResource;
  canEdit?: boolean;
  onDelete?: (id: number) => void;
  onDownload?: (id: number) => void;
}

const typeIcons: Record<string, string> = {
  pdf: '📄',
  video: '🎥',
  document: '📝',
  url: '🔗',
  image: '🖼️',
};

export default function ResourceCard({ resource, canEdit = false, onDelete, onDownload }: Props) {
  return (
    <div className="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition border border-gray-200">
      <div className="bg-gradient-to-r from-gray-500 to-gray-600 h-24 flex items-center justify-center">
        <span className="text-4xl">{typeIcons[resource.resource_type] || '📦'}</span>
      </div>

      <div className="p-4">
        <div className="flex items-start justify-between mb-2">
          <h3 className="text-base font-bold text-gray-900 flex-1 line-clamp-2">{resource.title}</h3>
          {resource.status === 'published' && (
            <span className="text-xs font-semibold px-2 py-1 rounded bg-green-100 text-green-800 ml-2 flex-shrink-0">
              Published
            </span>
          )}
        </div>

        <p className="text-sm text-gray-600 mb-3 line-clamp-2">{resource.description}</p>

        <div className="flex flex-wrap gap-2 mb-3 pb-3 border-b text-xs">
          {resource.subject && (
            <span className="px-2 py-1 rounded bg-blue-100 text-blue-800 font-medium">
              {resource.subject.name}
            </span>
          )}
          <span className="px-2 py-1 rounded bg-gray-100 text-gray-800 font-medium">
            {resource.topic_category}
          </span>
        </div>

        {resource.uploaded_by && (
          <p className="text-xs text-gray-500 mb-3">
            By <span className="font-medium">{resource.uploaded_by.name}</span>
          </p>
        )}

        <div className="flex gap-2 flex-wrap">
          <Link
            href={route('teacher.resources.show', resource.id)}
            className="flex-1 min-w-20 px-2 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-xs font-medium text-center"
          >
            View
          </Link>
          {!canEdit && (
            <button
              onClick={() => onDownload?.(resource.id)}
              className="flex-1 min-w-20 px-2 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition text-xs font-medium"
            >
              Download
            </button>
          )}
          {canEdit && (
            <>
              <Link
                href={route('teacher.resources.edit', resource.id)}
                className="px-2 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-xs font-medium"
              >
                Edit
              </Link>
              <button
                onClick={() => onDelete?.(resource.id)}
                className="px-2 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-xs font-medium"
              >
                Delete
              </button>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
