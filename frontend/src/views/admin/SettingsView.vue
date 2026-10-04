<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ApiError, useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
const systemPrompt = ref('')
const error = ref<string | null>(null)
const saved = ref(false)
const saving = ref(false)

onMounted(async () => {
  try {
    const data = await admin.request<{ systemPrompt: string }>('GET', '/api/admin/settings')
    systemPrompt.value = data.systemPrompt
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не удалось загрузить настройки'
  }
})

async function save() {
  error.value = null
  saved.value = false
  saving.value = true
  try {
    await admin.request('PUT', '/api/admin/settings', { systemPrompt: systemPrompt.value })
    saved.value = true
  } catch (e) {
    if (e instanceof ApiError) {
      error.value = e.errors.systemPrompt ?? e.message
    } else {
      error.value = 'Не удалось сохранить'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <h3>Системный промпт</h3>
  <p class="text-muted">
    Отправляется в модель вместе с каждой командой и задаёт общее поведение бота: на каком языке
    отвечать, насколько длинно и так далее. Можно оставить пустым.
  </p>

  <form @submit.prevent="save">
    <textarea
      v-model="systemPrompt"
      class="form-control mb-3"
      rows="8"
      maxlength="4000"
      @input="saved = false"
    ></textarea>

    <div v-if="error" class="alert alert-danger py-2">{{ error }}</div>
    <div v-if="saved" class="alert alert-success py-2">Сохранено</div>

    <button type="submit" class="btn btn-primary" :disabled="saving">Сохранить</button>
  </form>
</template>
