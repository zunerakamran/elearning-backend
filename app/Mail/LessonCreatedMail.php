<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LessonCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $courseTitle;
    public $moduleTitle;
    public $lessonTitle;
    public $contentType;
    public $actionUrl;

    public function __construct($studentName, $courseTitle, $moduleTitle, $lessonTitle, $contentType, $actionUrl)
    {
        $this->studentName = $studentName;
        $this->courseTitle = $courseTitle;
        $this->moduleTitle = $moduleTitle;
        $this->lessonTitle = $lessonTitle;
        $this->contentType = $contentType;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Lesson Added: ' . $this->lessonTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lesson-created',
        );
    }
}
