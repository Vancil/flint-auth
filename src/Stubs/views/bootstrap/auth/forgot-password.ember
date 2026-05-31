@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Forgot Password</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Enter your email address and we'll send you a link to reset your password.
                </p>

                <form method="POST" action="/forgot-password">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input
                            type="email"
                            name="email"
                            id="email"
                            class="form-control"
                            value="@old('email')"
                            autocomplete="email"
                            required
                        >
                        @error('email')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                <a href="/login">Back to login</a>
            </div>
        </div>
    </div>
</div>
@endsection
