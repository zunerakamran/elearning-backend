<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizAttemptedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $courseTitle;
    public $quizTitle;
    public $studentName;
    public $studentEmail;
    public $score;
    public $passed;
    public $actionUrl;

    public function __construct($instructorName, $courseTitle, $quizTitle, $studentName, $studentEmail, $score, $passed, $actionUrl)
    {
        $this->instructorName = $instructorName;
        $this->courseTitle = $courseTitle;
        $this->quizTitle = $quizTitle;
        $this->studentName = $studentName;
        $this->studentEmail = $studentEmail;
        $this->score = $score;
        $this->passed = $passed;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quiz Completed by Student: ' . $this->studentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quiz-attempted',
        );
    }
}
