<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Flint\Mail\Mailer;
use Flint\Request;
use Flint\Response;

class ForgotPasswordController
{
    public function __construct(private readonly Mailer $mailer) {}

    public function showForm(Request $request): Response
    {
        return Response::view('auth.forgot-password');
    }

    public function sendLink(Request $request): Response
    {
        $data = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $data['email'])->firstModel();

        // Always show success to avoid email enumeration
        if (!$user) {
            session()->flash('status', 'If an account exists for that email, a reset link has been sent.');
            return Response::redirect('/forgot-password');
        }

        // Delete any existing tokens for this email
        $existing = PasswordResetToken::where('email', $data['email'])->firstModel();
        if ($existing) {
            $existing->delete();
        }

        $token = bin2hex(random_bytes(32));

        PasswordResetToken::create([
            'email'      => $data['email'],
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $url = rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/reset-password/' . $token;

        $this->mailer
            ->to($user->email, $user->name)
            ->subject('Reset your password')
            ->view('emails.reset-password', ['user' => $user, 'url' => $url])
            ->send();

        session()->flash('status', 'If an account exists for that email, a reset link has been sent.');
        return Response::redirect('/forgot-password');
    }
}
