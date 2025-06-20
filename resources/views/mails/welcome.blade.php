@extends('.mails.layouts.base')

@section('content')

<body style="font-family: Arial, sans-serif; color:#333;">
    <h2>Welcome to AINET, {{ $user['first_name'] }}!</h2>

    <p>Dear {{ $user['first_name'] }} {{ $user['last_name'] }},</p>

    <p>Thank you for becoming a member of <strong>AINET</strong>.</p>

    <p>We’re excited to have you with us.</p>
    <p>
        An account has been created for you on
        Use the details <a href="{{ $mailHelper->portalLink('login') }}" target="_blank">Log In</a>
        to your account.
    </p>

    <p>If you have any questions, feel free to contact us at <a href="mailto:theainet@gmail.com">theainet@gmail.com</a>.</p>

    <p>Warm regards, <br> The AINET Team</p>
</body>

@endsection