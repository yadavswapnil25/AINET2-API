@extends('.mails.layouts.base')

@php
    $title = 'AINET 2026 Delegate Registration - Payment Confirmation';
@endphp

@section('content')
<table style="width: 100%; font-family: Arial, sans-serif; color: #374151;">
    <tr>
        <td>
            <h2 style="margin-bottom: 16px; color: #0f172a;">Thank you for completing your registration!</h2>
            <p style="margin: 0 0 12px;">
                Dear {{ $drf->pre_title }} {{ $drf->name }},
            </p>
            <p style="margin: 0 0 12px;">
                We have received your delegate registration payment for the
                <strong>9th AINET International Conference</strong>. Please find the summary below and the official tax
                invoice attached for your records.
            </p>

            <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
                <tbody>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; width: 45%; font-weight: 600;">Invoice Number</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $invoiceNumber }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Payment Date</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">{{ $paidAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; font-weight: 600;">Registration Type</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $drf->you_are_register_as }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Amount Paid</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">₹ {{ number_format($amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; font-weight: 600;">Razorpay Payment ID</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $paymentId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Razorpay Order ID</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">{{ $orderId }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin: 0 0 12px;">
                Please keep this email and the attached invoice for your records. We look forward to your active participation at the conference.
            </p>

            <p style="margin: 0 0 12px;">
                If you have any questions, feel free to reach out to us at
                <a href="mailto:theainet@gmail.com" style="color: #0f172a; font-weight: 600;">theainet@gmail.com</a>.
            </p>

            <p style="margin-top: 24px;">
                Warm regards,<br>
                <strong>AINET Conference Team</strong>
            </p>
        </td>
    </tr>
</table>
@endsection

