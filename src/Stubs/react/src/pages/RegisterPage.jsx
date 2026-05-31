import { useState } from 'react'
import { useNavigate } from 'react-router-dom'

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}

export default function RegisterPage() {
    const navigate = useNavigate()
    const [form,    setForm]    = useState({ name: '', email: '', password: '', password_confirmation: '' })
    const [error,   setError]   = useState('')
    const [loading, setLoading] = useState(false)

    function update(field) {
        return e => setForm(f => ({ ...f, [field]: e.target.value }))
    }

    async function handleSubmit(e) {
        e.preventDefault()
        setLoading(true)
        setError('')

        const res = await fetch('/api/auth/register', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
            body:        JSON.stringify(form),
        })

        setLoading(false)

        if (res.ok) {
            navigate('/dashboard')
        } else {
            const data = await res.json()
            const firstError = data.errors ? Object.values(data.errors)[0][0] : data.message
            setError(firstError ?? 'Registration failed.')
        }
    }

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light">
            <div className="card shadow-sm" style={{ width: 440 }}>
                <div className="card-header"><h5 className="mb-0">Create Account</h5></div>
                <div className="card-body">
                    {error && <div className="alert alert-danger">{error}</div>}
                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label className="form-label">Full name</label>
                            <input type="text" className="form-control" value={form.name} onChange={update('name')} required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Email</label>
                            <input type="email" className="form-control" value={form.email} onChange={update('email')} required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Password</label>
                            <input type="password" className="form-control" value={form.password} onChange={update('password')} required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Confirm password</label>
                            <input type="password" className="form-control" value={form.password_confirmation} onChange={update('password_confirmation')} required />
                        </div>
                        <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                            {loading ? 'Creating account...' : 'Create Account'}
                        </button>
                    </form>
                </div>
                <div className="card-footer text-center text-muted small">
                    Already have an account? <a href="/login">Login</a>
                </div>
            </div>
        </div>
    )
}
