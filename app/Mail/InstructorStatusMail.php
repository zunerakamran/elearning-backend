<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstructorStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $status;
    public $actionUrl;

    public function __construct($instructorName, $status, $actionUrl)
    {
        $this->instructorName = $instructorName;
        $this->status = $status;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        $subject = 'Instructor Application Status Update';
        if ($this->status === 'approved') {
            $subject = 'Congratulations! Your Instructor Account is Approved';
        } elseif ($this->status === 'rejected') {
            $subject = 'Update on Your Instructor Application';
        } elseif ($this->status === 'verified') {
            $subject = 'Congratulations! You are now a Verified Instructor';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.instructor-status',
        );
    }
}
