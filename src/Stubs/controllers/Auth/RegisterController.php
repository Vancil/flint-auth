<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\User;
use Flint\Auth\Auth;
use Flint\Mail\Mailer;
use Flint\Request;
use Flint\Response;

class RegisterController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Mailer $mailer,
    ) {}

    public function showForm(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view('auth.register');
    }

    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email',
            'password'              => 'required|min:8',
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            return Response::back()
                ->withErrors(['password' => ['Passwords do not match.']])
                ->withInput(['name' => $data['name'], 'email' => $data['email']]);
        }

        if (User::where('email', $data['email'])->firstModel()) {
            return Response::back()
                ->withErrors(['email' => ['An account with this email already exists.']])
                ->withInput(['name' => $data['name'], 'email' => $data['email']]);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        // Send email verification
        $this->sendVerificationEmail($user);

        $this->auth->login($user);

        return Response::redirect('/dashboard');
    }

    private function sendVerificationEmail(User $user): void
    {
        $token = bin2hex(random_bytes(32));

        \App\Models\EmailVerification::create([
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
    }
}
