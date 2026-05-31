import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}

export default function DashboardPage() {
    const navigate = useNavigate()
    const [user, setUser] = useState(null)

    useEffect(() => {
        fetch('/api/auth/user', { credentials: 'include' })
            .then(res => {
                if (!res.ok) return navigate('/login')
                return res.json()
            })
            .then(data => data && setUser(data))
    }, [])

    async function logout() {
        await fetch('/api/auth/logout', {
            method:      'POST',
            credentials: 'include',
            headers:     { 'X-CSRF-Token': await getCsrf() },
        })
        navigate('/login')
    }

    if (!user) return null

    return (
        <div className="container mt-4">
            <h1>Dashboard</h1>
            <div className="card mt-3">
                <div className="card-body">
                    <h5 className="card-title">Welcome, {user.name}!</h5>
                    <p className="card-text text-muted">{user.email}</p>
                </div>
            </div>
            <button onClick={logout} className="btn btn-outline-secondary mt-3">Logout</button>
        </div>
    )
}
