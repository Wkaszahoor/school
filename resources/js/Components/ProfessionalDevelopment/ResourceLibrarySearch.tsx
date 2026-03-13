import React, { useState } from 'react';
import { FunnelIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface ResourceLibrarySearchProps {
    onFilter: (filters: {
        search: string;
        resource_type: string;
        subject_id: string;
        topic_category: string;
    }) => void;
    resourceTypes?: Array<{ id: string; name: string }>;
    subjects?: Array<{ id: number; subject_name: string }>;
    topicCategories?: Array<{ id: string; name: string }>;
}

export default function ResourceLibrarySearch({
    onFilter, resourceTypes = [], subjects = [], topicCategories = []
}: ResourceLibrarySearchProps) {
    const [search, setSearch] = useState('');
    const [resourceType, setResourceType] = useState('');
    const [subjectId, setSubjectId] = useState('');
    const [topicCategory, setTopicCategory] = useState('');

    const handleApplyFilters = () => {
        onFilter({ search, resource_type: resourceType, subject_id: subjectId, topic_category: topicCategory });
    };

    const handleClearFilters = () => {
        setSearch('');
        setResourceType('');
        setSubjectId('');
        setTopicCategory('');
        onFilter({ search: '', resource_type: '', subject_id: '', topic_category: '' });
    };

    const hasFilters = search || resourceType || subjectId || topicCategory;

    return (
        <div className="card">
            <div className="card-header border-b border-gray-200">
                <h3 className="card-title flex items-center gap-2">
                    <FunnelIcon className="w-5 h-5" />
                    Filter Resources
                </h3>
            </div>
            <div className="card-body space-y-4">
                <div>
                    <label htmlFor="search" className="block text-sm font-medium text-gray-900 mb-1">
                        Search
                    </label>
                    <input
                        id="search"
                        type="text"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Search by title…"
                        className="form-input"
                    />
                </div>

                <div>
                    <label htmlFor="resourceType" className="block text-sm font-medium text-gray-900 mb-1">
                        Resource Type
                    </label>
                    <select
                        id="resourceType"
                        value={resourceType}
                        onChange={(e) => setResourceType(e.target.value)}
                        className="form-select"
                    >
                        <option value="">All Types</option>
                        {resourceTypes.map(type => (
                            <option key={type.id} value={type.id}>
                                {type.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="subject" className="block text-sm font-medium text-gray-900 mb-1">
                        Subject
                    </label>
                    <select
                        id="subject"
                        value={subjectId}
                        onChange={(e) => setSubjectId(e.target.value)}
                        className="form-select"
                    >
                        <option value="">All Subjects</option>
                        {subjects.map(subject => (
                            <option key={subject.id} value={subject.id}>
                                {subject.subject_name}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label htmlFor="topicCategory" className="block text-sm font-medium text-gray-900 mb-1">
                        Topic Category
                    </label>
                    <input
                        id="topicCategory"
                        type="text"
                        value={topicCategory}
                        onChange={(e) => setTopicCategory(e.target.value)}
                        placeholder="Search by topic…"
                        className="form-input"
                    />
                </div>

                <div className="flex gap-2 pt-2">
                    <button
                        onClick={handleApplyFilters}
                        className="btn btn-primary flex-1"
                    >
                        Apply Filters
                    </button>
                    {hasFilters && (
                        <button
                            onClick={handleClearFilters}
                            className="btn btn-secondary flex-1"
                        >
                            <XMarkIcon className="w-4 h-4" />
                            Clear
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
