<template>
  <div class="min-vh-100 bg-light d-flex flex-column">
    <header class="navbar bg-white border-bottom">
      <div class="container">
        <span class="navbar-brand mb-0 h1">Yndx Review</span>
      </div>
    </header>

    <main class="py-5 flex-grow-1">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm">
              <div class="card-body p-4">
                <h1 class="h4 mb-4">Вход</h1>

                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <form @submit.prevent="handleSubmit">
                  <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                      id="email"
                      v-model="email"
                      type="email"
                      class="form-control"
                      required
                      autocomplete="username"
                      :disabled="loading"
                    />
                  </div>

                  <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input
                      id="password"
                      v-model="password"
                      type="password"
                      class="form-control"
                      required
                      autocomplete="current-password"
                      :disabled="loading"
                    />
                  </div>

                  <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" />
                    {{ loading ? 'Вход...' : 'Войти' }}
                  </button>
                </form>

                <p class="text-muted small mt-3 mb-0">
                  Демо: demo@example.com / password
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { login, loading, error } = useAuth()

const email = ref('demo@example.com')
const password = ref('password')

async function handleSubmit() {
  const success = await login(email.value, password.value)
  if (success) {
    await router.push({ name: 'home' })
  }
}
</script>
