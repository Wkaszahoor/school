import { GroupSubmission, StudentGroup } from '@/types/professional-development';
import StatusBadge from './StatusBadge';

interface Props {
  submission: GroupSubmission;
  onDownload?: () => void;
  onGrade?: () => void;
  onFeedback?: () => void;
}

export default function SubmissionCard({ submission, onDownload, onGrade, onFeedback }: Props) {
  const submissionDate = submission.submitted_at
    ? new Date(submission.submitted_at).toLocaleDateString('en-GB')
    : 'Not submitted';

  const groupName = submission.group?.group_name || 'Unknown Group';
  const memberCount = submission.group?.members_count || 0;

  return (
    <div className="bg-white rounded-lg shadow-md p-4 border border-gray-200 hover:shadow-lg transition">
      <div className="flex items-start justify-between mb-4">
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-gray-900">{groupName}</h3>
          <p className="text-sm text-gray-600">
            {memberCount} {memberCount === 1 ? 'member' : 'members'}
          </p>
        </div>
        <StatusBadge status={submission.status} type="assignment" size="sm" />
      </div>

      <div className="grid grid-cols-2 gap-4 mb-4 pb-4 border-b">
        <div>
          <p className="text-xs text-gray-500 uppercase font-semibold">Submitted</p>
          <p className="text-sm text-gray-900 font-medium">{submissionDate}</p>
        </div>
        {submission.score !== undefined && (
          <div>
            <p className="text-xs text-gray-500 uppercase font-semibold">Score</p>
            <p className="text-sm text-gray-900 font-medium">
              {submission.score} {submission.score && submission.score > 0 ? 'pts' : ''}
            </p>
          </div>
        )}
      </div>

      {submission.file_name && (
        <div className="mb-4 pb-4 border-b">
          <p className="text-xs text-gray-500 uppercase font-semibold mb-2">File</p>
          <p className="text-sm text-blue-600 font-medium truncate hover:underline">
            {submission.file_name}
          </p>
        </div>
      )}

      {submission.feedback && (
        <div className="mb-4 pb-4 border-b">
          <p className="text-xs text-gray-500 uppercase font-semibold mb-2">Feedback</p>
          <p className="text-sm text-gray-700 line-clamp-3">{submission.feedback}</p>
        </div>
      )}

      <div className="flex gap-2 flex-wrap">
        {submission.file_path && onDownload && (
          <button
            onClick={onDownload}
            className="flex-1 px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition text-sm font-medium"
          >
            Download
          </button>
        )}
        {onGrade && submission.status !== 'graded' && (
          <button
            onClick={onGrade}
            className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-sm font-medium"
          >
            Grade
          </button>
        )}
        {onFeedback && (
          <button
            onClick={onFeedback}
            className="flex-1 px-3 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition text-sm font-medium"
          >
            Feedback
          </button>
        )}
      </div>
    </div>
  );
}
