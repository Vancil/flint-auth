<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\PasswordResetToken;
use App\Models\User;
use Flint\Auth\Auth;
use Flint\Mail\Mailer;
use Flint\Request;
use Flint\Response;

class AuthApiController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Mailer $mailer,
    ) {}

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->firstModel();

        if (!$user || !password_verify($data['password'], $user->password)) {
            return Response::json(['message' => 'Invalid credentials.'], 401);
        }

        $this->auth->login($user);

        return Response::json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
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
            return Response::json(['errors' => ['password' => ['Passwords do not match.']]], 422);
        }

        if (User::where('email', $data['email'])->firstModel()) {
            return Response::json(['errors' => ['email' => ['Email already registered.']]], 422);
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
        ]);

        $this->auth->login($user);

        return Response::json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::json(['message' => 'Logged out.']);
    }

    public function user(Request $request): Response
    {
        $user = $this->auth->user();

        if (!$user) {
            return Response::json(['message' => 'Unauthenticated.'], 401);
        }

        return Response::json([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ]);
    }

    public function forgotPassword(Request $request): Response
    {
        $data = $request->validate(['email' => 'required|email']);

        $user = User::where('email', $data['email'])->firstModel();

        if ($user) {
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
        }

        // Always return success to prevent email enumeration
        return Response::json(['message' => 'If an account exists for that email, a reset link has been sent.']);
    }

    public function resetPassword(Request $request): Response
    {
        $data = $request->validate([
            'token'                 => 'required',
            'password'              => 'required|min:8',
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            return Response::json(['errors' => ['password' => ['Passwords do not match.']]], 422);
        }

        $record = PasswordResetToken::where('token', $data['token'])->firstModel();

        if (!$record || (time() - strtotime($record->created_at)) > 3600) {
            return Response::json(['message' => 'This reset link is invalid or has expired.'], 422);
        }

        $user = User::where('email', $record->email)->firstModel();

        if (!$user) {
            return Response::json(['message' => 'No account found.'], 404);
        }

        $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->save();

        $record->delete();

        return Response::json(['message' => 'Password reset successfully.']);
    }
}
