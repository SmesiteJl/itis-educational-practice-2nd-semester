<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import JoinForm from '@/components/JoinForm.vue'
import MessageItem from '@/components/MessageItem.vue'
import { useChatStore } from '@/stores/chat'
import type { BotCommand } from '@/types'

const chat = useChatStore()
const text = ref('')
const messagesBox = ref<HTMLElement | null>(null)
const commands = ref<BotCommand[]>([])
const showUsers = ref(false)

onMounted(async () => {
  try {
    const response = await fetch('/api/commands')
    commands.value = await response.json()
  } catch (e) {
    console.error('Не удалось загрузить список команд', e)
  }
})

const suggestions = computed(() => {
  if (!text.value.startsWith('!') || text.value.includes(' ')) {
    return []
  }
  const typed = text.value.slice(1).toLowerCase()
  return commands.value.filter((command) => command.name.startsWith(typed))
})

function chooseCommand(command: BotCommand) {
  text.value = '!' + command.name + ' '
  document.getElementById('message-input')?.focus()
}

function send() {
  const value = text.value.trim()
  if (value === '') {
    return
  }
  if (chat.sendMessage(value)) {
    text.value = ''
  }
}

watch(
  () => chat.messages.length,
  async () => {
    await nextTick()
    if (messagesBox.value) {
      messagesBox.value.scrollTop = messagesBox.value.scrollHeight
    }
  },
)
</script>

<template>
  <JoinForm v-if="!chat.nickname" />

  <div v-else class="chat-page">
    <nav class="navbar navbar-dark bg-primary px-3">
      <span class="navbar-brand mb-0 h1">Чат</span>
      <div class="text-white">
        <button class="btn btn-sm btn-outline-light me-2 d-md-none" @click="showUsers = !showUsers">
          Онлайн: {{ chat.users.length }}
        </button>
        <span class="d-none d-sm-inline"
          >Вы: <b>{{ chat.nickname }}</b></span
        >
        <button class="btn btn-sm btn-light ms-2" @click="chat.leave()">Выйти</button>
      </div>
    </nav>

    <div
      v-if="chat.status !== 'connected'"
      class="alert alert-warning rounded-0 mb-0 py-1 text-center"
    >
      Нет соединения с сервером, переподключаюсь...
    </div>

    <div class="chat-body">
      <div ref="messagesBox" class="messages">
        <p v-if="chat.messages.length === 0" class="text-muted text-center mt-3">
          Сообщений пока нет
        </p>
        <MessageItem
          v-for="message in chat.messages"
          :key="message.id"
          :message="message"
          :my-nickname="chat.nickname"
          @react="(emoji) => chat.toggleReaction(message.id, emoji)"
        />
      </div>

      <div class="users" :class="{ open: showUsers }">
        <h6>Онлайн ({{ chat.users.length }})</h6>
        <ul class="list-group">
          <li v-for="user in chat.users" :key="user" class="list-group-item">
            {{ user }}
            <span v-if="user === chat.nickname" class="text-muted">(вы)</span>
          </li>
        </ul>

        <h6 class="mt-4">Команды бота</h6>
        <ul class="list-unstyled small">
          <li v-for="command in commands" :key="command.name" class="mb-1">
            <code>!{{ command.name }}</code> — {{ command.description }}
          </li>
        </ul>
      </div>
    </div>

    <form class="send-form" @submit.prevent="send">
      <div v-for="task in chat.botTasks" :key="task.id" class="bot-thinking">
        <span class="spinner-grow spinner-grow-sm text-warning"></span>
        Бот думает над !{{ task.command }} от {{ task.nickname }}...
      </div>

      <div v-if="chat.lastError" class="alert alert-warning py-1 px-2 mb-2">
        {{ chat.lastError }}
      </div>

      <div class="position-relative">
        <ul v-if="suggestions.length > 0" class="list-group suggestions">
          <li
            v-for="command in suggestions"
            :key="command.name"
            class="list-group-item list-group-item-action"
            @click="chooseCommand(command)"
          >
            <b>!{{ command.name }}</b> <span class="text-muted">{{ command.description }}</span>
          </li>
        </ul>

        <div class="input-group">
          <input
            id="message-input"
            v-model="text"
            class="form-control"
            placeholder="Сообщение... (команды для бота начинаются с !)"
            maxlength="2000"
            autocomplete="off"
          />
          <button class="btn btn-primary" type="submit">Отправить</button>
        </div>
      </div>
    </form>
  </div>
</template>

<style scoped>
.chat-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
}

.chat-body {
  flex: 1;
  display: flex;
  min-height: 0;
}

.messages {
  flex: 1;
  overflow-y: auto;
  padding: 10px 15px;
}

.users {
  width: 260px;
  border-left: 1px solid #dee2e6;
  background: #fff;
  padding: 10px;
  overflow-y: auto;
}

.send-form {
  padding: 10px 15px;
  border-top: 1px solid #dee2e6;
  background: #fff;
}

.bot-thinking {
  font-size: 13px;
  color: #6c757d;
  margin-bottom: 6px;
}

.suggestions {
  position: absolute;
  bottom: 42px;
  left: 0;
  width: 400px;
  max-width: 100%;
  z-index: 20;
  cursor: pointer;
}

.chat-body {
  position: relative;
}

@media (max-width: 767px) {
  .users {
    display: none;
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    width: 80%;
    max-width: 300px;
    z-index: 30;
    box-shadow: -2px 0 8px rgba(0, 0, 0, 0.15);
  }

  .users.open {
    display: block;
  }

  .messages {
    padding: 8px;
  }

  .send-form {
    padding: 8px;
  }
}
</style>
