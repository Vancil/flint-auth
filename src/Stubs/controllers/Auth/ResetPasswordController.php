<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\PasswordResetToken;
use App\Models\User;
use Flint\Request;
use Flint\Response;

class ResetPasswordController
{
    public function showForm(Request $request, string $token): Response
    {
        $record = PasswordResetToken::where('token', $token)->firstModel();

        if (!$record || $this->isExpired($record->created_at)) {
            session()->flash('error', 'This password reset link is invalid or has expired.');
            return Response::redirect('/forgot-password');
        }

        return Response::view('auth.reset-password', ['token' => $token]);
    }

    public function reset(Request $request): Response
    {
        $data = $request->validate([
            'token'                 => 'required',
            'password'              => 'required|min:8',
            'password_confirmation' => 'required',
        ]);

        if ($data['password'] !== $data['password_confirmation']) {
            return Response::back()
                ->withErrors(['password' => ['Passwords do not match.']])
                ->withInput(['token' => $data['token']]);
        }

        $record = PasswordResetToken::where('token', $data['token'])->firstModel();

        if (!$record || $this->isExpired($record->created_at)) {
            session()->flash('error', 'This password reset link is invalid or has expired.');
            return Response::redirect('/forgot-password');
        }

        $user = User::where('email', $record->email)->firstModel();

        if (!$user) {
            session()->flash('error', 'No account found for this reset link.');
            return Response::redirect('/forgot-password');
        }

        $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->save();

        $record->delete();

        session()->flash('status', 'Password reset successfully. Please log in.');
        return Response::redirect('/login');
    }

    private function isExpired(string $createdAt): bool
    {
        return (time() - strtotime($createdAt)) > 3600;
    }
}
