<?php

namespace App\Mails;

use App\Models\Drf;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DrfSubmissionConfirmationMail extends MailLayout
{
    protected Drf $drf;

    public function __construct(Drf $drf)
    {
        $this->drf = $drf;
        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->drf->email,
            subject: 'AINET 2026 Delegate Registration - Submission Confirmation'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.drf-submission-confirmation',
            with: [
                'drf' => $this->drf,
            ]
        );
    }
}

