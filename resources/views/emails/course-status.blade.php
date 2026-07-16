<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Status Update</title>
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
            @if($status === 'approved')
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            @elseif($status === 'rejected')
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            @else
                background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            @endif
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
        .message-body {
            font-size: 16px;
            margin-bottom: 28px;
            color: #4b5563;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            font-weight: 700;
            border-radius: 9999px;
            text-transform: uppercase;
            font-size: 14px;
            margin-bottom: 20px;
            @if($status === 'approved')
                background-color: #d1fae5;
                color: #065f46;
            @elseif($status === 'rejected')
                background-color: #fee2e2;
                color: #991b1b;
            @else
                background-color: #fef3c7;
                color: #92400e;
            @endif
        }
        .reason-box {
            background-color: #fcf2f2;
            border-left: 4px solid #ef4444;
            padding: 16px;
            border-radius: 4px;
            margin-top: 16px;
            font-size: 15px;
            color: #7f1d1d;
        }
        .button-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .button {
            display: inline-block;
            @if($status === 'approved')
                background-color: #10b981;
                box-shadow: 0 4px 6px rgba(16, 185, 129, 0.25);
            @elseif($status === 'rejected')
                background-color: #ef4444;
                box-shadow: 0 4px 6px rgba(239, 68, 68, 0.25);
            @else
                background-color: #f59e0b;
                box-shadow: 0 4px 6px rgba(245, 158, 11, 0.25);
            @endif
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
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
            <h1>Course Status Alert</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $instructorName }},</div>
            
            <div class="message-body">
                <p>There has been a status update for your course <strong>{{ $courseTitle }}</strong>:</p>

                @if($status === 'approved')
                    <div class="status-badge">Approved & Published</div>
                    <p>We are excited to let you know that your course has been approved by our moderation team and is now published on the platform! Students can now discover and enroll in your course.</p>
                @elseif($status === 'rejected')
                    <div class="status-badge">Changes Required</div>
                    <p>Thank you for submitting your course. Our review team has checked your submission and determined that some changes are required before it can be published.</p>
                    
                    @if($reason)
                        <div class="reason-box">
                            <strong>Reason for rejection / requested changes:</strong><br>
                            {{ $reason }}
                        </div>
                    @endif
                    <p style="margin-top: 16px;">Please update the course according to the feedback and re-submit it for approval.</p>
                @elseif($status === 'featured')
                    <div class="status-badge">Featured</div>
                    <p>Congratulations! Your course <strong>{{ $courseTitle }}</strong> has been chosen as a Featured Course on our homepage. This will significantly increase its visibility and attract more students.</p>
                @elseif($status === 'unfeatured')
                    <div class="status-badge">Unfeatured</div>
                    <p>Your course is no longer featured on the homepage. However, it remains published and fully accessible to all students.</p>
                @endif
            </div>

            @if($actionUrl)
                <div class="button-container">
                    <a href="{{ $actionUrl }}" class="button" target="_blank">
                        @if($status === 'approved' || $status === 'featured')
                            View Course
                        @else
                            Edit Course
                        @endif
                    </a>
                </div>
            @endif
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
