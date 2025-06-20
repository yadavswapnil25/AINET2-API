<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Http\Helpers\MailHelper;

abstract class MailLayout extends Mailable
{
    use Queueable, SerializesModels;

    public MailHelper $mailHelper;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($site = null)
    {
        $this->mailHelper = new MailHelper($site);
    }

    /**
     * @return Envelope
     */
    abstract public function envelope(): Envelope;

    /**
     * @return Content
     */
    abstract public function content(): Content;

}