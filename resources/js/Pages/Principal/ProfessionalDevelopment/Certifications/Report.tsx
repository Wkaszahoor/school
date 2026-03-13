import { Certification, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
  certifications: Certification[];
  stats?: {
    total_active: number;
    total_expired: number;
    total_revoked: number;
    by_type: Record<string, number>;
  };
  topTeachers?: Array<{
    name: string;
    count: number;
  }>;
}

export default function CertificationsReport({ certifications, stats, topTeachers }: Props) {
  const [selectedType, setSelectedType] = useState<string | null>(null);

  const typeLabels: Record<string, string> = {
    course_completion: 'Course Completion',
    professional_development_hours: 'Professional Development Hours',
    skill_mastery: 'Skill Mastery',
    custom: 'Custom',
  };

  const filteredByType = selectedType
    ? certifications.filter(c => c.certification_type === selectedType)
    : certifications;

  return (
    <AppLayout>
      <Head title="Certifications Report" />

      <div className="container mx-auto px-4 py-8">
        {/* Header */}
        <h1 className="text-3xl font-bold text-gray-900 mb-8">Certifications Report</h1>

        {/* Summary Stats */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div className="bg-green-50 border-2 border-green-200 rounded-lg p-6">
              <p className="text-sm text-green-600 uppercase font-semibold">Active</p>
              <p className="text-4xl font-bold text-green-900 mt-2">{stats.total_active}</p>
            </div>
            <div className="bg-yellow-50 border-2 border-yellow-200 rounded-lg p-6">
              <p className="text-sm text-yellow-600 uppercase font-semibold">Expired</p>
              <p className="text-4xl font-bold text-yellow-900 mt-2">{stats.total_expired}</p>
            </div>
            <div className="bg-red-50 border-2 border-red-200 rounded-lg p-6">
              <p className="text-sm text-red-600 uppercase font-semibold">Revoked</p>
              <p className="text-4xl font-bold text-red-900 mt-2">{stats.total_revoked}</p>
            </div>
            <div className="bg-blue-50 border-2 border-blue-200 rounded-lg p-6">
              <p className="text-sm text-blue-600 uppercase font-semibold">Total</p>
              <p className="text-4xl font-bold text-blue-900 mt-2">{certifications.length}</p>
            </div>
          </div>
        )}

        {/* Certifications by Type */}
        {stats && Object.keys(stats.by_type).length > 0 && (
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <div className="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-6">Certifications by Type</h2>
              <div className="space-y-4">
                {Object.entries(stats.by_type).map(([type, count]) => {
                  const percentage = count > 0 ? (count / certifications.length) * 100 : 0;
                  return (
                    <div key={type} className="cursor-pointer" onClick={() => setSelectedType(selectedType === type ? null : type)}>
                      <div className="flex items-center justify-between mb-2">
                        <p className="font-medium text-gray-900">{typeLabels[type] || type}</p>
                        <p className="text-sm text-gray-600">{count} ({percentage.toFixed(1)}%)</p>
                      </div>
                      <div className="w-full bg-gray-200 rounded-full h-3">
                        <div
                          className="bg-blue-500 h-3 rounded-full transition-all"
                          style={{ width: `${percentage}%` }}
                        />
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>

            {/* Top Teachers */}
            {topTeachers && topTeachers.length > 0 && (
              <div className="bg-white rounded-lg shadow-md p-6">
                <h2 className="text-lg font-semibold text-gray-900 mb-6">Top Certified Teachers</h2>
                <div className="space-y-3">
                  {topTeachers.slice(0, 5).map((teacher, idx) => (
                    <div key={idx} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                      <div className="flex items-center gap-3 flex-1">
                        <span className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white text-sm font-bold">
                          {idx + 1}
                        </span>
                        <span className="font-medium text-gray-900 truncate">{teacher.name}</span>
                      </div>
                      <span className="text-sm font-semibold text-blue-600">{teacher.count}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}

        {/* Detailed Table */}
        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="p-6 border-b border-gray-200">
            <h2 className="text-lg font-semibold text-gray-900">
              {selectedType ? `${typeLabels[selectedType] || selectedType} Certifications` : 'All Certifications'}
            </h2>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b-2 border-gray-300">
                <tr>
                  <th className="text-left py-3 px-4 font-semibold text-gray-900">Teacher</th>
                  <th className="text-left py-3 px-4 font-semibold text-gray-900">Title</th>
                  <th className="text-center py-3 px-4 font-semibold text-gray-900">Type</th>
                  <th className="text-center py-3 px-4 font-semibold text-gray-900">Issued Date</th>
                  <th className="text-center py-3 px-4 font-semibold text-gray-900">Expiry Date</th>
                  <th className="text-center py-3 px-4 font-semibold text-gray-900">Status</th>
                </tr>
              </thead>
              <tbody>
                {filteredByType.length > 0 ? (
                  filteredByType.map((cert, idx) => (
                    <tr key={cert.id} className={`border-b ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}>
                      <td className="py-3 px-4">
                        <p className="font-medium text-gray-900">{cert.teacher?.name}</p>
                      </td>
                      <td className="py-3 px-4 text-gray-600">{cert.title}</td>
                      <td className="text-center py-3 px-4 text-xs">
                        <span className="px-2 py-1 bg-gray-100 rounded text-gray-700">
                          {typeLabels[cert.certification_type] || cert.certification_type}
                        </span>
                      </td>
                      <td className="text-center py-3 px-4 text-gray-600">
                        {new Date(cert.issued_date).toLocaleDateString('en-GB')}
                      </td>
                      <td className="text-center py-3 px-4 text-gray-600">
                        {cert.expiry_date ? new Date(cert.expiry_date).toLocaleDateString('en-GB') : 'N/A'}
                      </td>
                      <td className="text-center py-3 px-4">
                        <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                          cert.status === 'active' ? 'bg-green-100 text-green-800' :
                          cert.status === 'expired' ? 'bg-red-100 text-red-800' :
                          'bg-gray-100 text-gray-800'
                        }`}>
                          {cert.status.charAt(0).toUpperCase() + cert.status.slice(1)}
                        </span>
                      </td>
                    </tr>
                  ))
                ) : (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-gray-600">
                      No certifications found
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Export Button */}
        <div className="mt-8 flex gap-2">
          <button
            onClick={() => window.print()}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            Print Report
          </button>
          <button
            onClick={() => {
              // CSV Export logic would go here
              alert('Export functionality to be implemented');
            }}
            className="px-6 py-3 bg-green-500 text-white rounded-lg font-medium hover:bg-green-600 transition"
          >
            Export to CSV
          </button>
        </div>
      </div>
    </AppLayout>
  );
}
