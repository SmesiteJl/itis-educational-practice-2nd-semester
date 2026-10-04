<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ApiError, useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
const router = useRouter()

const login = ref('')
const password = ref('')
const error = ref<string | null>(null)
const loading = ref(false)

async function submit() {
  error.value = null
  loading.value = true
  try {
    await admin.signIn(login.value, password.value)
    router.push('/admin')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Сервер не отвечает'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="container">
    <div class="card login-card">
      <div class="card-body">
        <h4 class="card-title mb-3">Вход в админку</h4>
        <form @submit.prevent="submit">
          <div class="mb-3">
            <label for="login" class="form-label">Логин</label>
            <input id="login" v-model="login" class="form-control" autocomplete="username" />
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Пароль</label>
            <input
              id="password"
              v-model="password"
              type="password"
              class="form-control"
              autocomplete="current-password"
            />
          </div>
          <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
          <button type="submit" class="btn btn-dark w-100" :disabled="loading">Войти</button>
        </form>
        <RouterLink to="/" class="d-block text-center mt-3 small">Вернуться в чат</RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-card {
  max-width: 400px;
  margin: 100px auto 0;
}
</style>
