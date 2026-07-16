<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $courseTitle;
    public $status;
    public $reason;
    public $actionUrl;

    public function __construct($instructorName, $courseTitle, $status, $reason = null, $actionUrl = null)
    {
        $this->instructorName = $instructorName;
        $this->courseTitle = $courseTitle;
        $this->status = $status;
        $this->reason = $reason;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        $subject = 'Course Moderation Update: ' . $this->courseTitle;
        if ($this->status === 'approved') {
            $subject = 'Congratulations! Your Course Has Been Approved';
        } elseif ($this->status === 'rejected') {
            $subject = 'Your Course Request Update';
        } elseif ($this->status === 'featured') {
            $subject = 'Congratulations! Your Course Has Been Featured';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-status',
        );
    }
}
