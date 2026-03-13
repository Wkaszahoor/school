import { Certification, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import CertificateCard from '@/Components/ProfessionalDevelopment/CertificateCard';
import Pagination from '@/Components/Pagination';
import Modal from '@/Components/Modal';

interface Props extends PageProps {
  certifications: {
    data: Certification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: {
    teacher_id?: string;
    type?: string;
    status?: string;
  };
}

export default function CertificationsIndex({ certifications, filters = {} }: Props) {
  const { flash } = usePage().props;
  const [typeFilter, setTypeFilter] = useState(filters.type || '');
  const [statusFilter, setStatusFilter] = useState(filters.status || '');
  const [revokeModalOpen, setRevokeModalOpen] = useState(false);
  const [certToRevoke, setCertToRevoke] = useState<Certification | null>(null);

  const handleFilter = () => {
    router.get(route('principal.professional-development.certifications.index'), {
      type: typeFilter,
      status: statusFilter,
    }, { preserveScroll: true });
  };

  const handleRevoke = (cert: Certification) => {
    setCertToRevoke(cert);
    setRevokeModalOpen(true);
  };

  const confirmRevoke = () => {
    if (!certToRevoke) return;
    router.patch(route('principal.professional-development.certifications.revoke', certToRevoke.id), {}, {
      onSuccess: () => {
        setRevokeModalOpen(false);
        setCertToRevoke(null);
      },
    });
  };

  return (
    <AppLayout>
      <Head title="Teacher Certifications" />

      <div className="container mx-auto px-4 py-8">
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Teacher Certifications</h1>
            <p className="text-gray-600 mt-1">{certifications.total} certifications in system</p>
          </div>
          <Link
            href={route('principal.professional-development.certifications.report')}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            View Report
          </Link>
        </div>

        {/* Filters */}
        <div className="bg-white rounded-lg shadow-md p-6 mb-8">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Types</option>
                <option value="course_completion">Course Completion</option>
                <option value="professional_development_hours">Professional Development Hours</option>
                <option value="skill_mastery">Skill Mastery</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="expired">Expired</option>
                <option value="revoked">Revoked</option>
              </select>
            </div>
            <div className="flex items-end">
              <button
                onClick={handleFilter}
                className="w-full px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
              >
                Filter
              </button>
            </div>
          </div>
        </div>

        {/* Certifications Grid */}
        {certifications.data.length > 0 ? (
          <>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
              {certifications.data.map((cert) => (
                <div key={cert.id} className="relative">
                  <CertificateCard certification={cert} />
                  {cert.status === 'active' && (
                    <button
                      onClick={() => handleRevoke(cert)}
                      className="absolute top-4 right-4 px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 transition text-xs font-medium"
                    >
                      Revoke
                    </button>
                  )}
                </div>
              ))}
            </div>

            {certifications && (
              <Pagination data={certifications} />
            )}
          </>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-600 text-lg">No certifications found</p>
          </div>
        )}

        {/* Revoke Modal */}
        <Modal
          isOpen={revokeModalOpen}
          onClose={() => setRevokeModalOpen(false)}
          title="Revoke Certification"
        >
          <div className="text-center py-4">
            <p className="text-gray-900 font-medium mb-4">
              Revoke certification "{certToRevoke?.title}"?
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
                onClick={confirmRevoke}
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
