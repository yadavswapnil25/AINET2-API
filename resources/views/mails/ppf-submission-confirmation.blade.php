@extends('.mails.layouts.base')

@php
    $title = 'AINET 2026 Presentation Proposal - Submission Confirmation';
@endphp

@section('content')
<table style="width: 100%; font-family: Arial, sans-serif; color: #374151;">
    <tr>
        <td>
            <h2 style="margin-bottom: 16px; color: #0f172a;">Thank you for submitting your presentation proposal!</h2>
            <p style="margin: 0 0 12px;">
                Dear {{ $ppf->main_title }} {{ $ppf->main_name }},
            </p>
            <p style="margin: 0 0 12px;">
                We have successfully received your presentation proposal for the
                <strong>9th AINET International Conference</strong> - "Empowering English Language Education in the Digital Era".
            </p>

            <div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 16px; margin: 24px 0; border-radius: 4px;">
                <p style="margin: 0; color: #92400e; font-weight: 600;">
                    📋 Proposal Under Review
                </p>
                <p style="margin: 8px 0 0; color: #78350f;">
                    Your proposal will be reviewed by our committee. You will receive an email notification regarding the decision by <strong>15th December 2025</strong>.
                </p>
            </div>

            <table style="width: 100%; border-collapse: collapse; margin: 24px 0;">
                <tbody>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; width: 45%; font-weight: 600;">Submission Date</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $ppf->created_at->timezone(config('app.timezone'))->format('F j, Y g:i A') }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Proposal ID</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">PPF-{{ $ppf->id }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; font-weight: 600;">Presentation Title</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $ppf->pr_title }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Conference Sub-theme</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">{{ $ppf->sub_theme }}@if($ppf->sub_theme_other) ({{ $ppf->sub_theme_other }})@endif</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; font-weight: 600;">Presentation Type</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $ppf->pr_nature }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #ffffff; font-weight: 600;">Main Presenter</td>
                        <td style="padding: 8px 12px; background-color: #ffffff;">{{ $ppf->main_title }} {{ $ppf->main_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; background-color: #f8fafc; font-weight: 600;">Email</td>
                        <td style="padding: 8px 12px; background-color: #f8fafc;">{{ $ppf->main_email }}</td>
                    </tr>
                </tbody>
            </table>

            <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px; margin: 24px 0; border-radius: 4px;">
                <p style="margin: 0; color: #1e40af; font-weight: 600;">
                    ⚠️ Important Note
                </p>
                <p style="margin: 8px 0 0; color: #1e3a8a;">
                    After receiving the acceptance of your proposal, you and all your co-presenters must register for the conference by completing the <strong>Delegate Registration Form</strong> by <strong>25 December 2025</strong>.
                </p>
            </div>

            <p style="margin: 24px 0 12px;">
                Please keep this email for your records. We will notify you of the review decision via email.
            </p>

            <p style="margin: 0 0 12px;">
                If you have any questions about your proposal submission, feel free to reach out to us at
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

