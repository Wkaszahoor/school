import { Head, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, LinkIcon, PlusIcon, TrashIcon, SparklesIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps } from '@/types';
import { useState } from 'react';

interface Instructor {
    id: number;
    name: string;
    email: string;
}

interface Props extends PageProps {
    instructors?: Instructor[];
    course?: any; // For edit mode
}

export default function CreateCourse({ instructors = [], course }: Props) {
    const { data, setData, post, put, processing, errors } = useForm({
        course_code: course?.course_code || '',
        course_name: course?.course_name || '',
        description: course?.description || '',
        instructor_id: course?.instructor_id || '',
        course_type: course?.course_type || 'workshop',
        level: course?.level || 'beginner',
        objectives: course?.objectives || '',
        duration_hours: course?.duration_hours || 1,
        max_participants: course?.max_participants || '',
        start_date: course?.start_date || '',
        end_date: course?.end_date || '',
        location: course?.location || '',
        status: course?.status || 'draft',
        cost: course?.cost || 0,
    });

    const [materials, setMaterials] = useState<Array<{id: string; url: string; title: string}>>([]);
    const [newLink, setNewLink] = useState({ title: '', url: '' });
    const [uploadedFiles, setUploadedFiles] = useState<File[]>([]);
    const [generatedQuestions, setGeneratedQuestions] = useState<string[]>([]);
    const [isGenerating, setIsGenerating] = useState(false);
    const [showQuestionGenerator, setShowQuestionGenerator] = useState(false);
    const [isCustomType, setIsCustomType] = useState(data.course_type === '' || !['workshop', 'certification', 'seminar', 'online', 'conference'].includes(data.course_type));
    const [customTypeName, setCustomTypeName] = useState(
        isCustomType && data.course_type ? data.course_type : ''
    );

    const addHyperlink = () => {
        if (newLink.title.trim() && newLink.url.trim()) {
            setMaterials([...materials, { id: Date.now().toString(), ...newLink }]);
            setNewLink({ title: '', url: '' });
        }
    };

    const removeHyperlink = (id: string) => {
        setMaterials(materials.filter(m => m.id !== id));
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            setUploadedFiles([...uploadedFiles, ...Array.from(e.target.files)]);
        }
    };

    const removeFile = (index: number) => {
        setUploadedFiles(uploadedFiles.filter((_, i) => i !== index));
    };

    const generateQuestionsWithAI = async () => {
        if (!data.objectives.trim()) {
            alert('Please enter learning objectives first');
            return;
        }

        setIsGenerating(true);
        try {
            // Simulated AI response - in production, call your AI API
            const mockQuestions = [
                `What are the main learning objectives for "${data.course_name}"?`,
                `How would you apply the concepts from this course in your work?`,
                `What challenges might arise when implementing the knowledge from this course?`,
                `Can you summarize the key takeaways in 3-5 bullet points?`,
                `How does this course relate to your previous knowledge and experience?`,
            ];
            setGeneratedQuestions(mockQuestions);
            setShowQuestionGenerator(true);
        } catch (error) {
            alert('Failed to generate questions');
        } finally {
            setIsGenerating(false);
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (course) {
            put(route('principal.professional-development.training-courses.update', course.id));
        } else {
            post(route('principal.professional-development.training-courses.store'));
        }
    };

    return (
        <AppLayout title={course ? 'Edit Training Course' : 'Create Training Course'}>
            <Head title={course ? 'Edit Training Course' : 'Create Training Course'} />

            <div className="page-header">
                <div>
                    <h1 className="page-title">{course ? 'Edit Training Course' : 'Create Training Course'}</h1>
                    <p className="page-subtitle">{course ? 'Update course details' : 'Add a new professional development course'}</p>
                </div>
            </div>

            <div className="card">
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="code" className="block text-sm font-medium text-gray-900 mb-1">
                                Course Code <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="code"
                                type="text"
                                value={data.course_code}
                                onChange={(e) => setData('course_code', e.target.value)}
                                className="form-input"
                                placeholder="e.g., TC001"
                                required
                            />
                            {errors.course_code && <p className="text-sm text-red-600 mt-1">{errors.course_code}</p>}
                        </div>

                        <div>
                            <label htmlFor="name" className="block text-sm font-medium text-gray-900 mb-1">
                                Course Name <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.course_name}
                                onChange={(e) => setData('course_name', e.target.value)}
                                className="form-input"
                                placeholder="e.g., Advanced Classroom Management"
                                required
                            />
                            {errors.course_name && <p className="text-sm text-red-600 mt-1">{errors.course_name}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="description" className="block text-sm font-medium text-gray-900 mb-1">
                            Description
                        </label>
                        <textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            className="form-textarea"
                            rows={4}
                            placeholder="Course description and learning outcomes…"
                        />
                        {errors.description && <p className="text-sm text-red-600 mt-1">{errors.description}</p>}
                    </div>

                    <div>
                        <label htmlFor="instructor" className="block text-sm font-medium text-gray-900 mb-1">
                            Instructor
                        </label>
                        <select
                            id="instructor"
                            value={data.instructor_id}
                            onChange={(e) => setData('instructor_id', e.target.value)}
                            className="form-select"
                        >
                            <option value="">-- Select Instructor --</option>
                            {instructors.map(instructor => (
                                <option key={instructor.id} value={instructor.id}>
                                    {instructor.name}
                                </option>
                            ))}
                        </select>
                        {errors.instructor_id && <p className="text-sm text-red-600 mt-1">{errors.instructor_id}</p>}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="type" className="block text-sm font-medium text-gray-900 mb-1">
                                Course Type <span className="text-red-600">*</span>
                            </label>
                            {isCustomType ? (
                                <input
                                    type="text"
                                    value={customTypeName}
                                    onChange={(e) => {
                                        setCustomTypeName(e.target.value);
                                        setData('course_type', e.target.value);
                                    }}
                                    placeholder="e.g., English B1 Test, Advanced Python"
                                    className="form-input"
                                    required
                                />
                            ) : (
                                <select
                                    id="type"
                                    value={data.course_type}
                                    onChange={(e) => {
                                        if (e.target.value === 'custom') {
                                            setIsCustomType(true);
                                            setCustomTypeName('');
                                            setData('course_type', '');
                                        } else {
                                            setData('course_type', e.target.value as any);
                                        }
                                    }}
                                    className="form-select"
                                    required
                                >
                                    <option value="">-- Select Type --</option>
                                    <option value="workshop">Workshop</option>
                                    <option value="certification">Certification</option>
                                    <option value="seminar">Seminar</option>
                                    <option value="online">Online</option>
                                    <option value="conference">Conference</option>
                                    <option value="custom">Custom Type...</option>
                                </select>
                            )}
                            {isCustomType && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        setIsCustomType(false);
                                        setData('course_type', 'workshop');
                                    }}
                                    className="text-xs text-blue-600 hover:underline mt-2"
                                >
                                    ← Back to predefined types
                                </button>
                            )}
                            {errors.course_type && <p className="text-sm text-red-600 mt-1">{errors.course_type}</p>}
                        </div>

                        <div>
                            <label htmlFor="level" className="block text-sm font-medium text-gray-900 mb-1">
                                Level <span className="text-red-600">*</span>
                            </label>
                            <select
                                id="level"
                                value={data.level}
                                onChange={(e) => setData('level', e.target.value as any)}
                                className="form-select"
                                required
                            >
                                <option value="beginner">Beginner</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="advanced">Advanced</option>
                            </select>
                            {errors.level && <p className="text-sm text-red-600 mt-1">{errors.level}</p>}
                        </div>
                    </div>

                    <div>
                        <label htmlFor="objectives" className="block text-sm font-medium text-gray-900 mb-1">
                            Learning Objectives
                        </label>
                        <textarea
                            id="objectives"
                            value={data.objectives}
                            onChange={(e) => setData('objectives', e.target.value)}
                            className="form-textarea"
                            rows={3}
                            placeholder="List the main learning objectives…"
                        />
                        {errors.objectives && <p className="text-sm text-red-600 mt-1">{errors.objectives}</p>}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label htmlFor="duration" className="block text-sm font-medium text-gray-900 mb-1">
                                Duration (hours) <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="duration"
                                type="number"
                                min="1"
                                value={data.duration_hours}
                                onChange={(e) => setData('duration_hours', parseInt(e.target.value) || 1)}
                                className="form-input"
                                required
                            />
                            {errors.duration_hours && <p className="text-sm text-red-600 mt-1">{errors.duration_hours}</p>}
                        </div>

                        <div>
                            <label htmlFor="cost" className="block text-sm font-medium text-gray-900 mb-1">
                                Cost <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="cost"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.cost}
                                onChange={(e) => setData('cost', parseFloat(e.target.value) || 0)}
                                className="form-input"
                                required
                            />
                            {errors.cost && <p className="text-sm text-red-600 mt-1">{errors.cost}</p>}
                        </div>

                        <div>
                            <label htmlFor="participants" className="block text-sm font-medium text-gray-900 mb-1">
                                Max Participants
                            </label>
                            <input
                                id="participants"
                                type="number"
                                min="1"
                                value={data.max_participants}
                                onChange={(e) => setData('max_participants', e.target.value ? parseInt(e.target.value) : '')}
                                className="form-input"
                            />
                            {errors.max_participants && <p className="text-sm text-red-600 mt-1">{errors.max_participants}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="start" className="block text-sm font-medium text-gray-900 mb-1">
                                Start Date <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="start"
                                type="date"
                                value={data.start_date}
                                onChange={(e) => setData('start_date', e.target.value)}
                                className="form-input"
                                required
                            />
                            {errors.start_date && <p className="text-sm text-red-600 mt-1">{errors.start_date}</p>}
                        </div>

                        <div>
                            <label htmlFor="end" className="block text-sm font-medium text-gray-900 mb-1">
                                End Date <span className="text-red-600">*</span>
                            </label>
                            <input
                                id="end"
                                type="date"
                                value={data.end_date}
                                onChange={(e) => setData('end_date', e.target.value)}
                                className="form-input"
                                required
                            />
                            {errors.end_date && <p className="text-sm text-red-600 mt-1">{errors.end_date}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label htmlFor="location" className="block text-sm font-medium text-gray-900 mb-1">
                                Location
                            </label>
                            <input
                                id="location"
                                type="text"
                                value={data.location}
                                onChange={(e) => setData('location', e.target.value)}
                                className="form-input"
                                placeholder="e.g., Main Hall, Room 101"
                            />
                            {errors.location && <p className="text-sm text-red-600 mt-1">{errors.location}</p>}
                        </div>

                        <div>
                            <label htmlFor="status" className="block text-sm font-medium text-gray-900 mb-1">
                                Status <span className="text-red-600">*</span>
                            </label>
                            <select
                                id="status"
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value as any)}
                                className="form-select"
                                required
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            {errors.status && <p className="text-sm text-red-600 mt-1">{errors.status}</p>}
                        </div>
                    </div>

                    {/* Hyperlink Resources Section */}
                    <div className="border-t pt-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <LinkIcon className="w-5 h-5" />
                            Learning Resources (Hyperlinks)
                        </h3>
                        <div className="space-y-3">
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        Resource Title
                                    </label>
                                    <input
                                        type="text"
                                        value={newLink.title}
                                        onChange={(e) => setNewLink({...newLink, title: e.target.value})}
                                        placeholder="e.g., Best Practices Guide"
                                        className="form-input"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">
                                        URL
                                    </label>
                                    <input
                                        type="url"
                                        value={newLink.url}
                                        onChange={(e) => setNewLink({...newLink, url: e.target.value})}
                                        placeholder="https://example.com/resource"
                                        className="form-input"
                                    />
                                </div>
                                <div className="flex items-end">
                                    <button
                                        type="button"
                                        onClick={addHyperlink}
                                        className="w-full btn btn-primary flex items-center justify-center gap-2"
                                    >
                                        <PlusIcon className="w-4 h-4" />
                                        Add Link
                                    </button>
                                </div>
                            </div>

                            {materials.length > 0 && (
                                <div className="mt-4 space-y-2">
                                    <p className="text-sm font-medium text-gray-900">Added Resources:</p>
                                    {materials.map((material) => (
                                        <div key={material.id} className="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                            <div>
                                                <p className="font-medium text-sm text-gray-900">{material.title}</p>
                                                <a href={material.url} target="_blank" rel="noopener noreferrer" className="text-xs text-blue-600 hover:underline">
                                                    {material.url}
                                                </a>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeHyperlink(material.id)}
                                                className="text-red-600 hover:text-red-700 p-2"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* File Upload Section */}
                    <div className="border-t pt-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Course Materials (Files)</h3>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Upload PDF, Word, PowerPoint, or other documents
                            </label>
                            <input
                                type="file"
                                multiple
                                onChange={handleFileUpload}
                                className="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg p-2.5 cursor-pointer"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                            />

                            {uploadedFiles.length > 0 && (
                                <div className="mt-4 space-y-2">
                                    <p className="text-sm font-medium text-gray-900">Uploaded Files:</p>
                                    {uploadedFiles.map((file, index) => (
                                        <div key={index} className="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200">
                                            <div>
                                                <p className="font-medium text-sm text-gray-900">{file.name}</p>
                                                <p className="text-xs text-gray-500">{(file.size / 1024 / 1024).toFixed(2)} MB</p>
                                            </div>
                                            <button
                                                type="button"
                                                onClick={() => removeFile(index)}
                                                className="text-red-600 hover:text-red-700 p-2"
                                            >
                                                <TrashIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* AI Question Generator Section */}
                    <div className="border-t pt-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <SparklesIcon className="w-5 h-5 text-purple-600" />
                            Generate Assignment Questions with AI
                        </h3>
                        <p className="text-sm text-gray-600 mb-4">
                            Automatically generate quiz questions based on your course objectives
                        </p>
                        <button
                            type="button"
                            onClick={generateQuestionsWithAI}
                            disabled={isGenerating || !data.objectives.trim()}
                            className="btn btn-primary flex items-center gap-2 mb-4"
                        >
                            <SparklesIcon className="w-4 h-4" />
                            {isGenerating ? 'Generating...' : 'Generate Questions'}
                        </button>

                        {generatedQuestions.length > 0 && (
                            <div className="bg-purple-50 border border-purple-200 rounded-lg p-4">
                                <h4 className="font-semibold text-gray-900 mb-3">Generated Questions:</h4>
                                <div className="space-y-3">
                                    {generatedQuestions.map((question, index) => (
                                        <div key={index} className="flex gap-3 p-3 bg-white rounded-lg border border-purple-100">
                                            <span className="font-semibold text-purple-600 flex-shrink-0">{index + 1}.</span>
                                            <p className="text-sm text-gray-900">{question}</p>
                                        </div>
                                    ))}
                                </div>
                                <p className="text-xs text-gray-500 mt-3">
                                    Tip: You can copy these questions and add them to your course assignments
                                </p>
                            </div>
                        )}
                    </div>

                    {/* Submit Buttons */}
                    <div className="flex gap-2 justify-end pt-4 border-t border-gray-200">
                        <a href={route('principal.professional-development.training-courses.index')} className="btn btn-secondary">
                            <ArrowLeftIcon className="w-4 h-4" />
                            Cancel
                        </a>
                        <button type="submit" className="btn btn-primary" disabled={processing}>
                            {processing ? 'Saving…' : 'Save Course'}
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
