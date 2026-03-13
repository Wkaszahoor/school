<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Attendance Report</title>
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
            font-size: 12px;
        }
        .filters {
            background-color: #f5f5f5;
            padding: 15px;
            border-left: 4px solid #1a3a52;
            margin-bottom: 20px;
            font-size: 12px;
        }
        .filters div {
            margin-bottom: 5px;
        }
        .filters strong {
            display: inline-block;
            width: 100px;
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
            padding: 10px;
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
        .status-p {
            background-color: #d4edda;
            color: #155724;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-a {
            background-color: #f8d7da;
            color: #721c24;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status-l {
            background-color: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
        .summary {
            background-color: #f9f9f9;
            padding: 15px;
            border-left: 4px solid #1a3a52;
            margin-top: 20px;
            font-size: 12px;
        }
        .summary-item {
            display: inline-block;
            margin-right: 40px;
        }
        .summary-label {
            color: #666;
            font-size: 10px;
        }
        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a3a52;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('school.name', 'KORT School') }}</h1>
        <p>Attendance Report</p>
        <p>Generated on {{ now()->format('d M Y H:i') }}</p>
    </div>

    <div class="filters">
        <div><strong>Class:</strong> {{ $selectedClass?->class . ($selectedClass?->section ? ' ' . $selectedClass->section : '') ?? 'All Classes' }}</div>
        <div><strong>Subject:</strong> {{ $subject?->subject_name ?? 'All Subjects' }}</div>
        @if($fromDate || $toDate)
        <div><strong>Period:</strong> {{ $fromDate ?? 'Start' }} to {{ $toDate ?? 'End' }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Admission No</th>
                @if($fromDate && $toDate)
                <th>Total Days</th>
                @endif
                <th>Date</th>
                <th>Status</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendance as $record)
            <tr>
                <td>{{ $record->student?->full_name ?? '—' }}</td>
                <td>{{ $record->student?->admission_no ?? '—' }}</td>
                @if($fromDate && $toDate)
                <td>—</td>
                @endif
                <td>{{ $record->attendance_date }}</td>
                <td>
                    <span class="status-{{ strtolower($record->status) }}">{{ $record->status }}</span>
                </td>
                <td>{{ $record->remarks ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; padding: 20px;">No attendance records found</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($attendance && count($attendance) > 0)
    <div class="summary">
        <p style="margin: 0 0 10px 0; font-weight: bold;">Summary</p>
        <div class="summary-item">
            <div class="summary-label">Total Records</div>
            <div class="summary-value">{{ count($attendance) }}</div>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>This is an automatically generated report. Please verify all details before official use.</p>
        <p>For inquiries, contact the school administration.</p>
    </div>
</body>
</html>
