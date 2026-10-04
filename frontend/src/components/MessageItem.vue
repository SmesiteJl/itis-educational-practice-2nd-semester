<script setup lang="ts">
import { computed } from 'vue'
import ReactionBar from '@/components/ReactionBar.vue'
import type { ChatMessage } from '@/types'

const props = defineProps<{
  message: ChatMessage
  myNickname: string | null
}>()

const emit = defineEmits<{
  react: [emoji: string]
}>()

const isMine = computed(() => props.message.nickname === props.myNickname)

const time = computed(() => {
  const date = new Date(props.message.createdAt)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
})
</script>

<template>
  <div v-if="message.type === 'system'" class="system-message">
    {{ message.text }} <span class="time">{{ time }}</span>
  </div>

  <div v-else-if="message.type === 'bot'" class="message-row">
    <div class="message bot-message">
      <div class="message-header">
        🤖 <b>Бот</b>
        <span class="time">{{ time }}</span>
        <span v-if="message.private" class="time">· видно только вам</span>
      </div>
      <div v-if="message.replyTo" class="reply-to">
        {{ message.replyTo.nickname }}: {{ message.replyTo.text }}
      </div>
      <div class="message-text">{{ message.text }}</div>
      <ReactionBar
        v-if="!message.private"
        :reactions="message.reactions"
        :my-nickname="myNickname"
        @react="(emoji) => emit('react', emoji)"
      />
    </div>
  </div>

  <div v-else class="message-row" :class="{ mine: isMine }">
    <div class="message">
      <div class="message-header">
        <b>{{ message.nickname }}</b>
        <span class="time">{{ time }}</span>
      </div>
      <div class="message-text">{{ message.text }}</div>
      <ReactionBar
        :reactions="message.reactions"
        :my-nickname="myNickname"
        @react="(emoji) => emit('react', emoji)"
      />
    </div>
  </div>
</template>

<style scoped>
.system-message {
  text-align: center;
  color: #6c757d;
  font-size: 13px;
  margin: 8px 0;
}

.message-row {
  display: flex;
  margin: 6px 0;
}

.message-row.mine {
  justify-content: flex-end;
}

.message {
  max-width: 70%;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  padding: 6px 10px;
}

.mine .message {
  background: #d7e9ff;
  border-color: #b6d4fe;
}

.bot-message {
  background: #fff8e1;
  border-color: #ffe08a;
}

.reply-to {
  font-size: 13px;
  color: #6c757d;
  border-left: 3px solid #ffc107;
  padding-left: 6px;
  margin: 2px 0 4px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.message-header {
  font-size: 13px;
}

.time {
  color: #6c757d;
  font-size: 12px;
  margin-left: 6px;
}

.message-text {
  white-space: pre-wrap;
  word-break: break-word;
}

@media (max-width: 767px) {
  .message {
    max-width: 88%;
  }
}
</style>
