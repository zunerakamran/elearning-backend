<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
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
        .otp-container {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
            border: 2px dashed #e5e7eb;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 8px;
            color: #4f46e5;
            margin: 0;
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
            <h1>Your Verification Code</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $name }},</div>
            <div class="intro">Use the following 6-digit code to verify your email address. This code will expire in 10 minutes.</div>
            
            <div class="otp-container">
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <div class="intro" style="font-size: 14px; margin-top: 24px;">
                If you did not request this code, you can safely ignore this email.
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
