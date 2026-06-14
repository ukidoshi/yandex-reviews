<template>
  <div v-if="!initialized" class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="text-center">
      <div class="spinner-border text-primary" role="status" />
      <p class="text-muted mt-3 mb-0">Загрузка...</p>
    </div>
  </div>

  <RouterView v-else />
</template>

<script setup>
import { useAuth } from './composables/useAuth'

const { initialized } = useAuth()
</script>
