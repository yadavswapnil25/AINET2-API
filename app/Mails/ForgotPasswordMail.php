<?php

namespace App\Mails;

use App\Models\User;
use App\Mails\MailLayout;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ForgotPasswordMail extends MailLayout
{

    protected User $user;
    protected string $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;

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
            subject: "Password Reset Request - AINET"
        );
    }

    /**
     * @return Content
     * @throws \Exception
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.forgot-password',
            markdown: 'mails.forgot-password',
            with: [
                'user' => [
                    'name' => $this->user->name,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'email' => $this->user->email,
                ],
                'token' => $this->token,
            ]
        );
    }
}

