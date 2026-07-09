<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssignmentCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $studentName;
    public $courseTitle;
    public $assignmentTitle;
    public $instructions;
    public $dueDate;
    public $totalMarks;
    public $actionUrl;

    public function __construct($studentName, $courseTitle, $assignmentTitle, $instructions, $dueDate, $totalMarks, $actionUrl)
    {
        $this->studentName = $studentName;
        $this->courseTitle = $courseTitle;
        $this->assignmentTitle = $assignmentTitle;
        $this->instructions = $instructions;
        $this->dueDate = $dueDate;
        $this->totalMarks = $totalMarks;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Assignment Added: ' . $this->assignmentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.assignment-created',
        );
    }
}
