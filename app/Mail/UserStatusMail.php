<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $status;
    public $actionUrl;

    public function __construct($userName, $status, $actionUrl)
    {
        $this->userName = $userName;
        $this->status = $status;
        $this->actionUrl = $actionUrl;
    }

    public function envelope(): Envelope
    {
        $subject = 'Account Status Update';
        if ($this->status === 'suspended') {
            $subject = 'Your Account Has Been Suspended';
        } elseif ($this->status === 'banned') {
            $subject = 'Your Account Has Been Banned';
        } elseif ($this->status === 'active') {
            $subject = 'Your Account Has Been Activated';
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-status',
        );
    }
}
