<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $courseTitle;
    public $studentName;
    public $rating;
    public $comment;
    public $actionUrl;

    public function __construct($instructorName, $courseTitle, $studentName, $rating, $comment, $actionUrl)
    {
        $this->instructorName = $instructorName;
        $this->courseTitle = $courseTitle;
        $this->studentName = $studentName;
        $this->rating = $rating;
        $this->comment = $comment;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Review on Your Course: ' . $this->courseTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.review-created',
        );
    }
}
