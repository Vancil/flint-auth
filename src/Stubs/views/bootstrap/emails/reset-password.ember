<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset your password</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h2>Reset your password</h2>
        <p>Hello {{ $user->name }},</p>
        <p>We received a request to reset the password for your account. Click the button below to choose a new password.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $url }}" style="background: #0d6efd; color: #fff; padding: 12px 24px; text-decoration: none; border-radius: 4px;">
                Reset Password
            </a>
        </p>

        <p>Or copy and paste this link into your browser:</p>
        <p style="word-break: break-all; color: #666;">{{ $url }}</p>

        <p>This link will expire in 1 hour.</p>
        <p>If you didn't request a password reset, you can safely ignore this email.</p>

        <hr style="margin-top: 30px;">
        <p style="color: #999; font-size: 12px;">{{ config('app.name', 'Flint') }}</p>
    </div>
</body>
</html>
