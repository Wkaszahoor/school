<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $student->full_name }} - Student Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            color: #1a3a52;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .section h2 {
            background-color: #f5f5f5;
            padding: 10px;
            margin: 0 0 15px 0;
            font-size: 14px;
            border-left: 4px solid #1a3a52;
        }
        .section-content {
            margin-left: 10px;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 12px;
        }
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .two-column {
            display: flex;
            gap: 40px;
        }
        .two-column > div {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 11px;
        }
        table th {
            background-color: #1a3a52;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
        }
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('school.name', 'KORT School') }}</h1>
        <p>Student Profile Report</p>
        <p>Generated on {{ now()->format('d M Y') }}</p>
    </div>

    <!-- Student Information -->
    <div class="section">
        <h2>Student Information</h2>
        <div class="section-content">
            <div class="two-column">
                <div>
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value">{{ $student->full_name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Admission No:</span>
                        <span class="info-value">{{ $student->admission_no }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender:</span>
                        <span class="info-value">{{ ucfirst($student->gender) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth:</span>
                        <span class="info-value">{{ $student->dob }}</span>
                    </div>
                </div>
                <div>
                    <div class="info-row">
                        <span class="info-label">Class:</span>
                        <span class="info-value">{{ $student->class?->class }}{{ $student->class?->section ? ' ' . $student->class->section : '' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Blood Group:</span>
                        <span class="info-value">{{ $student->blood_group ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value">
                            <span class="badge {{ $student->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $student->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guardian Information -->
    <div class="section">
        <h2>Guardian Information</h2>
        <div class="section-content">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $student->guardian_name ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value">{{ $student->guardian_phone ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value">{{ $student->guardian_email ?? '—' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Address:</span>
                <span class="info-value">{{ $student->guardian_address ?? '—' }}</span>
            </div>
        </div>
    </div>

    @if($attendanceSummary)
    <!-- Attendance Summary -->
    <div class="section">
        <h2>Attendance Summary</h2>
        <div class="section-content">
            <table>
                <tr>
                    <th>Total Days</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Attendance Rate</th>
                </tr>
                <tr>
                    <td>{{ $attendanceSummary['total'] }}</td>
                    <td>{{ $attendanceSummary['present'] }}</td>
                    <td>{{ $attendanceSummary['absent'] }}</td>
                    <td>{{ $attendanceSummary['leave'] }}</td>
                    <td><strong>{{ $attendanceSummary['rate'] }}%</strong></td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    @if($student->results && count($student->results) > 0)
    <!-- Academic Results -->
    <div class="section">
        <h2>Academic Results</h2>
        <div class="section-content">
            <table>
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam Type</th>
                        <th>Marks</th>
                        <th>%</th>
                        <th>Grade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($student->results as $result)
                    <tr>
                        <td>{{ $result->subject?->subject_name ?? 'N/A' }}</td>
                        <td>{{ $result->exam_type }}</td>
                        <td>{{ $result->obtained_marks }}/{{ $result->total_marks }}</td>
                        <td>{{ $result->percentage }}%</td>
                        <td><strong>{{ $result->grade }}</strong></td>
                        <td>
                            <span class="badge {{ $result->approval_status === 'approved' ? 'badge-success' : ($result->approval_status === 'rejected' ? 'badge-danger' : 'badge-warning') }}">
                                {{ ucfirst($result->approval_status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This is an automatically generated report. Please verify all details before official use.</p>
        <p>For inquiries, contact the school administration.</p>
    </div>
</body>
</html>
