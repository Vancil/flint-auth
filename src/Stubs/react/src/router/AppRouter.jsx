import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import LoginPage from '../pages/LoginPage'
import RegisterPage from '../pages/RegisterPage'
import ForgotPasswordPage from '../pages/ForgotPasswordPage'
import ResetPasswordPage from '../pages/ResetPasswordPage'
import DashboardPage from '../pages/DashboardPage'

export default function AppRouter() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/login"             element={<LoginPage />} />
                <Route path="/register"          element={<RegisterPage />} />
                <Route path="/forgot-password"   element={<ForgotPasswordPage />} />
                <Route path="/reset-password/:token" element={<ResetPasswordPage />} />
                <Route path="/dashboard"         element={<DashboardPage />} />
                <Route path="/"                  element={<Navigate to="/login" replace />} />
            </Routes>
        </BrowserRouter>
    )
}
