<template>
  <div class="min-vh-100 bg-light d-flex flex-column">
    <header class="navbar bg-white border-bottom">
      <div class="container d-flex align-items-center">
        <span class="navbar-brand mb-0 h1">Yndx Review</span>

        <button
          class="btn btn-outline-secondary btn-sm ms-auto"
          :disabled="loading"
          @click="handleLogout"
        >
          <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" />
          Выйти
        </button>
      </div>
    </header>

    <main class="py-5 flex-grow-1">
      <div class="container">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h4 mb-3">Главная</h1>
            <p class="text-muted mb-0">
              Вы вошли как <strong>{{ user?.name }}</strong> ({{ user?.email }}).
            </p>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { user, loading, logout } = useAuth()

async function handleLogout() {
  await logout()
  await router.push({ name: 'login' })
}
</script>
