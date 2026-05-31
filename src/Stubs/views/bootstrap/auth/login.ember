@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Login</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/login">
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

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Remember me</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">Login</button>
                        <a href="/forgot-password" class="text-muted small">Forgot password?</a>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                Don't have an account? <a href="/register">Register</a>
            </div>
        </div>
    </div>
</div>
@endsection
