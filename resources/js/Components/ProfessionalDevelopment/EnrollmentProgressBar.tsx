import StatusBadge from './StatusBadge';

interface Props {
  enrollment: {
    progress_percentage: number;
    status: 'enrolled' | 'in_progress' | 'completed' | 'dropped';
  };
}

export default function EnrollmentProgressBar({ enrollment }: Props) {
  const isInProgress = enrollment.status === 'in_progress';

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium text-gray-700">Progress</span>
        <span className="text-sm font-semibold text-gray-900">
          {enrollment.progress_percentage}%
        </span>
      </div>
      <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
        <div
          className={`h-full rounded-full transition-all duration-300 ${
            enrollment.progress_percentage === 100
              ? 'bg-green-500'
              : 'bg-blue-500'
          } ${isInProgress ? 'animate-pulse' : ''}`}
          style={{
            width: `${enrollment.progress_percentage}%`,
          }}
        />
      </div>
      <div className="flex justify-center mt-2">
        <StatusBadge status={enrollment.status} type="course" size="sm" />
      </div>
    </div>
  );
}
