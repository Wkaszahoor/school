import { Certification } from '@/types/professional-development';
import StatusBadge from './StatusBadge';
import { Link } from '@inertiajs/react';

interface Props {
  certification: Certification;
}

const typeIcons: Record<string, string> = {
  course_completion: '📚',
  professional_development_hours: '⏱️',
  skill_mastery: '⭐',
  custom: '🏆',
};

export default function CertificateCard({ certification }: Props) {
  const isExpired = certification.expiry_date && new Date(certification.expiry_date) < new Date();
  const isExpiringSoon = !isExpired && certification.expiry_date &&
    (new Date(certification.expiry_date).getTime() - new Date().getTime()) < (30 * 24 * 60 * 60 * 1000);

  const formattedIssued = new Date(certification.issued_date).toLocaleDateString('en-GB');
  const formattedExpiry = certification.expiry_date
    ? new Date(certification.expiry_date).toLocaleDateString('en-GB')
    : 'No expiry';

  return (
    <div className={`p-6 rounded-lg border-2 transition-all ${
      isExpired
        ? 'bg-red-50 border-red-200'
        : isExpiringSoon
        ? 'bg-yellow-50 border-yellow-200'
        : 'bg-white border-gray-200'
    }`}>
      <div className="flex items-start justify-between mb-4">
        <div className="text-3xl">{typeIcons[certification.certification_type] || '🏅'}</div>
        <StatusBadge status={certification.status} type="cert" size="sm" />
      </div>

      <h3 className="text-lg font-semibold text-gray-900 mb-1">{certification.title}</h3>
      <p className="text-sm text-gray-600 mb-4">{certification.issuing_body}</p>

      <div className="space-y-2 text-sm mb-4 border-t pt-4">
        <div className="flex justify-between">
          <span className="text-gray-600">Issued Date:</span>
          <span className="font-medium text-gray-900">{formattedIssued}</span>
        </div>
        {certification.expiry_date && (
          <div className="flex justify-between">
            <span className="text-gray-600">Expiry Date:</span>
            <span className={`font-medium ${
              isExpired ? 'text-red-600' : isExpiringSoon ? 'text-yellow-600' : 'text-gray-900'
            }`}>
              {formattedExpiry}
            </span>
          </div>
        )}
        {certification.certificate_number && (
          <div className="flex justify-between">
            <span className="text-gray-600">Certificate No:</span>
            <span className="font-mono text-gray-900">{certification.certificate_number}</span>
          </div>
        )}
      </div>

      {certification.description && (
        <p className="text-sm text-gray-600 mb-4 pb-4 border-t">{certification.description}</p>
      )}

      <div className="flex gap-2">
        <Link
          href={route('principal.certifications.show', certification.id)}
          className="flex-1 px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition text-center text-sm font-medium"
        >
          View Details
        </Link>
        {certification.pdf_path && (
          <a
            href={`/storage/${certification.pdf_path}`}
            download
            className="flex-1 px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition text-center text-sm font-medium"
          >
            Download PDF
          </a>
        )}
      </div>
    </div>
  );
}
