<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Course Review</title>
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
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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
        .review-card {
            background-color: #fffbeb;
            border-radius: 8px;
            padding: 24px;
            border: 1px solid #fde68a;
            margin-bottom: 28px;
        }
        .stars {
            font-size: 24px;
            margin-bottom: 12px;
            letter-spacing: 2px;
        }
        .rating-label {
            font-size: 13px;
            color: #92400e;
            font-weight: 600;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .review-comment {
            color: #374151;
            font-size: 15px;
            font-style: italic;
            line-height: 1.7;
            border-left: 3px solid #f59e0b;
            padding-left: 16px;
            margin: 0;
        }
        .review-meta {
            margin-top: 16px;
            font-size: 13px;
            color: #6b7280;
            border-top: 1px solid #fde68a;
            padding-top: 12px;
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
            background-color: #d97706;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(217, 119, 6, 0.25);
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
            <h1>⭐ New Course Review</h1>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $instructorName }},</div>
            <div class="intro">A student has left a new review on your course <strong>{{ $courseTitle }}</strong>.</div>

            <div class="review-card">
                <div class="stars">
                    @for ($i = 1; $i <= 5; $i++)
                        {{ $i <= $rating ? '⭐' : '☆' }}
                    @endfor
                </div>
                <div class="rating-label">{{ $rating }} out of 5 stars</div>
                @if ($comment)
                    <p class="review-comment">"{{ $comment }}"</p>
                @endif
                <div class="review-meta">
                    Reviewed by: <strong>{{ $studentName }}</strong>
                </div>
            </div>

            <div class="details-card">
                <div class="detail-row">
                    <div class="detail-label">Course</div>
                    <div class="detail-value"><strong>{{ $courseTitle }}</strong></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Student</div>
                    <div class="detail-value">{{ $studentName }}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Rating</div>
                    <div class="detail-value">{{ $rating }} / 5</div>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $actionUrl }}" class="button" target="_blank">View Course Reviews</a>
            </div>
        </div>
        <div class="footer">
            <p>This is an automated notification from your E-Learning Platform.</p>
            <p>&copy; {{ date('Y') }} E-Learning. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
