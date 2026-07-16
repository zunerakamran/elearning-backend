<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewCourseAwaitingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $courseTitle;
    public $courseDescription;
    public $instructorName;
    public $actionUrl;

    public function __construct($courseTitle, $courseDescription, $instructorName, $actionUrl)
    {
        $this->courseTitle = $courseTitle;
        $this->courseDescription = $courseDescription;
        $this->instructorName = $instructorName;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Course Submitted: Approval Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-course-awaiting',
        );
    }
}
