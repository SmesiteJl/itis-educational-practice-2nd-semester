<script setup lang="ts">
import { ref } from 'vue'
import { useChatStore } from '@/stores/chat'

const chat = useChatStore()
const nickname = ref(chat.savedNickname())

function submit() {
  const value = nickname.value.trim()
  if (value === '') {
    return
  }
  chat.join(value)
}
</script>

<template>
  <div class="container">
    <div class="card join-card">
      <div class="card-body">
        <h4 class="card-title mb-3">Вход в чат</h4>
        <form @submit.prevent="submit">
          <div class="mb-3">
            <label for="nickname" class="form-label">Ваш ник</label>
            <input
              id="nickname"
              v-model="nickname"
              type="text"
              class="form-control"
              :class="{ 'is-invalid': chat.joinError }"
              maxlength="24"
              autocomplete="off"
              autofocus
            />
            <div v-if="chat.joinError" class="invalid-feedback">{{ chat.joinError }}</div>
          </div>
          <button type="submit" class="btn btn-primary w-100" :disabled="chat.joining">
            {{ chat.joining ? 'Подключение...' : 'Войти' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.join-card {
  max-width: 400px;
  margin: 100px auto 0;
}
</style>
