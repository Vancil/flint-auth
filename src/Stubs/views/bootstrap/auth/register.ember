@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Create Account</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/register">
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control"
                            value="@old('name')"
                            autocomplete="name"
                            required
                        >
                        @error('name')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

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
                            autocomplete="new-password"
                            required
                        >
                        @error('password')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            id="password_confirmation"
                            class="form-control"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Create Account</button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                Already have an account? <a href="/login">Login</a>
            </div>
        </div>
    </div>
</div>
@endsection
