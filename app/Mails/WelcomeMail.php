<?php

namespace App\Mails;

use App\Models\User;
use App\Mails\MailLayout;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class WelcomeMail extends MailLayout
{

    protected User $user;

    public function __construct($user)
    {
        $this->user = $user;

        parent::__construct();
    }
    /**
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->user->email,
            subject: "Welcome to AINET"
        );
    }

    /**
     * @return Content
     * @throws \Exception
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.welcome',
            markdown: 'mails.welcome',
            with: [
                'user' => [
                    'name' => $this->user->name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email,
                ]
            ]
        );
    }
}
