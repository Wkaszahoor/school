<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admission Card - {{ $card->student->full_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            line-height: 1.4;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            background: white;
            width: 21cm;
            height: 29.7cm;
            margin: 0 auto;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
            page-break-after: always;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            color: rgba(0,0,0,0.08);
            font-weight: bold;
            z-index: 0;
            pointer-events: none;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #1a3a52;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-logo {
            font-weight: bold;
            font-size: 14px;
            color: #1a3a52;
            margin-bottom: 5px;
        }

        .header-title {
            font-size: 24px;
            font-weight: bold;
            color: #1a3a52;
            margin: 10px 0;
        }

        .header-subtitle {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }

        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #1a3a52;
            color: white;
            padding: 10px 15px;
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 12px;
            border-radius: 3px;
        }

        .student-info {
            display: flex;
            gap: 30px;
            margin-bottom: 15px;
        }

        .info-column {
            flex: 1;
        }

        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 12px;
        }

        .info-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }

        .info-value {
            flex: 1;
            color: #333;
            border-bottom: 1px dotted #999;
            padding-bottom: 2px;
        }

        .eligibility-box {
            background-color: {{ $card->attendance_eligible ? '#d4edda' : '#f8d7da' }};
            border: 2px solid {{ $card->attendance_eligible ? '#28a745' : '#dc3545' }};
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 15px 0;
        }

        .eligibility-status {
            font-size: 16px;
            font-weight: bold;
            color: {{ $card->attendance_eligible ? '#28a745' : '#dc3545' }};
            margin-bottom: 8px;
        }

        .eligibility-details {
            font-size: 11px;
            color: #555;
        }

        .datesheet-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .datesheet-table thead {
            background-color: #f0f0f0;
        }

        .datesheet-table th {
            padding: 10px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #ddd;
            background-color: #e8e8e8;
        }

        .datesheet-table td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            font-size: 11px;
        }

        .datesheet-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 11px;
            color: #666;
        }

        .footer-right {
            text-align: center;
        }

        .signature-line {
            border-top: 1px solid #333;
            width: 150px;
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #555;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin: 5px 5px 5px 0;
        }

        .badge-eligible {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .badge-not-eligible {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .badge-draft {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .badge-issued {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .no-datesheets {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            color: #999;
            border: 1px dashed #ddd;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($status === 'draft')
            <div class="watermark">DRAFT</div>
        @endif

        <div class="content">
            <!-- Header -->
            <div class="header">
                <div class="header-logo">KORT SCHOOL MANAGEMENT SYSTEM</div>
                <div class="header-logo" style="font-size: 11px;">https://kort.org.uk</div>
                <div class="header-title">ADMISSION CARD</div>
                <div class="header-subtitle">Academic Year: {{ $card->academic_year }} | Exam Period: {{ $card->exam_period }}</div>
            </div>

            <!-- Student Information -->
            <div class="section">
                <div class="section-title">STUDENT INFORMATION</div>
                <div class="student-info">
                    <div class="info-column">
                        <div class="info-row">
                            <span class="info-label">Name:</span>
                            <span class="info-value">{{ $card->student->full_name }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Admission No:</span>
                            <span class="info-value">{{ $card->student->admission_no }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Date of Birth:</span>
                            <span class="info-value">{{ $card->student->dob ? $card->student->dob->format('d-m-Y') : '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Gender:</span>
                            <span class="info-value">{{ $card->student->gender ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="info-column">
                        <div class="info-row">
                            <span class="info-label">Class:</span>
                            <span class="info-value">{{ $card->class->class }}{{ $card->class->section ? ' (' . $card->class->section . ')' : '' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Father's Name:</span>
                            <span class="info-value">{{ $card->student->father_name ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Guardian:</span>
                            <span class="info-value">{{ $card->student->guardian_name ?? '—' }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Contact:</span>
                            <span class="info-value">{{ $card->student->phone ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance & Eligibility -->
            <div class="section">
                <div class="section-title">ATTENDANCE & ELIGIBILITY STATUS</div>
                <div class="eligibility-box">
                    <div class="eligibility-status">
                        @if($card->attendance_eligible)
                            ✓ ELIGIBLE TO APPEAR
                        @else
                            ✗ NOT ELIGIBLE
                        @endif
                    </div>
                    <div class="eligibility-details">
                        Attendance: <strong>{{ $card->attendance_percent ?? '—' }}%</strong> (Required: ≥ 75%)
                    </div>
                </div>
            </div>

            <!-- Date Sheet -->
            @if($datesheets->count() > 0)
                <div class="section">
                    <div class="section-title">EXAM DATE SHEET</div>
                    <table class="datesheet-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Subject</th>
                                <th style="width: 25%;">Date</th>
                                <th style="width: 20%;">Time</th>
                                <th style="width: 15%;">Marks</th>
                                <th style="width: 10%;">Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($datesheets as $sheet)
                                <tr>
                                    <td>{{ $sheet->subject_name }}</td>
                                    <td>{{ $sheet->exam_date->format('d-m-Y') }}</td>
                                    <td>{{ $sheet->exam_time ?? '—' }}</td>
                                    <td>{{ $sheet->total_marks }}</td>
                                    <td>{{ $sheet->room_no ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="section">
                    <div class="section-title">EXAM DATE SHEET</div>
                    <div class="no-datesheets">
                        No exam schedule configured for this period
                    </div>
                </div>
            @endif

            <!-- Footer -->
            <div class="footer">
                <div class="footer-left">
                    <div>Generated: {{ now()->format('d-m-Y H:i') }}</div>
                    <div style="font-size: 10px; color: #999; margin-top: 5px;">
                        This is a computer-generated document. No signature is required.
                    </div>
                </div>
                <div class="footer-right">
                    <div style="margin-bottom: 5px;">Principal's Signature</div>
                    <div class="signature-line"></div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
