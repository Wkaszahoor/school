import { TeachingResource, PageProps } from '@/types/professional-development';
import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ResourceCard from '@/Components/ProfessionalDevelopment/ResourceCard';
import Pagination from '@/Components/Pagination';

interface Props extends PageProps {
  myResources: {
    data: TeachingResource[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  libraryResources: {
    data: TeachingResource[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  filters?: {
    type?: string;
    subject_id?: string;
    search?: string;
  };
}

export default function ResourcesIndex({ myResources, libraryResources, filters = {} }: Props) {
  const { flash } = usePage().props;
  const [typeFilter, setTypeFilter] = useState(filters.type || '');
  const [subjectFilter, setSubjectFilter] = useState(filters.subject_id || '');
  const [searchTerm, setSearchTerm] = useState(filters.search || '');

  const handleFilter = () => {
    router.get(route('teacher.professional-development.resources.index'), {
      type: typeFilter,
      subject_id: subjectFilter,
      search: searchTerm,
    }, { preserveScroll: true });
  };

  const handleDelete = (id: number) => {
    if (confirm('Delete this resource?')) {
      router.delete(route('teacher.professional-development.resources.destroy', id));
    }
  };

  const handleDownload = (id: number) => {
    router.post(route('teacher.professional-development.resources.download', id));
  };

  return (
    <AppLayout>
      <Head title="Teaching Resources" />

      <div className="container mx-auto px-4 py-8">
        {flash?.success && (
          <div className="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
            {flash.success}
          </div>
        )}

        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <h1 className="text-3xl font-bold text-gray-900">Teaching Resources</h1>
          <Link
            href={route('teacher.professional-development.resources.create')}
            className="px-6 py-3 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
          >
            Upload Resource
          </Link>
        </div>

        {/* My Resources Section */}
        <div className="mb-12">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">My Resources ({myResources.total})</h2>
          {myResources.data.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {myResources.data.map((resource) => (
                  <ResourceCard
                    key={resource.id}
                    resource={resource}
                    canEdit={true}
                    onDelete={handleDelete}
                  />
                ))}
              </div>
              {myResources && myResources.last_page > 1 && (
                <Pagination data={myResources} />
              )}
            </>
          ) : (
            <p className="text-gray-600 text-center py-8">You haven't uploaded any resources yet</p>
          )}
          <hr className="my-8" />
        </div>

        {/* Resource Library Section */}
        <div>
          <h2 className="text-2xl font-bold text-gray-900 mb-6">Resource Library ({libraryResources.total})</h2>

          {/* Filters */}
          <div className="bg-white rounded-lg shadow-md p-6 mb-8">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                  placeholder="Search resources..."
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select
                  value={typeFilter}
                  onChange={(e) => setTypeFilter(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Types</option>
                  <option value="pdf">PDF</option>
                  <option value="video">Video</option>
                  <option value="document">Document</option>
                  <option value="image">Image</option>
                  <option value="url">URL</option>
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <select
                  value={subjectFilter}
                  onChange={(e) => setSubjectFilter(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                  <option value="">All Subjects</option>
                </select>
              </div>
              <div className="flex items-end">
                <button
                  onClick={handleFilter}
                  className="w-full px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
                >
                  Search
                </button>
              </div>
            </div>
          </div>

          {/* Library Grid */}
          {libraryResources.data.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {libraryResources.data.map((resource) => (
                  <ResourceCard
                    key={resource.id}
                    resource={resource}
                    canEdit={false}
                    onDownload={handleDownload}
                  />
                ))}
              </div>
              {libraryResources && libraryResources.last_page > 1 && (
                <Pagination data={libraryResources} />
              )}
            </>
          ) : (
            <div className="text-center py-12 bg-gray-50 rounded-lg">
              <p className="text-gray-600 text-lg">No resources found in library</p>
            </div>
          )}
        </div>
      </div>
    </AppLayout>
  );
}
