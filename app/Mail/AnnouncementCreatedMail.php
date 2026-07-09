<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $courseTitle;
    public $announcementTitle;
    public $announcementBody;
    public $instructorName;
    public $actionUrl;

    public function __construct($studentName, $courseTitle, $announcementTitle, $announcementBody, $instructorName, $actionUrl)
    {
        $this->studentName = $studentName;
        $this->courseTitle = $courseTitle;
        $this->announcementTitle = $announcementTitle;
        $this->announcementBody = $announcementBody;
        $this->instructorName = $instructorName;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Course Announcement: ' . $this->announcementTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement-created',
        );
    }
}
