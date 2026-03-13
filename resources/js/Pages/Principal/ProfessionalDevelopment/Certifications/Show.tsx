import { Certification, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router } from '@inertiajs/react';
import StatusBadge from '@/Components/ProfessionalDevelopment/StatusBadge';
import Modal from '@/Components/Modal';
import { useState } from 'react';

interface Props extends PageProps {
  certification: Certification;
}

export default function CertificationsShow({ certification }: Props) {
  const [revokeModalOpen, setRevokeModalOpen] = useState(false);

  const handleRevoke = () => {
    router.patch(route('principal.professional-development.certifications.revoke', certification.id), {}, {
      onSuccess: () => setRevokeModalOpen(false),
    });
  };

  const isExpired = certification.expiry_date && new Date(certification.expiry_date) < new Date();
  const isExpiringSoon = !isExpired && certification.expiry_date &&
    (new Date(certification.expiry_date).getTime() - new Date().getTime()) < (30 * 24 * 60 * 60 * 1000);

  const formattedIssued = new Date(certification.issued_date).toLocaleDateString('en-GB', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });

  const formattedExpiry = certification.expiry_date
    ? new Date(certification.expiry_date).toLocaleDateString('en-GB', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    })
    : 'No expiry date';

  return (
    <AppLayout>
      <Head title={certification.title} />

      <div className="container mx-auto px-4 py-8">
        {/* Back Link */}
        <Link
          href={route('principal.professional-development.certifications.index')}
          className="text-blue-500 hover:text-blue-600 text-sm font-medium mb-4 block"
        >
          &larr; Back to Certifications
        </Link>

        <div className="max-w-3xl mx-auto">
          {/* Certificate Card */}
          <div className={`p-8 rounded-lg border-2 mb-8 ${
            isExpired
              ? 'bg-red-50 border-red-200'
              : isExpiringSoon
              ? 'bg-yellow-50 border-yellow-200'
              : 'bg-green-50 border-green-200'
          }`}>
            <div className="flex items-start justify-between mb-6">
              <div>
                <p className="text-sm text-gray-600 uppercase font-semibold mb-2">Certificate</p>
                <h1 className="text-4xl font-bold text-gray-900">{certification.title}</h1>
              </div>
              <StatusBadge status={certification.status} type="cert" size="md" />
            </div>

            <p className="text-lg text-gray-700 mb-8">{certification.description}</p>

            <div className="grid grid-cols-2 gap-8 py-8 border-t border-b">
              <div>
                <p className="text-xs text-gray-600 uppercase font-semibold mb-2">Issued By</p>
                <p className="text-xl font-semibold text-gray-900">{certification.issuing_body}</p>
              </div>
              <div>
                <p className="text-xs text-gray-600 uppercase font-semibold mb-2">Certificate Number</p>
                <p className="text-xl font-mono text-gray-900">{certification.certificate_number}</p>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-8 py-8">
              <div>
                <p className="text-xs text-gray-600 uppercase font-semibold mb-2">Issued Date</p>
                <p className="text-lg font-medium text-gray-900">{formattedIssued}</p>
              </div>
              {certification.expiry_date && (
                <div>
                  <p className="text-xs text-gray-600 uppercase font-semibold mb-2">Expiry Date</p>
                  <p className={`text-lg font-medium ${
                    isExpired ? 'text-red-600' : isExpiringSoon ? 'text-yellow-600' : 'text-gray-900'
                  }`}>
                    {formattedExpiry}
                  </p>
                </div>
              )}
            </div>

            {certification.certification_type && (
              <div className="pt-8 border-t">
                <p className="text-xs text-gray-600 uppercase font-semibold mb-2">Certification Type</p>
                <p className="text-lg font-medium text-gray-900 capitalize">
                  {certification.certification_type.replace(/_/g, ' ')}
                </p>
              </div>
            )}
          </div>

          {/* Teacher Info */}
          {certification.teacher && (
            <div className="bg-white rounded-lg shadow-md p-6 mb-8">
              <h2 className="text-lg font-semibold text-gray-900 mb-4">Teacher Information</h2>
              <div className="space-y-2">
                <p><span className="text-gray-600">Name:</span> <span className="font-medium text-gray-900">{certification.teacher.name}</span></p>
              </div>
            </div>
          )}

          {/* Actions */}
          <div className="flex gap-3">
            {certification.pdf_path && (
              <a
                href={`/storage/${certification.pdf_path}`}
                download
                className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
              >
                Download Certificate PDF
              </a>
            )}
            {certification.status === 'active' && (
              <button
                onClick={() => setRevokeModalOpen(true)}
                className="px-6 py-3 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition"
              >
                Revoke Certificate
              </button>
            )}
          </div>
        </div>

        {/* Revoke Modal */}
        <Modal
          isOpen={revokeModalOpen}
          onClose={() => setRevokeModalOpen(false)}
          title="Revoke Certificate"
        >
          <div className="text-center py-4">
            <p className="text-gray-900 font-medium mb-4">
              Revoke "{certification.title}" for {certification.teacher?.name}?
            </p>
            <p className="text-sm text-gray-600 mb-6">
              This will mark the certification as revoked.
            </p>
            <div className="flex gap-2 justify-center">
              <button
                onClick={() => setRevokeModalOpen(false)}
                className="px-4 py-2 bg-gray-300 text-gray-900 rounded-lg font-medium hover:bg-gray-400 transition"
              >
                Cancel
              </button>
              <button
                onClick={handleRevoke}
                className="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition"
              >
                Revoke
              </button>
            </div>
          </div>
        </Modal>
      </div>
    </AppLayout>
  );
}
