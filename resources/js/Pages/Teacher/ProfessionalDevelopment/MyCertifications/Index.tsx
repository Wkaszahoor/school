import { Certification, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import CertificateCard from '@/Components/ProfessionalDevelopment/CertificateCard';

interface Props extends PageProps {
  certifications: Certification[];
  stats?: {
    total_active: number;
    expiring_soon: number;
    expired: number;
  };
}

export default function MyCertificationsIndex({ certifications, stats }: Props) {
  const activeCerts = certifications.filter(c => c.status === 'active');
  const expiredCerts = certifications.filter(c => c.status === 'expired');
  const revokedCerts = certifications.filter(c => c.status === 'revoked');

  const expiringCerts = activeCerts.filter(c =>
    c.expiry_date &&
    (new Date(c.expiry_date).getTime() - new Date().getTime()) < (30 * 24 * 60 * 60 * 1000)
  );

  return (
    <AppLayout>
      <Head title="My Certifications" />

      <div className="container mx-auto px-4 py-8">
        {/* Header */}
        <h1 className="text-3xl font-bold text-gray-900 mb-8">My Certifications</h1>

        {/* Stats Cards */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div className="bg-green-50 border-2 border-green-200 rounded-lg p-6">
              <p className="text-sm text-green-600 uppercase font-semibold">Active Certifications</p>
              <p className="text-3xl font-bold text-green-900 mt-2">{stats.total_active}</p>
            </div>
            <div className="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6">
              <p className="text-sm text-yellow-600 uppercase font-semibold">Expiring Soon (30 days)</p>
              <p className="text-3xl font-bold text-yellow-900 mt-2">{stats.expiring_soon}</p>
            </div>
            <div className="bg-red-50 border-2 border-red-200 rounded-lg p-6">
              <p className="text-sm text-red-600 uppercase font-semibold">Expired</p>
              <p className="text-3xl font-bold text-red-900 mt-2">{stats.expired}</p>
            </div>
          </div>
        )}

        {certifications.length > 0 ? (
          <>
            {/* Active Certifications */}
            {activeCerts.length > 0 && (
              <div className="mb-12">
                <h2 className="text-2xl font-bold text-gray-900 mb-6">Active Certifications</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {activeCerts.map((cert) => (
                    <CertificateCard key={cert.id} certification={cert} />
                  ))}
                </div>
              </div>
            )}

            {/* Expiring Soon */}
            {expiringCerts.length > 0 && (
              <div className="mb-12">
                <h2 className="text-2xl font-bold text-yellow-900 mb-6">Expiring Soon</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {expiringCerts.map((cert) => (
                    <CertificateCard key={cert.id} certification={cert} />
                  ))}
                </div>
              </div>
            )}

            {/* Expired Certifications */}
            {expiredCerts.length > 0 && (
              <div className="mb-12">
                <h2 className="text-2xl font-bold text-red-900 mb-6">Expired Certifications</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {expiredCerts.map((cert) => (
                    <CertificateCard key={cert.id} certification={cert} />
                  ))}
                </div>
              </div>
            )}

            {/* Revoked Certifications */}
            {revokedCerts.length > 0 && (
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-6">Revoked Certifications</h2>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  {revokedCerts.map((cert) => (
                    <CertificateCard key={cert.id} certification={cert} />
                  ))}
                </div>
              </div>
            )}
          </>
        ) : (
          <div className="text-center py-12 bg-gray-50 rounded-lg">
            <p className="text-gray-600 text-lg">You don't have any certifications yet</p>
            <p className="text-gray-500 mt-2">Complete training courses and professional development activities to earn certifications</p>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
