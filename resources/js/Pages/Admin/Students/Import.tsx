import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeftIcon, ArrowUpTrayIcon } from '@heroicons/react/24/outline';
import * as XLSX from 'xlsx';
import AppLayout from '@/Layouts/AppLayout';
import type { PageProps, SchoolClass } from '@/types';

interface ImportedStudent {
    admission_no: string;
    full_name: string;
    student_cnic?: string;
    dob: string;
    gender: string;
    class_id: string;
    [key: string]: string | number | undefined;
}

interface Props extends PageProps {
    classes: SchoolClass[];
}

export default function AdminImportStudents({ classes }: Props) {
    const [students, setStudents] = useState<ImportedStudent[]>([]);
    const [fileName, setFileName] = useState<string>('');
    const [errors, setErrors] = useState<string[]>([]);
    const { post, processing } = useForm({});

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setFileName(file.name);
        setErrors([]);

        const reader = new FileReader();
        reader.onload = (event) => {
            try {
                const workbook = XLSX.read(event.target?.result, { type: 'binary' });
                const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                const rawData = XLSX.utils.sheet_to_json(worksheet);

                // Validate and map data
                const validatedStudents = validateAndMapStudents(rawData);
                setStudents(validatedStudents);
            } catch (error) {
                setErrors([`Error reading file: ${error instanceof Error ? error.message : 'Unknown error'}`]);
            }
        };
        reader.readAsBinaryString(file);
    };

    const validateAndMapStudents = (data: any[]): ImportedStudent[] => {
        const newErrors: string[] = [];
        const validated: ImportedStudent[] = [];

        data.forEach((row, index) => {
            try {
                const student: ImportedStudent = {
                    admission_no: row['ID'] || row['admission_no'] || `ADM${Date.now()}${index}`,
                    full_name: row['Student_Name'] || row['full_name'] || '',
                    student_cnic: row['Student_CNIC'] || '',
                    dob: formatDate(row['Date_of_Birth'] || row['dob'] || ''),
                    gender: (row['Gender'] || 'male').toLowerCase(),
                    class_id: findClassId(row['Class'] || ''),
                    father_name: row['Father_Name'] || '',
                    father_cnic: row['Father_CNIC'] || '',
                    mother_name: row['Mother_Name'] || '',
                    mother_cnic: row['Mother_CNIC'] || '',
                    guardian_name: row['Guardian\'s_Name'] || '',
                    guardian_cnic: row['Guardian\'s_CNIC'] || '',
                    guardian_phone: row['Contact_Number'] || '',
                    guardian_address: row['Address'] || '',
                    phone: row['Contact_Number'] || '',
                    blood_group: row['Blood_Group'] || '',
                    favorite_color: row['Favorite_color'] || '',
                    favorite_food: row['Favorite_Food'] || '',
                    favorite_subject: row['Favorite_Subject'] || '',
                    ambition: row['Ambition'] || '',
                    group_stream: row['Course'] || 'general',
                    semester: row['Semester'] || '',
                    join_date_kort: formatDate(row['Joining_Date'] || ''),
                    is_active: row['Status'] !== 'inactive' ? 1 : 0,
                    reason_left_kort: row['Reason_LeftKORT'] || '',
                    leaving_date: formatDate(row['Leaving_Date'] || ''),
                };

                // Validate required fields
                if (!student.full_name) {
                    throw new Error('Full name is required');
                }
                if (!student.dob) {
                    throw new Error('Date of birth is required');
                }
                if (!student.class_id) {
                    throw new Error('Valid class is required');
                }

                validated.push(student);
            } catch (error) {
                newErrors.push(
                    `Row ${index + 2}: ${error instanceof Error ? error.message : 'Unknown error'}`
                );
            }
        });

        if (newErrors.length > 0) {
            setErrors(newErrors);
        }

        return validated;
    };

    const formatDate = (dateInput: any): string => {
        if (!dateInput) return '';

        // If it's an Excel serial number
        if (typeof dateInput === 'number') {
            const excelDate = new Date((dateInput - 25569) * 86400 * 1000);
            return excelDate.toISOString().split('T')[0];
        }

        // If it's a string
        if (typeof dateInput === 'string') {
            const date = new Date(dateInput);
            if (!isNaN(date.getTime())) {
                return date.toISOString().split('T')[0];
            }
        }

        return '';
    };

    const findClassId = (className: string): string => {
        const match = classes.find(
            (c) => c.class.toLowerCase() === className.toLowerCase() ||
                   c.class.includes(className)
        );
        return match?.id.toString() || '';
    };

    const handleImport = () => {
        if (students.length === 0) {
            setErrors(['No valid students to import']);
            return;
        }

        post(route('admin.import-students.store'), {
            data: { students },
            onError: () => {
                setErrors(['Failed to import students. Please check the data and try again.']);
            },
        } as any);
    };

    const downloadTemplate = () => {
        const template = [
            {
                'ID': '',
                'Photo': '',
                'Student_Name': 'John Doe',
                'Student_CNIC': '12345-1234567-1',
                'Father_Name': 'Ahmed Ali',
                'Father_CNIC': '12345-1234567-1',
                'Mother_Name': 'Fatima Khan',
                'Mother_CNIC': '12345-1234567-1',
                'Guardian\'s_Name': 'Ahmed Ali',
                'Guardian\'s_CNIC': '12345-1234567-1',
                'Address': '123 Main Street',
                'Contact_Number': '+44 7700 000000',
                'Date_of_Birth': '2010-01-15',
                'Favorite_color': 'Blue',
                'Favorite_Food': 'Pizza',
                'Favorite_Subject': 'Mathematics',
                'Ambition': 'Engineer',
                'Class': '9A',
                'Course': 'general',
                'Semester': '1',
                'Joining_Date': '2025-01-15',
                'Gender': 'male',
                'Status': 'active',
                'Reason_LeftKORT': '',
                'Leaving_Date': '',
            },
        ];

        const ws = XLSX.utils.json_to_sheet(template);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Students');
        XLSX.writeFile(wb, 'student_import_template.xlsx');
    };

    return (
        <AppLayout title="Import Students">
            <Head title="Import Students" />

            <div className="page-header">
                <div className="flex items-center gap-3">
                    <Link href={route('admin.students.index')} className="btn-ghost btn-icon">
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h1 className="page-title">Import Students from Excel</h1>
                        <p className="page-subtitle">Bulk import student records from an Excel file</p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <div className="lg:col-span-2 space-y-5">
                    {/* Upload Section */}
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Step 1: Upload Excel File</p>
                        </div>
                        <div className="card-body space-y-4">
                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition">
                                <ArrowUpTrayIcon className="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                <label className="cursor-pointer">
                                    <span className="text-indigo-600 font-semibold hover:underline">
                                        Click to upload
                                    </span>
                                    {' '}or drag and drop
                                    <input
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        onChange={handleFileUpload}
                                        className="hidden"
                                    />
                                </label>
                                <p className="text-sm text-gray-500 mt-2">
                                    {fileName ? `File: ${fileName}` : 'XLSX, XLS, or CSV files up to 10MB'}
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={downloadTemplate}
                                className="btn-secondary w-full"
                            >
                                Download Template
                            </button>
                        </div>
                    </div>

                    {/* Preview Section */}
                    {students.length > 0 && (
                        <div className="card">
                            <div className="card-header">
                                <p className="card-title">Step 2: Review Data ({students.length} students)</p>
                            </div>
                            <div className="card-body">
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b">
                                                <th className="text-left py-2 px-3">Name</th>
                                                <th className="text-left py-2 px-3">Admission No.</th>
                                                <th className="text-left py-2 px-3">DOB</th>
                                                <th className="text-left py-2 px-3">Class</th>
                                                <th className="text-left py-2 px-3">Gender</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {students.slice(0, 5).map((student, idx) => (
                                                <tr key={idx} className="border-b hover:bg-gray-50">
                                                    <td className="py-2 px-3">{student.full_name}</td>
                                                    <td className="py-2 px-3">{student.admission_no}</td>
                                                    <td className="py-2 px-3">{student.dob}</td>
                                                    <td className="py-2 px-3">
                                                        {classes.find(c => c.id.toString() === student.class_id)?.class}
                                                    </td>
                                                    <td className="py-2 px-3 capitalize">{student.gender}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                    {students.length > 5 && (
                                        <p className="text-sm text-gray-600 mt-2 text-center">
                                            ... and {students.length - 5} more
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Errors Section */}
                    {errors.length > 0 && (
                        <div className="card bg-red-50 border border-red-200">
                            <div className="card-header">
                                <p className="card-title text-red-700">Import Errors</p>
                            </div>
                            <div className="card-body">
                                <ul className="space-y-1 text-sm text-red-700">
                                    {errors.map((error, idx) => (
                                        <li key={idx}>• {error}</li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}
                </div>

                {/* Action Sidebar */}
                <div className="space-y-5">
                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Step 3: Import</p>
                        </div>
                        <div className="card-body space-y-3">
                            <div className="bg-indigo-50 rounded-xl p-3 text-sm text-indigo-700">
                                <p className="font-semibold">Ready to import?</p>
                                <p className="mt-1">
                                    {students.length > 0
                                        ? `${students.length} student(s) will be added`
                                        : 'Upload a file to begin'}
                                </p>
                            </div>

                            <button
                                type="button"
                                onClick={handleImport}
                                disabled={processing || students.length === 0}
                                className="btn-primary w-full"
                            >
                                {processing ? (
                                    <><span className="spinner w-4 h-4 border-white/30 border-t-white" /> Importing…</>
                                ) : 'Import Students'}
                            </button>

                            <Link href={route('admin.students.index')} className="btn-secondary w-full text-center">
                                Cancel
                            </Link>
                        </div>
                    </div>

                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Required Fields</p>
                        </div>
                        <div className="card-body text-sm space-y-2">
                            <p className="text-gray-700">
                                <span className="font-semibold">Student_Name</span>
                                <br />
                                <span className="text-xs text-gray-600">Student full name</span>
                            </p>
                            <p className="text-gray-700">
                                <span className="font-semibold">Date_of_Birth</span>
                                <br />
                                <span className="text-xs text-gray-600">YYYY-MM-DD format</span>
                            </p>
                            <p className="text-gray-700">
                                <span className="font-semibold">Gender</span>
                                <br />
                                <span className="text-xs text-gray-600">male, female, or other</span>
                            </p>
                            <p className="text-gray-700">
                                <span className="font-semibold">Class</span>
                                <br />
                                <span className="text-xs text-gray-600">e.g., 9A, 10B, 12A</span>
                            </p>
                        </div>
                    </div>

                    <div className="card">
                        <div className="card-header">
                            <p className="card-title">Supported Fields</p>
                        </div>
                        <div className="card-body text-xs text-gray-600 space-y-1">
                            <p>• Student_CNIC</p>
                            <p>• Father/Mother Name & CNIC</p>
                            <p>• Guardian Info & Address</p>
                            <p>• Contact Number</p>
                            <p>• Blood Group</p>
                            <p>• Favorite Color/Food/Subject</p>
                            <p>• Ambition</p>
                            <p>• Course & Semester</p>
                            <p>• Joining Date</p>
                            <p>• Status & Leaving Details</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
