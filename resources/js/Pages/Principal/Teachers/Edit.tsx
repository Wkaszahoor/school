import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, CameraIcon, XMarkIcon } from '@heroicons/react/24/outline';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, TeacherProfile } from '@/types';

interface Props extends PageProps {
    teacher: TeacherProfile;
}

export default function EditTeacher({ teacher }: Props) {
    const [photoPreview, setPhotoPreview] = useState<string | null>(teacher.photo ? `/storage/${teacher.photo}` : null);
    const { data, setData, put, processing, errors } = useForm({
        name: teacher.user?.name ?? '',
        email: teacher.user?.email ?? '',
        phone: teacher.phone ?? '',
        gender: teacher.gender ?? '',
        dob: teacher.dob ?? '',
        cnic: teacher.cnic ?? '',
        qualification: teacher.qualification ?? '',
        specialization: teacher.specialisation ?? '',
        experience_years: String(teacher.experience_years ?? ''),
        bio: teacher.bio ?? '',
        previous_school: teacher.previous_school ?? '',
        achievements: teacher.achievements ?? '',
        photo: null as File | null,
    });

    const handlePhotoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('photo', file);
            const reader = new FileReader();
            reader.onloadend = () => {
                setPhotoPreview(reader.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const clearPhoto = () => {
        setData('photo', null);
        setPhotoPreview(null);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route('principal.teachers.update', teacher.id), {
            forceFormData: true,
        });
    };

    return (
        <AppLayout title={`Edit — ${teacher.user?.name}`}>
            <Head title={`Edit — ${teacher.user?.name}`} />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('principal.teachers.show', teacher.id)} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Edit Teacher</h1>
                        <p className="page-subtitle">{teacher.user?.name} · {teacher.employee_id}</p>
                    </div>
                </div>
            </div>

            <form onSubmit={handleSubmit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                    <div className="lg:col-span-2 space-y-5">
                        {/* Basic Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Basic Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group col-span-2">
                                        <label className="form-label">Full Name</label>
                                        <input
                                            className="form-input"
                                            value={data.name}
                                            onChange={e => setData('name', e.target.value)}
                                        />
                                        {errors.name && <p className="text-red-600 text-sm mt-1">{errors.name}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Email</label>
                                        <input
                                            type="email"
                                            className="form-input"
                                            value={data.email}
                                            onChange={e => setData('email', e.target.value)}
                                        />
                                        {errors.email && <p className="text-red-600 text-sm mt-1">{errors.email}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Phone</label>
                                        <input
                                            type="tel"
                                            className="form-input"
                                            value={data.phone}
                                            onChange={e => setData('phone', e.target.value)}
                                        />
                                        {errors.phone && <p className="text-red-600 text-sm mt-1">{errors.phone}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Personal Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Personal Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Gender</label>
                                        <select
                                            className="form-input"
                                            value={data.gender}
                                            onChange={e => setData('gender', e.target.value)}
                                        >
                                            <option value="">Select Gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                        {errors.gender && <p className="text-red-600 text-sm mt-1">{errors.gender}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Date of Birth</label>
                                        <input
                                            type="date"
                                            className="form-input"
                                            value={data.dob}
                                            onChange={e => setData('dob', e.target.value)}
                                        />
                                        {errors.dob && <p className="text-red-600 text-sm mt-1">{errors.dob}</p>}
                                    </div>
                                    <div className="form-group col-span-2">
                                        <label className="form-label">CNIC</label>
                                        <input
                                            className="form-input"
                                            placeholder="00000-0000000-0"
                                            value={data.cnic}
                                            onChange={e => setData('cnic', e.target.value)}
                                        />
                                        {errors.cnic && <p className="text-red-600 text-sm mt-1">{errors.cnic}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Academic Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Academic Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="form-group">
                                        <label className="form-label">Qualification</label>
                                        <input
                                            className="form-input"
                                            placeholder="e.g., B.A, M.Sc"
                                            value={data.qualification}
                                            onChange={e => setData('qualification', e.target.value)}
                                        />
                                        {errors.qualification && <p className="text-red-600 text-sm mt-1">{errors.qualification}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Specialization</label>
                                        <input
                                            className="form-input"
                                            placeholder="e.g., Mathematics, English"
                                            value={data.specialization}
                                            onChange={e => setData('specialization', e.target.value)}
                                        />
                                        {errors.specialization && <p className="text-red-600 text-sm mt-1">{errors.specialization}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Experience (Years)</label>
                                        <input
                                            type="number"
                                            min="0"
                                            className="form-input"
                                            value={data.experience_years}
                                            onChange={e => setData('experience_years', e.target.value)}
                                        />
                                        {errors.experience_years && <p className="text-red-600 text-sm mt-1">{errors.experience_years}</p>}
                                    </div>
                                    <div className="form-group">
                                        <label className="form-label">Previous School</label>
                                        <input
                                            className="form-input"
                                            value={data.previous_school}
                                            onChange={e => setData('previous_school', e.target.value)}
                                        />
                                        {errors.previous_school && <p className="text-red-600 text-sm mt-1">{errors.previous_school}</p>}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Additional Information */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Additional Information</p></div>
                            <div className="card-body space-y-4">
                                <div className="form-group">
                                    <label className="form-label">Bio</label>
                                    <textarea
                                        className="form-textarea"
                                        rows={3}
                                        value={data.bio}
                                        onChange={e => setData('bio', e.target.value)}
                                    />
                                    {errors.bio && <p className="text-red-600 text-sm mt-1">{errors.bio}</p>}
                                </div>
                                <div className="form-group">
                                    <label className="form-label">Achievements</label>
                                    <textarea
                                        className="form-textarea"
                                        rows={3}
                                        value={data.achievements}
                                        onChange={e => setData('achievements', e.target.value)}
                                    />
                                    {errors.achievements && <p className="text-red-600 text-sm mt-1">{errors.achievements}</p>}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-5">
                        {/* Photo Upload */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Teacher Photo</p></div>
                            <div className="card-body space-y-4">
                                {/* Photo Preview */}
                                <div className="flex justify-center">
                                    {photoPreview ? (
                                        <div className="relative">
                                            <img src={photoPreview} alt="Teacher" className="w-40 h-40 rounded-lg object-cover border-2 border-indigo-200" />
                                            <button
                                                type="button"
                                                onClick={clearPhoto}
                                                className="absolute top-2 right-2 bg-red-600 text-white rounded-full p-1.5 hover:bg-red-700"
                                            >
                                                <XMarkIcon className="w-4 h-4" />
                                            </button>
                                        </div>
                                    ) : (
                                        <div className="w-40 h-40 rounded-lg bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">
                                            <div className="text-center">
                                                <CameraIcon className="w-8 h-8 mx-auto text-gray-400 mb-2" />
                                                <p className="text-sm text-gray-500">No photo</p>
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* File Input */}
                                <div className="form-group">
                                    <label className="form-label">Upload Photo</label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={handlePhotoChange}
                                        className="form-input"
                                    />
                                    <p className="text-xs text-gray-500 mt-2">JPG, PNG or GIF (Max. 5MB)</p>
                                </div>
                            </div>
                        </div>

                        {/* Save Changes */}
                        <div className="card">
                            <div className="card-header"><p className="card-title">Save Changes</p></div>
                            <div className="card-body space-y-3">
                                <button type="submit" disabled={processing} className="btn-primary w-full">
                                    {processing ? 'Saving…' : 'Save Changes'}
                                </button>
                                <Link href={route('principal.teachers.show', teacher.id)} className="btn-secondary w-full text-center">
                                    Cancel
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </AppLayout>
    );
}
