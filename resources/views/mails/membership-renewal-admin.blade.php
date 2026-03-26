@extends('.mails.layouts.base')

@php
    $title = 'Membership Renewal Notification';
    $planLabels = ['Annual' => 'Annual (1 Year)', 'LongTerm' => 'Long Term (3 Years)', 'Overseas' => 'Overseas (1 Year)'];
    $planLabel = $planLabels[$plan] ?? $plan;
@endphp

@section('content')
<table style="width: 100%; font-family: Arial, sans-serif; color: #374151;">
    <tr>
        <td>
            <h2 style="margin-bottom: 16px; color: #0f172a;">Membership Renewal — Payment Received</h2>

            <p style="margin: 0 0 12px;">
                A member has successfully renewed their AINET membership. Details are listed below.
            </p>

            <!-- Member Details -->
            <table style="width: 100%; border-collapse: collapse; margin: 24px 0; border: 1px solid #e2e8f0; overflow: hidden;">
                <thead>
                    <tr>
                        <td colspan="2" style="padding: 12px 16px; background-color: #0f172a; color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.5px;">
                            Member Details
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; width: 40%; font-weight: 600; color: #475569;">Member Name</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a; font-weight: 600;">{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Email</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #0f172a;">
                            <a href="mailto:{{ $user->email }}" style="color: #1d4ed8;">{{ $user->email }}</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Mobile</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a;">{{ $user->mobile ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Membership ID</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #0f172a;">{{ $user->m_id ?? 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Renewal Details -->
            <table style="width: 100%; border-collapse: collapse; margin: 0 0 24px 0; border: 1px solid #e2e8f0; overflow: hidden;">
                <thead>
                    <tr>
                        <td colspan="2" style="padding: 12px 16px; background-color: #1d4ed8; color: #ffffff; font-weight: 700; font-size: 14px; letter-spacing: 0.5px;">
                            Renewal &amp; Payment Details
                        </td>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; width: 40%; font-weight: 600; color: #475569;">Membership Type</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #0f172a;">{{ $type }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Plan</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #0f172a;">{{ $planLabel }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">New Expiry Date</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #16a34a; font-weight: 700;">{{ $expiresAt->format('F j, Y') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Payment Date</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #0f172a;">{{ $paidAt->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Amount Received</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 700; font-size: 16px; color: #16a34a;">₹ {{ number_format($amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #ffffff; font-weight: 600; color: #475569;">Razorpay Payment ID</td>
                        <td style="padding: 10px 16px; background-color: #ffffff; color: #64748b; font-size: 13px;">{{ $paymentId }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 16px; background-color: #f8fafc; font-weight: 600; color: #475569;">Razorpay Order ID</td>
                        <td style="padding: 10px 16px; background-color: #f8fafc; color: #64748b; font-size: 13px;">{{ $orderId }}</td>
                    </tr>
                </tbody>
            </table>

            <p style="margin: 0 0 12px; color: #64748b; font-size: 13px;">
                This is an automated notification from the AINET member portal.
            </p>
        </td>
    </tr>
</table>
@endsection
