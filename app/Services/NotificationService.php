<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $link = null
    ): void {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
        ]);
    }

    // Helpers for each notification type
    public static function newMessage(int $receiverId, string $senderName, string $courseTitle, int $conversationId): void
    {
        self::send(
            $receiverId,
            'message',
            'New message',
            "{$senderName} sent you a message about \"{$courseTitle}\"",
            '/chat'
        );
    }

    public static function assignmentGraded(int $studentId, string $assignmentTitle, int $marks, int $total, int $courseId): void
    {
        self::send(
            $studentId,
            'graded',
            'Assignment graded',
            "Your submission for \"{$assignmentTitle}\" received {$marks}/{$total} marks",
            "/courses/{$courseId}?tab=assignments"
        );
    }

    public static function certificateIssued(int $studentId, string $courseTitle, int $courseId): void
    {
        self::send(
            $studentId,
            'certificate',
            'Certificate issued 🎓',
            "You have been awarded a certificate for \"{$courseTitle}\"",
            '/my-certificates'
        );
    }

    public static function newAnnouncement(int $studentId, string $instructorName, string $courseTitle, int $courseId): void
    {
        self::send(
            $studentId,
            'announcement',
            'New announcement',
            "{$instructorName} posted an announcement in \"{$courseTitle}\"",
            "/courses/{$courseId}?tab=announcements"
        );
    }

    public static function enrollmentConfirmed(int $studentId, string $courseTitle, int $courseId): void
    {
        self::send(
            $studentId,
            'enrollment',
            'Enrollment confirmed',
            "You are now enrolled in \"{$courseTitle}\"",
            "/courses/{$courseId}"
        );
    }

    public static function newEnrollment(int $instructorId, string $studentName, string $courseTitle, int $courseId): void
    {
        self::send(
            $instructorId,
            'new_enrollment',
            'New student enrolled',
            "{$studentName} enrolled in \"{$courseTitle}\"",
            "/courses/{$courseId}/students"
        );
    }

    public static function reviewPosted(int $instructorId, string $studentName, string $courseTitle, int $rating, int $courseId): void
    {
        self::send(
            $instructorId,
            'review',
            'New review received',
            "{$studentName} gave {$rating} stars on \"{$courseTitle}\"",
            "/courses/{$courseId}/reviews"
        );
    }

    public static function assignmentSubmitted(int $instructorId, string $studentName, string $assignmentTitle, int $courseId, int $assignmentId): void
    {
        self::send(
            $instructorId,
            'submission',
            'New assignment submission',
            "{$studentName} submitted \"{$assignmentTitle}\"",
            "/courses/{$courseId}/assignments/{$assignmentId}"
        );
    }

    public static function quizCompleted(int $studentId, string $quizTitle, int $score, bool $passed, int $lessonId): void
    {
        self::send(
            $studentId,
            'quiz',
            $passed ? 'Quiz passed!' : 'Quiz completed',
            $passed
                ? "You passed \"{$quizTitle}\" with a score of {$score}%"
                : "You scored {$score}% on \"{$quizTitle}\". Keep trying!",
            "/lessons/{$lessonId}/quiz"
        );
    }

    public static function lessonAdded(int $studentId, string $lessonTitle, string $courseTitle, int $moduleId, int $lessonId): void
    {
        self::send(
            $studentId,
            'lesson',
            'New lesson available',
            "A new lesson \"{$lessonTitle}\" has been added to \"{$courseTitle}\"",
            "/modules/{$moduleId}/lessons/{$lessonId}"
        );
    }

    public static function quizAdded(int $studentId, string $quizTitle, string $courseTitle, int $lessonId): void
    {
        self::send(
            $studentId,
            'quiz',
            'New quiz available',
            "A new quiz \"{$quizTitle}\" has been added to a lesson in \"{$courseTitle}\"",
            "/lessons/{$lessonId}/quiz"
        );
    }

    public static function assignmentAdded(int $studentId, string $assignmentTitle, string $courseTitle, int $courseId, int $assignmentId): void
    {
        self::send(
            $studentId,
            'assignment',
            'New assignment available',
            "A new assignment \"{$assignmentTitle}\" has been added to \"{$courseTitle}\"",
            "/courses/{$courseId}/assignments/{$assignmentId}"
        );
    }

    public static function quizAttempted(int $instructorId, string $studentName, string $quizTitle, int $score, bool $passed, int $courseId): void
    {
        self::send(
            $instructorId,
            'quiz_attempt',
            $passed ? 'Student passed quiz' : 'Student attempted quiz',
            "{$studentName} scored {$score}% on \"{$quizTitle}\"",
            "/courses/{$courseId}/report"
        );
    }

    public static function discussionQuestionAdded(int $instructorId, string $userName, string $courseTitle, string $questionTitle, int $courseId): void
    {
        self::send(
            $instructorId,
            'discussion',
            'New discussion question',
            "{$userName} asked \"{$questionTitle}\" in \"{$courseTitle}\"",
            "/courses/{$courseId}?tab=discussions"
        );
    }

    public static function discussionReplyAdded(int $userId, string $userName, string $questionTitle, int $courseId): void
    {
        self::send(
            $userId,
            'discussion',
            'New reply to question',
            "{$userName} replied to \"{$questionTitle}\"",
            "/courses/{$courseId}?tab=discussions"
        );
    }

    public static function discussionReplyPinned(int $userId, string $questionTitle, int $courseId): void
    {
        self::send(
            $userId,
            'discussion',
            'Answer pinned',
            "The instructor pinned your reply in \"{$questionTitle}\"",
            "/courses/{$courseId}?tab=discussions"
        );
    }

    public static function discussionReplyAccepted(int $userId, string $questionTitle, int $courseId): void
    {
        self::send(
            $userId,
            'discussion',
            'Answer accepted',
            "The instructor marked your reply as the accepted answer in \"{$questionTitle}\"",
            "/courses/{$courseId}?tab=discussions"
        );
    }

    public static function discussionReplyLiked(int $userId, string $likerName, string $questionTitle, int $courseId): void
    {
        self::send(
            $userId,
            'discussion',
            'Someone liked your reply',
            "{$likerName} liked your reply in \"{$questionTitle}\"",
            "/courses/{$courseId}?tab=discussions"
        );
    }
}