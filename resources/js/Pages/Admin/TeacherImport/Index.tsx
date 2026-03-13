import AppLayout from '@/Layouts/AppLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowUpTrayIcon, CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import * as XLSX from 'xlsx';

interface TeacherData {
    name: string;
    teaching_subject: string;
    teaching_class: string;
    is_class_teacher: boolean;
}

interface ImportResult {
    created_users: number;
    updated_users: number;
    created_subjects: number;
    created_classes: number;
    assignments_created: number;
    failed: number;
    errors: Array<{ row: number; error: string }>;
    details: Array<{
        name: string;
        email: string;
        subject: string;
        class: string;
        role: string;
        status: string;
        password: string;
    }>;
}

export default function Index() {
    const [teachers, setTeachers] = useState<TeacherData[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [result, setResult] = useState<ImportResult | null>(null);

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        try {
            const reader = new FileReader();
            reader.onload = (event) => {
                try {
                    const arrayBuffer = event.target?.result;
                    const workbook = XLSX.read(arrayBuffer, { type: 'array' });
                    const worksheet = workbook.Sheets[workbook.SheetNames[0]];
                    const data = XLSX.utils.sheet_to_json<TeacherData>(worksheet, {
                        header: 1,
                        defval: '',
                    }) as any[];

                    console.log('Raw data from Excel:', data);

                    // Parse headers and data
                    if (data.length < 2) {
                        alert('Error: Excel file must have headers and at least one data row');
                        return;
                    }

                    const rawHeaders = data[0];
                    console.log('Raw headers:', rawHeaders);

                    const headers = rawHeaders.map((h: any) => String(h).toLowerCase().replace(/[\s\-]+/g, '_'));
                    console.log('Normalized headers:', headers);

                    // Find column indices with flexible matching
                    const nameIdx = headers.findIndex(h => h === 'name');
                    const subjectIdx = headers.findIndex(h => h.includes('subject'));
                    const classIdx = headers.findIndex(h => h.includes('class') && !h.includes('teacher'));
                    const teacherIdx = headers.findIndex(h => h.includes('teacher'));

                    console.log('Column indices:', { nameIdx, subjectIdx, classIdx, teacherIdx });

                    if (nameIdx === -1 || subjectIdx === -1 || classIdx === -1) {
                        alert(`❌ Missing required columns!\n\nFound: ${headers.join(', ')}\n\nExpected:\n• name\n• teaching_subject (or teaching-subject)\n• teaching_class (or teaching-class)\n• class_teacher (optional)`);
                        return;
                    }

                    const parsedTeachers: TeacherData[] = [];

                    for (let i = 1; i < data.length; i++) {
                        const row = data[i];

                        // Skip completely empty rows
                        if (!row || row.every((cell: any) => !cell)) continue;

                        const name = String(row[nameIdx] || '').trim();
                        const subjectsStr = String(row[subjectIdx] || '').trim();
                        const classesStr = String(row[classIdx] || '').trim();
                        const isTeacherStr = String(row[teacherIdx] || '').trim();

                        if (!name || !subjectsStr || !classesStr) {
                            console.warn(`Skipping row ${i + 1} - missing required data`);
                            continue;
                        }

                        // Split comma-separated subjects and classes
                        const subjects = subjectsStr.split(',').map((s: string) => s.trim()).filter((s: string) => s.length > 0);
                        const classes = classesStr.split(',').map((c: string) => c.trim()).filter((c: string) => c.length > 0);

                        // Handle class teacher designation
                        // It might be one of the classes or a specific yes/no value
                        let isClassTeacher = false;
                        if (isTeacherStr.toLowerCase() === 'yes') {
                            isClassTeacher = true;
                        }

                        // Create an entry for each subject-class combination
                        for (const subject of subjects) {
                            for (const className of classes) {
                                parsedTeachers.push({
                                    name,
                                    teaching_subject: subject,
                                    teaching_class: className,
                                    is_class_teacher: isClassTeacher,
                                });
                            }
                        }

                        console.log(`Row ${i + 1}: ${name} - Created ${subjects.length * classes.length} assignments`);
                    }

                    console.log('Parsed teachers:', parsedTeachers);

                    if (parsedTeachers.length === 0) {
                        alert('❌ No valid teacher records found in the file');
                        return;
                    }

                    setTeachers(parsedTeachers);
                    setResult(null);
                } catch (parseError) {
                    alert('Error parsing file: ' + (parseError as any).message);
                    console.error('Parse error:', parseError);
                }
            };
            reader.readAsArrayBuffer(file);
        } catch (error) {
            alert('Error reading file: ' + (error as any).message);
            console.error('File read error:', error);
        }
    };

    const handleImport = async () => {
        if (teachers.length === 0) {
            alert('Please upload a valid Excel file first');
            return;
        }

        setIsLoading(true);

        try {
            const response = await fetch(route('admin.import-teachers.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                },
                body: JSON.stringify({ teachers }),
            });

            if (!response.ok) {
                const error = await response.json();
                alert('Import failed: ' + JSON.stringify(error));
                setIsLoading(false);
                return;
            }

            const data = await response.json();
            console.log('Import response:', data);
            setResult(data);
            setTeachers([]);
            setIsLoading(false);
        } catch (error) {
            alert('Import error: ' + (error as any).message);
            console.error('Import error:', error);
            setIsLoading(false);
        }
    };

    const downloadTemplate = () => {
        const template = [
            ['name', 'teaching-subject', 'teaching-class', 'class teacher'],
            ['Ahmad Ali', 'Mathematics', 'Class 9A', 'yes'],
            ['Fatima Khan', 'English', 'Class 9A', 'no'],
            ['Hassan Raza', 'Science', 'Class 10B', 'no'],
        ];

        const workbook = XLSX.utils.book_new();
        const worksheet = XLSX.utils.aoa_to_sheet(template);
        worksheet['!cols'] = [{ wch: 20 }, { wch: 25 }, { wch: 20 }, { wch: 15 }];

        XLSX.utils.book_append_sheet(workbook, worksheet, 'Teachers');
        XLSX.writeFile(workbook, 'teacher-template.xlsx');
    };

    return (
        <AppLayout title="Import Teachers">
            <Head title="Import Teachers" />

            <div className="page-header">
                <div>
                    <h1 className="page-title">Import Teachers</h1>
                    <p className="page-subtitle">Bulk import teachers with their subject and class assignments</p>
                </div>
            </div>

            {result ? (
                <div className="space-y-6">
                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div className="card">
                            <div className="card-body text-center">
                                <p className="text-gray-600 text-sm">Created Users</p>
                                <p className="text-3xl font-bold text-green-600">{result.created_users}</p>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body text-center">
                                <p className="text-gray-600 text-sm">Updated Users</p>
                                <p className="text-3xl font-bold text-blue-600">{result.updated_users}</p>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body text-center">
                                <p className="text-gray-600 text-sm">New Subjects</p>
                                <p className="text-3xl font-bold text-purple-600">{result.created_subjects}</p>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body text-center">
                                <p className="text-gray-600 text-sm">New Classes</p>
                                <p className="text-3xl font-bold text-indigo-600">{result.created_classes}</p>
                            </div>
                        </div>

                        <div className="card">
                            <div className="card-body text-center">
                                <p className="text-gray-600 text-sm">Failed</p>
                                <p className="text-3xl font-bold text-red-600">{result.failed}</p>
                            </div>
                        </div>
                    </div>

                    {/* Details Table */}
                    <div className="card">
                        <div className="card-body">
                            <h3 className="card-title mb-4">
                                <CheckCircleIcon className="w-5 h-5 inline mr-2 text-green-600" />
                                Successfully Imported Teachers
                            </h3>
                        </div>

                        <div className="table-wrapper">
                            <table className="table text-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {result.details.map((teacher, idx) => (
                                        <tr key={idx}>
                                            <td className="font-medium">{teacher.name}</td>
                                            <td className="text-xs">{teacher.email}</td>
                                            <td>{teacher.subject}</td>
                                            <td>{teacher.class}</td>
                                            <td>
                                                <span className={`badge ${teacher.role === 'Class Teacher' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'} text-xs px-2 py-1 rounded`}>
                                                    {teacher.role}
                                                </span>
                                            </td>
                                            <td>
                                                <span className={`badge ${teacher.status === 'Created' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'} text-xs px-2 py-1 rounded`}>
                                                    {teacher.status}
                                                </span>
                                            </td>
                                            <td className="font-mono text-xs">{teacher.password}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* Errors */}
                    {result.errors.length > 0 && (
                        <div className="card border border-red-200 bg-red-50">
                            <div className="card-body">
                                <h3 className="card-title text-red-700 mb-4">
                                    <ExclamationTriangleIcon className="w-5 h-5 inline mr-2" />
                                    Failed Imports
                                </h3>
                            </div>

                            <div className="px-4 pb-4">
                                <ul className="space-y-2">
                                    {result.errors.map((err, idx) => (
                                        <li key={idx} className="text-sm text-red-700">
                                            <strong>Row {err.row}:</strong> {err.error}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    )}

                    <button onClick={() => setResult(null)} className="btn-primary">
                        Import Another File
                    </button>
                </div>
            ) : (
                <div className="space-y-6">
                    {/* Template Download */}
                    <div className="card bg-blue-50 border border-blue-200">
                        <div className="card-body">
                            <h3 className="card-title text-blue-900 mb-2">📋 Download Template</h3>
                            <p className="text-sm text-blue-700 mb-4">
                                Download the Excel template to see the required format for importing teachers.
                            </p>
                            <button onClick={downloadTemplate} className="btn-secondary">
                                Download Template
                            </button>
                        </div>
                    </div>

                    {/* File Upload */}
                    <div className="card">
                        <div className="card-body">
                            <h3 className="card-title mb-4">Upload Excel File</h3>

                            <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition"
                                onClick={() => document.getElementById('fileInput')?.click()}>
                                <ArrowUpTrayIcon className="w-12 h-12 mx-auto text-gray-400 mb-2" />
                                <p className="text-lg font-medium text-gray-700">Click to upload or drag and drop</p>
                                <p className="text-sm text-gray-500">Excel files (xlsx, xls)</p>
                                <input
                                    id="fileInput"
                                    type="file"
                                    accept=".xlsx,.xls"
                                    onChange={handleFileUpload}
                                    className="hidden"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Data Preview */}
                    {teachers.length > 0 && (
                        <div className="card">
                            <div className="card-body">
                                <h3 className="card-title mb-4">
                                    Preview ({teachers.length} teachers)
                                </h3>
                            </div>

                            <div className="table-wrapper">
                                <table className="table text-sm">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Teaching Subject</th>
                                            <th>Teaching Class</th>
                                            <th>Class Teacher</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {teachers.slice(0, 10).map((teacher, idx) => (
                                            <tr key={idx}>
                                                <td>{idx + 1}</td>
                                                <td className="font-medium">{teacher.name}</td>
                                                <td>{teacher.teaching_subject}</td>
                                                <td>{teacher.teaching_class}</td>
                                                <td>
                                                    <span className={`badge ${teacher.is_class_teacher ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'} text-xs px-2 py-1 rounded`}>
                                                        {teacher.is_class_teacher ? 'Yes' : 'No'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {teachers.length > 10 && (
                                <div className="px-4 py-2 text-center text-sm text-gray-600">
                                    ... and {teachers.length - 10} more teachers
                                </div>
                            )}

                            <div className="card-footer">
                                <button
                                    onClick={handleImport}
                                    disabled={isLoading}
                                    className="btn-primary w-full"
                                >
                                    {isLoading ? 'Importing...' : `Import ${teachers.length} Teachers`}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* Instructions */}
                    <div className="card bg-gray-50">
                        <div className="card-body">
                            <h3 className="card-title mb-3">📝 Instructions</h3>
                            <ul className="space-y-2 text-sm text-gray-700">
                                <li>✓ Download the template above to see the required format</li>
                                <li>✓ Column 1: <strong>name</strong> - Full name of the teacher</li>
                                <li>✓ Column 2: <strong>teaching-subject</strong> - Subject they teach (e.g., Mathematics, English)</li>
                                <li>✓ Column 3: <strong>teaching-class</strong> - Class they teach (e.g., Class 9A, Class 10B)</li>
                                <li>✓ Column 4: <strong>class teacher</strong> - "yes" if they are the class teacher, "no" otherwise</li>
                                <li>✓ Default password for all new teachers: <strong>teacher123</strong></li>
                                <li>✓ Email is auto-generated from teacher name</li>
                                <li>✓ If a teacher already exists, their profile will be updated</li>
                            </ul>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
