<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Application Status Update</title>
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
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
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
                background-color: #dbeafe;
                color: #1e40af;
            @endif
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
                background-color: #3b82f6;
                box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);
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
            <h1>Instructor Status Update</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $instructorName }},</div>
            
            <div class="message-body">
                @if($status === 'approved')
                    <div class="status-badge">Approved</div>
                    <p>We are thrilled to inform you that your application to become an instructor has been approved! You can now log into your account, access the instructor dashboard, and start creating and publishing your courses.</p>
                @elseif($status === 'rejected')
                    <div class="status-badge">Rejected</div>
                    <p>Thank you for your interest in teaching on our platform. After reviewing your registration request, we regret to inform you that we are unable to approve your instructor application at this time. If you have questions, please reach out to support.</p>
                @elseif($status === 'verified')
                    <div class="status-badge">Verified</div>
                    <p>Congratulations! Your instructor profile has been reviewed and verified by our administration team. A verification badge has been added to your profile and courses, which helps build trust with students.</p>
                @endif
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="button" target="_blank">
                    @if($status === 'approved')
                        Go to Dashboard
                    @elseif($status === 'rejected')
                        Contact Support
                    @else
                        View Profile
                    @endif
                </a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
