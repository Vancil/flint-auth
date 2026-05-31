@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Verify Your Email</h5>
            </div>
            <div class="card-body">
                <p>
                    Thanks for registering! Before getting started, please verify your email address
                    by clicking the link we just sent you.
                </p>
                <p class="text-muted small">
                    If you didn't receive the email, click below to request another.
                </p>

                <form method="POST" action="/email/verify/resend">
                    @csrf
                    <button type="submit" class="btn btn-primary">Resend Verification Email</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
