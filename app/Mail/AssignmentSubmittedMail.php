<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssignmentSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $instructorName;
    public $courseTitle;
    public $assignmentTitle;
    public $studentName;
    public $studentEmail;
    public $submittedAt;
    public $note;
    public $fileName;
    public $actionUrl;

    public function __construct($instructorName, $courseTitle, $assignmentTitle, $studentName, $studentEmail, $submittedAt, $note, $fileName, $actionUrl)
    {
        $this->instructorName = $instructorName;
        $this->courseTitle = $courseTitle;
        $this->assignmentTitle = $assignmentTitle;
        $this->studentName = $studentName;
        $this->studentEmail = $studentEmail;
        $this->submittedAt = $submittedAt;
        $this->note = $note;
        $this->fileName = $fileName;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Assignment Submitted: ' . $this->studentName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assignment-submitted',
        );
    }
}
