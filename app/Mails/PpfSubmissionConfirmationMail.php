<?php

namespace App\Mails;

use App\Models\Ppf;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class PpfSubmissionConfirmationMail extends MailLayout
{
    protected Ppf $ppf;

    public function __construct(Ppf $ppf)
    {
        $this->ppf = $ppf;
        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->ppf->main_email,
            subject: 'AINET 2026 Presentation Proposal - Submission Confirmation'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.ppf-submission-confirmation',
            with: [
                'ppf' => $this->ppf,
            ]
        );
    }
}

