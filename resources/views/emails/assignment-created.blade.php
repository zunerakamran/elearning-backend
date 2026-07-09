<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Assignment Added</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 32px 24px;
            color: #1f2937;
            line-height: 1.6;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #111827;
        }
        .intro {
            font-size: 16px;
            margin-bottom: 24px;
            color: #4b5563;
        }
        .details-card {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            border: 1px solid #f3f4f6;
            margin-bottom: 28px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px dashed #e5e7eb;
            padding-bottom: 12px;
        }
        .detail-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
            width: 130px;
            flex-shrink: 0;
        }
        .detail-value {
            color: #4b5563;
        }
        .button-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.25);
            transition: background-color 0.2s;
        }
        .footer {
            background-color: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            font-size: 14px;
            color: #9ca3af;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Assignment Added</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $studentName }},</div>
            <div class="intro">A new assignment has been published in your course <strong>{{ $courseTitle }}</strong>. Please check the details below and make sure to submit on time.</div>
            
            <div class="details-card">
                <div class="detail-row">
                    <div class="detail-label">Title</div>
                    <div class="detail-value"><strong>{{ $assignmentTitle }}</strong></div>
                </div>
                @if($dueDate)
                <div class="detail-row">
                    <div class="detail-label">Due Date</div>
                    <div class="detail-value">{{ $dueDate }}</div>
                </div>
                @endif
                <div class="detail-row">
                    <div class="detail-label">Total Marks</div>
                    <div class="detail-value">{{ $totalMarks }} marks</div>
                </div>
                @if($instructions)
                <div class="detail-row" style="flex-direction: column; border-bottom: none; padding-bottom: 0;">
                    <div class="detail-label" style="width: 100%; margin-bottom: 6px;">Instructions:</div>
                    <div class="detail-value" style="background: #ffffff; padding: 12px; border-radius: 6px; border: 1px solid #e5e7eb; white-space: pre-wrap;">{{ $instructions }}</div>
                </div>
                @endif
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="button" target="_blank">View Assignment</a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
