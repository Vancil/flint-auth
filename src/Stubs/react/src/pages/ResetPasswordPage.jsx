import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}

export default function ResetPasswordPage() {
    const navigate  = useNavigate()
    const { token } = useParams()
    const [form,    setForm]    = useState({ password: '', password_confirmation: '' })
    const [error,   setError]   = useState('')
    const [loading, setLoading] = useState(false)

    function update(field) {
        return e => setForm(f => ({ ...f, [field]: e.target.value }))
    }

    async function handleSubmit(e) {
        e.preventDefault()
        setLoading(true)
        setError('')

        const res = await fetch('/api/auth/reset-password', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
            body:        JSON.stringify({ ...form, token }),
        })

        setLoading(false)

        if (res.ok) {
            navigate('/login')
        } else {
            const data = await res.json()
            setError(data.message ?? 'Password reset failed.')
        }
    }

    return (
        <div className="min-vh-100 d-flex align-items-center justify-content-center bg-light">
            <div className="card shadow-sm" style={{ width: 400 }}>
                <div className="card-header"><h5 className="mb-0">Reset Password</h5></div>
                <div className="card-body">
                    {error && <div className="alert alert-danger">{error}</div>}
                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label className="form-label">New password</label>
                            <input type="password" className="form-control" value={form.password} onChange={update('password')} required />
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Confirm new password</label>
                            <input type="password" className="form-control" value={form.password_confirmation} onChange={update('password_confirmation')} required />
                        </div>
                        <button type="submit" className="btn btn-primary w-100" disabled={loading}>
                            {loading ? 'Resetting...' : 'Reset Password'}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    )
}
