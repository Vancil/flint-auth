import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}

export default function LoginPage() {
    const navigate  = useNavigate()
    const [email,    setEmail]    = useState('')
    const [password, setPassword] = useState('')
    const [error,    setError]    = useState('')
    const [loading,  setLoading]  = useState(false)

    async function handleSubmit(e) {
        e.preventDefault()
        setLoading(true)
        setError('')

        const res = await fetch('/api/auth/login', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
            body:        JSON.stringify({ email, password }),
        })

        setLoading(false)

        if (res.ok) {
            navigate('/dashboard')
        } else {
            const data = await res.json()
            setError(data.message ?? 'Login failed.')
        }
    }

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light">
            <div className="card shadow-sm" style={{ width: 400 }}>
                <div className="card-header"><h5 className="mb-0">Login</h5></div>
                <div className="card-body">
                    {error && <div className="alert alert-danger">{error}</div>}
                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label className="form-label">Email</label>
                            <input type="email" className="form-control" value={email} onChange={e => setEmail(e.target.value)} required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Password</label>
                            <input type="password" className="form-control" value={password} onChange={e => setPassword(e.target.value)} required />
                        </div>
                        <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                            {loading ? 'Logging in...' : 'Login'}
                        </button>
                    </form>
                </div>
                <div className="card-footer text-center text-muted small">
                    <a href="/forgot-password">Forgot password?</a> &middot; <a href="/register">Create account</a>
                </div>
            </div>
        </div>
    )
}
