<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Course Announcement</title>
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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
        .announcement-card {
            background-color: #f0fdf4;
            border-radius: 8px;
            padding: 24px;
            border: 1px solid #d1fae5;
            margin-bottom: 28px;
        }
        .announcement-title {
            font-size: 18px;
            font-weight: 700;
            color: #065f46;
            margin-top: 0;
            margin-bottom: 12px;
        }
        .announcement-body {
            color: #374151;
            font-size: 15px;
            white-space: pre-wrap;
        }
        .announcement-meta {
            margin-top: 16px;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #d1fae5;
            padding-top: 12px;
        }
        .button-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .button {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.25);
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
            <h1>Course Announcement</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $studentName }},</div>
            <div class="intro">Your instructor has posted a new announcement in <strong>{{ $courseTitle }}</strong>:</div>
            
            <div class="announcement-card">
                <h2 class="announcement-title">{{ $announcementTitle }}</h2>
                <div class="announcement-body">{{ $announcementBody }}</div>
                <div class="announcement-meta">
                    Posted by: <strong>{{ $instructorName }}</strong>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="button" target="_blank">Go to Course Announcements</a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
