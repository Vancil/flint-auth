import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'

import LoginView from './views/LoginView.vue'
import RegisterView from './views/RegisterView.vue'
import ForgotPasswordView from './views/ForgotPasswordView.vue'
import ResetPasswordView from './views/ResetPasswordView.vue'
import DashboardView from './views/DashboardView.vue'

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/login',           component: LoginView },
        { path: '/register',        component: RegisterView },
        { path: '/forgot-password', component: ForgotPasswordView },
        { path: '/reset-password/:token', component: ResetPasswordView },
        { path: '/dashboard',       component: DashboardView, meta: { requiresAuth: true } },
        { path: '/',                redirect: '/login' },
    ],
})

router.beforeEach(async (to) => {
    if (to.meta.requiresAuth) {
        const res = await fetch('/api/auth/user', { credentials: 'include' })
        if (!res.ok) return '/login'
    }
})

const app = createApp({ template: '<router-view />' })
app.use(router)
app.mount('#app')
