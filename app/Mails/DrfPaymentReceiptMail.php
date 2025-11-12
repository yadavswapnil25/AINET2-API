<?php

namespace App\Mails;

use App\Models\Drf;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Carbon;

class DrfPaymentReceiptMail extends MailLayout
{
    protected Drf $drf;

    protected string $invoiceNumber;

    protected float $amount;

    protected Carbon $paidAt;

    protected string $paymentId;

    protected string $orderId;

    protected string $pdfData;

    public function __construct(
        Drf $drf,
        string $invoiceNumber,
        float $amount,
        Carbon $paidAt,
        string $paymentId,
        string $orderId,
        string $pdfData
    ) {
        $this->drf = $drf;
        $this->invoiceNumber = $invoiceNumber;
        $this->amount = $amount;
        $this->paidAt = $paidAt;
        $this->paymentId = $paymentId;
        $this->orderId = $orderId;
        $this->pdfData = $pdfData;

        parent::__construct();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->mailHelper->address(), $this->mailHelper->name()),
            to: $this->drf->email,
            subject: 'AINET 2026 Delegate Registration Payment Receipt'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.drf-payment-receipt',
            with: [
                'drf' => $this->drf,
                'invoiceNumber' => $this->invoiceNumber,
                'amount' => $this->amount,
                'paidAt' => $this->paidAt,
                'paymentId' => $this->paymentId,
                'orderId' => $this->orderId,
            ]
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(
                fn () => $this->pdfData,
                $this->invoiceNumber . '.pdf'
            )->withMime('application/pdf'),
        ];
    }
}

