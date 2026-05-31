<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify your email address</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h2>Verify your email address</h2>
        <p>Hello {{ $user->name }},</p>
        <p>Thanks for registering! Please verify your email address by clicking the button below.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background: #0d6efd; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px;">
                Verify Email Address
            </a>
        </p>

        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #666;">{{ $url }}</p>

        <p>If you did not create an account, no further action is required.</p>

        <hr style="margin-top: 30px;">
        <p style="color: #999; font-size: 12px;">{{ config('app.name', 'Flint') }}</p>
    </div>
</body>
</html>
