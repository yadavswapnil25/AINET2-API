<?php

namespace App\Mails;

use App\Models\User;
use App\Mails\MailLayout;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class MembershipRenewalUserMail extends MailLayout
{
    protected User $user;
    protected string $plan;
    protected string $type;
    protected float $amount;
    protected string $paymentId;
    protected string $orderId;
    protected Carbon $paidAt;
    protected int $months;
    protected Carbon $expiresAt;

    public function __construct(
        User $user,
        string $plan,
        string $type,
        float $amount,
        string $paymentId,
        string $orderId,
        Carbon $paidAt,
        int $months,
        Carbon $expiresAt
    ) {
        $this->user      = $user;
        $this->plan      = $plan;
        $this->type      = $type;
        $this->amount    = $amount;
        $this->paymentId = $paymentId;
        $this->orderId   = $orderId;
        $this->paidAt    = $paidAt;
        $this->months    = $months;
        $this->expiresAt = $expiresAt;

        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->user->email,
            subject: 'AINET Membership Renewed Successfully'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.membership-renewal-user',
            with: [
                'user'      => $this->user,
                'plan'      => $this->plan,
                'type'      => $this->type,
                'amount'    => $this->amount,
                'paymentId' => $this->paymentId,
                'orderId'   => $this->orderId,
                'paidAt'    => $this->paidAt,
                'months'    => $this->months,
                'expiresAt' => $this->expiresAt,
            ]
        );
    }
}
