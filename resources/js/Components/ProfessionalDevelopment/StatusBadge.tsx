interface Props {
  status: string;
  type?: 'course' | 'assignment' | 'cert' | 'resource';
  size?: 'sm' | 'md' | 'lg';
}

const statusConfig = {
  course: {
    draft: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft' },
    published: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Published' },
    archived: { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Archived' },
    enrolled: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Enrolled' },
    in_progress: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'In Progress' },
    completed: { bg: 'bg-green-100', text: 'text-green-800', label: 'Completed' },
    dropped: { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Dropped' },
  },
  assignment: {
    draft: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft' },
    active: { bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
    completed: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Completed' },
    archived: { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Archived' },
    submitted: { bg: 'bg-blue-100', text: 'text-blue-800', label: 'Submitted' },
    graded: { bg: 'bg-green-100', text: 'text-green-800', label: 'Graded' },
    revision_requested: { bg: 'bg-yellow-100', text: 'text-yellow-800', label: 'Revision Requested' },
  },
  cert: {
    active: { bg: 'bg-green-100', text: 'text-green-800', label: 'Active' },
    expired: { bg: 'bg-red-100', text: 'text-red-800', label: 'Expired' },
    revoked: { bg: 'bg-red-100', text: 'text-red-800', label: 'Revoked' },
  },
  resource: {
    draft: { bg: 'bg-gray-100', text: 'text-gray-800', label: 'Draft' },
    published: { bg: 'bg-green-100', text: 'text-green-800', label: 'Published' },
    archived: { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Archived' },
  },
};

const sizeClasses = {
  sm: 'px-2 py-1 text-xs',
  md: 'px-3 py-1 text-sm',
  lg: 'px-4 py-2 text-base',
};

export default function StatusBadge({ status, type = 'course', size = 'md' }: Props) {
  const config = statusConfig[type as keyof typeof statusConfig];
  const statusConfig_ = config?.[status as keyof typeof config] || {
    bg: 'bg-gray-100',
    text: 'text-gray-800',
    label: status,
  };

  return (
    <span
      className={`inline-flex items-center font-medium rounded-full ${statusConfig_.bg} ${statusConfig_.text} ${sizeClasses[size]}`}
    >
      {statusConfig_.label}
    </span>
  );
}
