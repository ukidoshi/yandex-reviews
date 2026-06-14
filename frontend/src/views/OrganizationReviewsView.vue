<template>
  <div class="min-vh-100 bg-light d-flex flex-column">
    <header class="navbar bg-white border-bottom">
      <div class="container d-flex align-items-center gap-3">
        <router-link to="/" class="btn btn-sm btn-outline-secondary">← Назад</router-link>
        <span class="navbar-brand mb-0 h1">{{ organization?.name ?? 'Отзывы' }}</span>
      </div>
    </header>

    <main class="py-4 flex-grow-1">
      <div class="container">
        <div v-if="organization" class="text-muted mb-3">
          Рейтинг {{ formatRating(organization.average_rating) }} ·
          {{ organization.ratings_count }} оценок ·
          {{ organization.reviews_count }} отзывов
        </div>

        <p v-if="loading" class="text-muted">Загрузка...</p>
        <p v-else-if="error" class="text-danger">{{ error }}</p>
        <p v-else-if="reviews.length === 0" class="text-muted">Отзывов пока нет.</p>

        <div v-else class="vstack gap-3">
          <article v-for="review in reviews" :key="review.id" class="card shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between mb-2">
                <strong>{{ review.author_name ?? 'Без имени' }}</strong>
                <span class="text-muted small">{{ formatDate(review.published_at) }}</span>
              </div>
              <div class="mb-2">{{ '★'.repeat(review.rating ?? 0) }}</div>
              <p class="mb-0">{{ review.text }}</p>
            </div>
          </article>
        </div>

        <nav v-if="lastPage > 1" class="mt-4">
          <ul class="pagination">
            <li class="page-item" :class="{ disabled: page === 1 }">
              <button class="page-link" @click="goToPage(page - 1)">Назад</button>
            </li>
            <li class="page-item disabled">
              <span class="page-link">{{ page }} / {{ lastPage }}</span>
            </li>
            <li class="page-item" :class="{ disabled: page === lastPage }">
              <button class="page-link" @click="goToPage(page + 1)">Вперёд</button>
            </li>
          </ul>
        </nav>
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { fetchOrganizationReviews, fetchOrganizations } from '../api/organizations'

const route = useRoute()

const organization = ref(null)
const reviews = ref([])
const page = ref(1)
const lastPage = ref(1)
const loading = ref(true)
const error = ref('')

onMounted(loadPage)

async function loadPage() {
  loading.value = true
  error.value = ''

  try {
    if (!organization.value) {
      const orgs = await fetchOrganizations()
      organization.value = orgs.organizations.find(
        (item) => item.id === Number(route.params.id),
      )
    }

    const data = await fetchOrganizationReviews(route.params.id, page.value)
    reviews.value = data.data
    lastPage.value = data.last_page
    page.value = data.current_page
  } catch (err) {
    error.value = err.data?.message ?? err.message
  } finally {
    loading.value = false
  }
}

function goToPage(nextPage) {
  if (nextPage < 1 || nextPage > lastPage.value) {
    return
  }

  page.value = nextPage
  loadPage()
}

function formatRating(value) {
  if (value === null || value === undefined) {
    return '—'
  }

  return Number(value).toFixed(1)
}

function formatDate(value) {
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleDateString('ru-RU')
}
</script>
