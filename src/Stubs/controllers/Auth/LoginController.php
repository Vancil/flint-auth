<?php
declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Models\User;
use Flint\Auth\Auth;
use Flint\Request;
use Flint\Response;

class LoginController
{
    public function __construct(private readonly Auth $auth) {}

    public function showForm(Request $request): Response
    {
        if ($this->auth->check()) {
            return Response::redirect('/dashboard');
        }

        return Response::view('auth.login');
    }

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->firstModel();

        if (!$user || !password_verify($data['password'], $user->password)) {
            return Response::back()
                ->withErrors(['email' => ['These credentials do not match our records.']])
                ->withInput(['email' => $data['email']]);
        }

        $this->auth->login($user, (bool) $request->input('remember'));

        return Response::redirect('/dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout();
        return Response::redirect('/');
    }
}
