<template>
    <div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
        <div class="card shadow-sm" style="width: 400px;">
            <div class="card-header"><h5 class="mb-0">Reset Password</h5></div>
            <div class="card-body">
                <div v-if="error" class="alert alert-danger">{{ error }}</div>
                <form @submit.prevent="submit">
                    <div class="mb-3">
                        <label class="form-label">New password</label>
                        <input v-model="form.password" type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm new password</label>
                        <input v-model="form.password_confirmation" type="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                        {{ loading ? 'Resetting...' : 'Reset Password' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route   = useRoute()
const router  = useRouter()
const loading = ref(false)
const error   = ref('')
const form    = ref({ password: '', password_confirmation: '' })

async function submit() {
    loading.value = true
    error.value   = ''

    const res = await fetch('/api/auth/reset-password', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json', 'X-CSRF-Token': await getCsrf() },
        body:        JSON.stringify({ ...form.value, token: route.params.token }),
    })

    loading.value = false

    if (res.ok) {
        router.push('/login')
    } else {
        const data = await res.json()
        error.value = data.message ?? 'Password reset failed.'
    }
}

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}
</script>
