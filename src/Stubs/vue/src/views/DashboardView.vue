<template>
    <div class="container mt-4">
        <h1>Dashboard</h1>
        <div v-if="user" class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Welcome, {{ user.name }}!</h5>
                <p class="card-text text-muted">{{ user.email }}</p>
            </div>
        </div>
        <button @click="logout" class="btn btn-outline-secondary mt-3">Logout</button>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const user   = ref(null)

onMounted(async () => {
    const res = await fetch('/api/auth/user', { credentials: 'include' })
    if (res.ok) {
        user.value = await res.json()
    } else {
        router.push('/login')
    }
})

async function logout() {
    await fetch('/api/auth/logout', {
        method:      'POST',
        credentials: 'include',
        headers:     { 'X-CSRF-Token': await getCsrf() },
    })
    router.push('/login')
}

async function getCsrf() {
    const res = await fetch('/api/csrf-token', { credentials: 'include' })
    const data = await res.json()
    return data.token ?? ''
}
</script>
