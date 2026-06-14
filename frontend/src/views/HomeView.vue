<template>
  <div class="min-vh-100 bg-light d-flex flex-column">
    <header class="navbar bg-white border-bottom">
      <div class="container d-flex align-items-center">
        <span class="navbar-brand mb-0 h1">Yndx Review</span>
        <button
          class="btn btn-outline-secondary btn-sm ms-auto"
          :disabled="authLoading"
          @click="handleLogout"
        >
          Выйти
        </button>
      </div>
    </header>

    <main class="py-4 flex-grow-1">
      <div class="container">
        <div class="card shadow-sm mb-4">
          <div class="card-body">
            <h1 class="h4 mb-3">Добавить организацию</h1>
            <form class="row g-2" @submit.prevent="handleAdd">
              <div class="col-md-9">
                <input
                  v-model="url"
                  type="url"
                  class="form-control"
                  placeholder="Ссылка на организацию в Яндекс Картах"
                  required
                >
              </div>
              <div class="col-md-3">
                <button class="btn btn-primary w-100" type="submit" :disabled="adding">
                  <span v-if="adding" class="spinner-border spinner-border-sm me-2" />
                  Добавить
                </button>
              </div>
            </form>
            <p v-if="error" class="text-danger small mb-0 mt-2">{{ error }}</p>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="h5 mb-3">Мои организации</h2>

            <p v-if="loading" class="text-muted mb-0">Загрузка...</p>
            <p v-else-if="organizations.length === 0" class="text-muted mb-0">
              Пока ничего нет. Вставьте ссылку выше.
            </p>

            <div v-else class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th>Название</th>
                    <th>Рейтинг</th>
                    <th>Оценки</th>
                    <th>Отзывы</th>
                    <th class="text-end">Действия</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="org in organizations" :key="org.id">
                    <td>
                      <router-link :to="{ name: 'organization-reviews', params: { id: org.id } }">
                        {{ org.name }}
                      </router-link>
                    </td>
                    <td>{{ formatRating(org.average_rating) }}</td>
                    <td>{{ org.ratings_count }}</td>
                    <td>{{ org.reviews_count }}</td>
                    <td class="text-end">
                      <button
                        class="btn btn-sm btn-outline-primary me-1"
                        :disabled="busyId === org.id"
                        @click="handleRefresh(org.id)"
                      >
                        Обновить
                      </button>
                      <button
                        class="btn btn-sm btn-outline-danger"
                        :disabled="busyId === org.id"
                        @click="handleDelete(org.id)"
                      >
                        Удалить
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import {
  addOrganization,
  deleteOrganization,
  fetchOrganizations,
  refreshOrganization,
} from '../api/organizations'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { loading: authLoading, logout } = useAuth()

const organizations = ref([])
const url = ref('')
const loading = ref(true)
const adding = ref(false)
const busyId = ref(null)
const error = ref('')

onMounted(loadOrganizations)

async function loadOrganizations() {
  loading.value = true
  error.value = ''

  try {
    const data = await fetchOrganizations()
    organizations.value = data.organizations
  } catch (err) {
    error.value = err.data?.message ?? err.message
  } finally {
    loading.value = false
  }
}

async function handleAdd() {
  adding.value = true
  error.value = ''

  try {
    await addOrganization(url.value.trim())
    url.value = ''
    await loadOrganizations()
  } catch (err) {
    error.value = err.data?.errors?.url?.[0] ?? err.data?.message ?? err.message
  } finally {
    adding.value = false
  }
}

async function handleRefresh(id) {
  busyId.value = id
  error.value = ''

  try {
    await refreshOrganization(id)
    await loadOrganizations()
  } catch (err) {
    error.value = err.data?.errors?.url?.[0] ?? err.data?.message ?? err.message
  } finally {
    busyId.value = null
  }
}

async function handleDelete(id) {
  if (!confirm('Удалить организацию и все её отзывы?')) {
    return
  }

  busyId.value = id
  error.value = ''

  try {
    await deleteOrganization(id)
    organizations.value = organizations.value.filter((org) => org.id !== id)
  } catch (err) {
    error.value = err.data?.message ?? err.message
  } finally {
    busyId.value = null
  }
}

async function handleLogout() {
  await logout()
  await router.push({ name: 'login' })
}

function formatRating(value) {
  if (value === null || value === undefined) {
    return '—'
  }

  return Number(value).toFixed(1)
}
</script>
