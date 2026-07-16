<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewInstructorAwaitingApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $instructorEmail;
    public $actionUrl;

    public function __construct($instructorName, $instructorEmail, $actionUrl)
    {
        $this->instructorName = $instructorName;
        $this->instructorEmail = $instructorEmail;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Instructor Registration: Approval Required',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-instructor-awaiting',
        );
    }
}
