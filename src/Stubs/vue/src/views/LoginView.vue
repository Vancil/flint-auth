<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-sm" style="width: 400px;">
            <div class="card-header"><h5 class="mb-0">Login</h5></div>
            <div class="card-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input v-model="form.password" type="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                        {{ loading ? 'Logging in...' : 'Login' }}
                    </button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                <a href="/forgot-password">Forgot password?</a> &middot;
                <a href="/register">Create account</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ email: '', password: '' })

async function submit() {
    loading.value = true
    error.value   = ''

    const res = await fetch('/api/auth/login', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
        body:        JSON.stringify(form.value),
    })

    loading.value = false

    if (res.ok) {
        router.push('/dashboard')
    } else {
        const data = await res.json()
        error.value = data.message ?? 'Login failed.'
    }
}

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}
</script>
