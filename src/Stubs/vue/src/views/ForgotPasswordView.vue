<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-sm" style="width: 400px;">
            <div class="card-header"><h5 class="mb-0">Forgot Password</h5></div>
            <div class="card-body">
                <div v-if="status" class="alert alert-success">{{ status }}</div>
                <p class="text-muted small">Enter your email to receive a password reset link.</p>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input v-model="email" type="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                        {{ loading ? 'Sending...' : 'Send Reset Link' }}
                    </button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                <a href="/login">Back to login</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const loading = ref(false)
const status  = ref('')
const email   = ref('')

async function submit() {
    loading.value = true
    status.value  = ''

    const res = await fetch('/api/auth/forgot-password', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
        body:        JSON.stringify({ email: email.value }),
    })

    loading.value = false
    status.value  = 'If an account exists for that email, a reset link has been sent.'
}

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}
</script>
