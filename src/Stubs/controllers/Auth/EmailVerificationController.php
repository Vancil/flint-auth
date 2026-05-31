<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\EmailVerification;
use App\Models\User;
use Flint\Auth\Auth;
use Flint\Mail\Mailer;
use Flint\Request;
use Flint\Response;

class EmailVerificationController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Mailer $mailer,
    ) {}

    /** Show the "please verify your email" notice. */
    public function show(Request $request): Response
    {
        return Response::view('auth.verify-email');
    }

    /** Handle the verification link click. */
    public function verify(Request $request, string $token): Response
    {
        $record = EmailVerification::where('token', $token)->firstModel();

        if (!$record) {
            session()->flash('error', 'Invalid or expired verification link.');
            return Response::redirect('/email/verify');
        }

        $user = User::where('email', $record->email)->firstModel();

        if (!$user) {
            session()->flash('error', 'No account found for this verification link.');
            return Response::redirect('/email/verify');
        }

        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        $record->delete();

        session()->flash('status', 'Email verified successfully.');
        return Response::redirect('/dashboard');
    }

    /** Resend the verification email. */
    public function resend(Request $request): Response
    {
        $user = $this->auth->user();

        if (!$user) {
            return Response::redirect('/login');
        }

        if ($user->isVerified()) {
            return Response::redirect('/dashboard');
        }

        // Remove any previous token
        $existing = EmailVerification::where('email', $user->email)->firstModel();
        if ($existing) {
            $existing->delete();
        }

        $token = bin2hex(random_bytes(32));

        EmailVerification::create([
            'email'      => $user->email,
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $url = rtrim(env('APP_URL', 'http://localhost:8000'), '/') . '/email/verify/' . $token;

        $this->mailer
            ->to($user->email, $user->name)
            ->subject('Verify your email address')
            ->view('emails.verify-email', ['user' => $user, 'url' => $url])
            ->send();

        session()->flash('status', 'Verification email resent.');
        return Response::redirect('/email/verify');
    }
}
