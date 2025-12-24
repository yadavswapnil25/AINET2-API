@extends('.mails.layouts.base')

@php
    $title = 'AINET 2026 Delegate Registration - Submission Confirmation';
@endphp

@section('content')
<table style="width: 100%; font-family: Arial, sans-serif; color: #374151;">
    <tr>
        <td>
            <h2 style="margin-bottom: 16px; color: #0f172a;">Thank you for your delegate registration!</h2>
            <p style="margin: 0 0 12px;">
                Dear {{ $drf->pre_title }} {{ $drf->name }},
            </p>
            <p style="margin: 0 0 12px;">
                We have successfully received your delegate registration for the
                <strong>9th AINET International Conference</strong> - "Empowering English Language Education in the Digital Era".
            </p>

            @if($drf->payment_status === 'pending')
            <div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0; border-radius: 4px;">
                <p style="margin: 0; color: #1e40af; font-weight: 600;">
                    💳 Payment Pending
                </p>
                <p style="margin: 8px 0 0; color: #1e3a8a;">
                    Your registration is pending payment. Please complete the payment to confirm your participation. You will receive a payment receipt via email once the payment is confirmed.
                </p>
            </div>
            @elseif($drf->payment_status === 'paid')
            <div style="background-color: #d1fae5; border-left: 4px solid #10b981; padding: 16px; margin: 24px 0; border-radius: 4px;">
                <p style="margin: 0; color: #065f46; font-weight: 600;">
                    ✅ Registration Confirmed
                </p>
                <p style="margin: 8px 0 0; color: #047857;">
                    Your registration has been confirmed. A payment receipt has been sent to your email.
                </p>
            </div>
            @endif

            <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
                <tbody>
                    <tr>
                        <td style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600; width: 40%;">
                            Registration ID
                        </td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb;">
                            DRF-{{ str_pad($drf->id, 6, '0', STR_PAD_LEFT) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;">
                            Name
                        </td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb;">
                            {{ $drf->pre_title }} {{ $drf->name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;">
                            Email
                        </td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb;">
                            {{ $drf->email }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;">
                            Delegate Type
                        </td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb;">
                            {{ $drf->you_are_register_as ?? 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; font-weight: 600;">
                            Payment Status
                        </td>
                        <td style="padding: 12px; border: 1px solid #e5e7eb;">
                            <span style="text-transform: uppercase; font-weight: 600; color: {{ $drf->payment_status === 'paid' ? '#10b981' : '#f59e0b' }};">
                                {{ $drf->payment_status }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

            <p style="margin: 24px 0 12px;">
                <strong>Next Steps:</strong>
            </p>
            <ul style="margin: 0 0 24px; padding-left: 24px; color: #4b5563;">
                @if($drf->payment_status === 'pending')
                <li style="margin-bottom: 8px;">Complete the payment to confirm your registration</li>
                <li style="margin-bottom: 8px;">You will receive a payment receipt via email after successful payment</li>
                @endif
                <li style="margin-bottom: 8px;">Keep this email for your records</li>
                <li style="margin-bottom: 8px;">You will receive further updates about the conference via email</li>
            </ul>

            <p style="margin: 24px 0 12px; color: #6b7280; font-size: 14px;">
                Please keep this email for your records. If you have any questions or need assistance, please contact us.
            </p>

            <p style="margin: 12px 0; color: #6b7280; font-size: 14px;">
                For any queries, please contact us at
                <a href="mailto:theainet@gmail.com" style="color: #0f172a; font-weight: 600;">theainet@gmail.com</a>.
            </p>
        </td>
    </tr>
</table>
@endsection

