import { useState } from 'react'

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}

export default function ForgotPasswordPage() {
    const [email,   setEmail]   = useState('')
    const [status,  setStatus]  = useState('')
    const [loading, setLoading] = useState(false)

    async function handleSubmit(e) {
        e.preventDefault()
        setLoading(true)

        await fetch('/api/auth/forgot-password', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
            body:        JSON.stringify({ email }),
        })

        setLoading(false)
        setStatus('If an account exists for that email, a reset link has been sent.')
    }

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light">
            <div className="card shadow-sm" style={{ width: 400 }}>
                <div className="card-header"><h5 className="mb-0">Forgot Password</h5></div>
                <div className="card-body">
                    {status && <div className="alert alert-success">{status}</div>}
                    <p className="text-muted small">Enter your email to receive a password reset link.</p>
                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label className="form-label">Email</label>
                            <input type="email" className="form-control" value={email} onChange={e => setEmail(e.target.value)} required />
                        </div>
                        <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                            {loading ? 'Sending...' : 'Send Reset Link'}
                        </button>
                    </form>
                </div>
                <div className="card-footer text-center text-muted small">
                    <a href="/login">Back to login</a>
                </div>
            </div>
        </div>
    )
}
