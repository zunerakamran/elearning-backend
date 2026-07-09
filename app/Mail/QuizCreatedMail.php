<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuizCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $courseTitle;
    public $quizTitle;
    public $passingScore;
    public $actionUrl;

    public function __construct($studentName, $courseTitle, $quizTitle, $passingScore, $actionUrl)
    {
        $this->studentName = $studentName;
        $this->courseTitle = $courseTitle;
        $this->quizTitle = $quizTitle;
        $this->passingScore = $passingScore;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Quiz Added: ' . $this->quizTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.quiz-created',
        );
    }
}
