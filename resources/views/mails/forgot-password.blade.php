@extends('.mails.layouts.base')

@section('content')

<body style="font-family: Arial, sans-serif; color:#333;">
<h2>Password Reset Request</h2>

    <p>Dear {{ $user['first_name'] }} {{ $user['last_name'] }},</p>

    <p>We received a request to reset your password for your <strong>AINET</strong> account.</p>

    <p>Use the following one-time password (OTP) to reset your account password:</p>

    <p style="margin: 30px 0; font-size: 28px; letter-spacing: 8px; font-weight: bold; color: #007BC3;">
        {{ $token }}
    </p>

    <p>Enter this 6-digit code along with your new password in the reset form. This code will expire in 60 minutes.</p>

    <p>If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>

    <p>If you have any questions, feel free to contact us at <a href="mailto:theainet@gmail.com">theainet@gmail.com</a>.</p>

    <p>Warm regards, <br> The AINET Team</p>
</body>

@endsection

