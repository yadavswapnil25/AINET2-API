@extends('.mails.layouts.base')

@php
    $title = 'AINET Membership Renewed Successfully';
    $planLabels = ['Annual' => 'Annual (1 Year)', 'LongTerm' => 'Long Term (3 Years)', 'Overseas' => 'Overseas (1 Year)'];
    $planLabel = $planLabels[$plan] ?? $plan;
@endphp

@section('content')
<table style="width: 100%; font-family: Arial, sans-serif; color: #374151;">
    <tr>
        <td>
            <h2 style="margin-bottom: 16px; color: #0f172a;">Your AINET Membership Has Been Renewed!</h2>

            <p style="margin: 0 0 12px;">
                Dear {{ $user->name }},
            </p>

            <p style="margin: 0 0 12px;">
                We are delighted to confirm that your <strong>AINET membership</strong> has been successfully renewed.
                Your membership is now active and valid until <strong>{{ $expiresAt->format('F j, Y') }}</strong>.
            </p>

            <!-- Payment Details Table -->
            <table style="width: 100%; border-collapse: collapse; margin: 24px 0; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr>
                        <td colspan="2" style="padding: 12px 16px; background-color: #1d4ed8; color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.5px;">
                            Renewal Confirmation
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; width: 45%; font-weight: 600; color: #475569;">Membership ID</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a;">{{ $user->m_id ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Membership Type</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #0f172a;">{{ $type }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Plan</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a;">{{ $planLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Valid Until</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #16a34a; font-weight: 700;">{{ $expiresAt->format('F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Payment Date</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a;">{{ $paidAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Amount Paid</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 700; font-size: 16px; color: #1d4ed8;">₹ {{ number_format($amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Payment ID</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #64748b; font-size: 13px;">{{ $paymentId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Order ID</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #64748b; font-size: 13px;">{{ $orderId }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin: 0 0 12px;">
                As a valued AINET member, you now have access to all the benefits of your membership including
                scholarships, travel grants, conference discounts, and more.
            </p>

            <p style="margin: 0 0 12px;">
                You can log in to your member portal at
                <a href="https://theainet.net/profile" style="color: #1d4ed8; font-weight: 600;">theainet.net/profile</a>
                to view your updated membership details.
            </p>

            <p style="margin: 0 0 12px;">
                If you have any questions, feel free to contact us at
                <a href="mailto:theainet@gmail.com" style="color: #1d4ed8; font-weight: 600;">theainet@gmail.com</a>.
            </p>

            <p style="margin-top: 24px;">
                Warm regards,<br>
                <strong>The AINET Team</strong>
            </p>
        </td>
    </tr>
</table>
@endsection
