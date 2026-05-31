<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-sm" style="width: 440px;">
            <div class="card-header"><h5 class="mb-0">Create Account</h5></div>
            <div class="card-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input v-model="form.name" type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input v-model="form.email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input v-model="form.password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm password</label>
                        <input v-model="form.password_confirmation" type="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                        {{ loading ? 'Creating account...' : 'Create Account' }}
                    </button>
                </form>
            </div>
            <div class="card-footer text-center text-muted small">
                Already have an account? <a href="/login">Login</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ name: '', email: '', password: '', password_confirmation: '' })

async function submit() {
    loading.value = true
    error.value   = ''

    const res = await fetch('/api/auth/register', {
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
        const firstError = data.errors ? Object.values(data.errors)[0][0] : data.message
        error.value = firstError ?? 'Registration failed.'
    }
}

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}
</script>
