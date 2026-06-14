import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import OrganizationReviewsView from '../views/OrganizationReviewsView.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true },
    },
    {
      path: '/',
      name: 'home',
      component: HomeView,
      meta: { requiresAuth: true },
    },
    {
      path: '/organizations/:id/reviews',
      name: 'organization-reviews',
      component: OrganizationReviewsView,
      meta: { requiresAuth: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const { initialized, isAuthenticated, fetchUser } = useAuth()

  if (!initialized.value) {
    await fetchUser()
  }

  if (to.meta.requiresAuth && !isAuthenticated.value) {
    return { name: 'login' }
  }

  if (to.meta.guest && isAuthenticated.value) {
    return { name: 'home' }
  }
})

export default router
