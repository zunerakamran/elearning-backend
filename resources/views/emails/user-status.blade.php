<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Status Update</title>
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
            @if($status === 'active')
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            @else
                background: linear-gradient(135deg, #374151 0%, #1f2937 100%);
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
            @if($status === 'active')
                background-color: #d1fae5;
                color: #065f46;
            @elseif($status === 'suspended')
                background-color: #fef3c7;
                color: #92400e;
            @else
                background-color: #fee2e2;
                color: #991b1b;
            @endif
        }
        .button-container {
            text-align: center;
            margin-bottom: 16px;
        }
        .button {
            display: inline-block;
            @if($status === 'active')
                background-color: #10b981;
                box-shadow: 0 4px 6px rgba(16, 185, 129, 0.25);
            @else
                background-color: #4b5563;
                box-shadow: 0 4px 6px rgba(75, 85, 99, 0.25);
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
            <h1>Account Moderation Alert</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $userName }},</div>
            
            <div class="message-body">
                @if($status === 'active')
                    <div class="status-badge">Activated</div>
                    <p>We are pleased to inform you that your user account has been reactivated. You now have full access to the platform once again.</p>
                @elseif($status === 'suspended')
                    <div class="status-badge">Suspended</div>
                    <p>We want to inform you that your account has been temporarily suspended by our administration team. During suspension, you will not be able to log in, participate in courses, or access your dashboard. If you believe this is an error, please contact support.</p>
                @elseif($status === 'banned')
                    <div class="status-badge">Banned</div>
                    <p>We regret to inform you that your account has been permanently banned from our platform due to a violation of our terms of service. You will no longer be able to log in or access your courses.</p>
                @endif
            </div>

            @if($status === 'active')
                <div class="button-container">
                    <a href="{{ $actionUrl }}" class="button" target="_blank">Log In to Account</a>
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
