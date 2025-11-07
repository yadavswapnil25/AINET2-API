@extends('.mails.layouts.base')

@section('content')

<body style="font-family: Arial, sans-serif; color:#333;">
    <h2>Password Reset Request</h2>

    <p>Dear {{ $user['first_name'] }} {{ $user['last_name'] }},</p>

    <p>We received a request to reset your password for your <strong>AINET</strong> account.</p>

    <p>Click the button below to reset your password:</p>

    <p style="margin: 30px 0;">
        <a href="{{ $resetUrl }}" 
           target="_blank" 
           style="background-color: #007BC3; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
            Reset Password
        </a>
    </p>

    <p>Or copy and paste this link into your browser:</p>
    <p style="word-break: break-all; color: #007BC3;">{{ $resetUrl }}</p>

    <p><strong>This link will expire in 60 minutes.</strong></p>

    <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>

    <p>If you have any questions, feel free to contact us at <a href="mailto:theainet@gmail.com">theainet@gmail.com</a>.</p>

    <p>Warm regards, <br> The AINET Team</p>
</body>

@endsection

