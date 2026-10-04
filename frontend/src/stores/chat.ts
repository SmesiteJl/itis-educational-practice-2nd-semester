import { ref } from 'vue'
import { defineStore } from 'pinia'
import type { BotTask, ChatMessage, ServerEvent } from '@/types'

type Status = 'disconnected' | 'connecting' | 'connected'

const NICKNAME_KEY = 'chat.nickname'

function socketUrl(): string {
  const protocol = location.protocol === 'https:' ? 'wss' : 'ws'
  return `${protocol}://${location.host}/ws`
}

export const useChatStore = defineStore('chat', () => {
  const status = ref<Status>('disconnected')
  const nickname = ref<string | null>(null)
  const joining = ref(false)
  const joinError = ref<string | null>(null)
  const lastError = ref<string | null>(null)
  const users = ref<string[]>([])
  const messages = ref<ChatMessage[]>([])
  const botTasks = ref<BotTask[]>([])

  let socket: WebSocket | null = null
  let leftByUser = false
  let reconnectTimer: number | undefined

  function savedNickname(): string {
    return localStorage.getItem(NICKNAME_KEY) ?? ''
  }

  function send(data: object): boolean {
    if (socket && socket.readyState === WebSocket.OPEN) {
      socket.send(JSON.stringify(data))
      return true
    }
    return false
  }

  function connect(onOpen: () => void) {
    status.value = 'connecting'
    socket = new WebSocket(socketUrl())

    socket.onopen = () => {
      status.value = 'connected'
      onOpen()
    }
    socket.onmessage = (event) => {
      handle(JSON.parse(event.data) as ServerEvent)
    }
    socket.onclose = () => {
      status.value = 'disconnected'
      botTasks.value = []
      if (joining.value) {
        joining.value = false
        joinError.value = 'Не получилось подключиться к серверу'
      }
      if (nickname.value !== null && !leftByUser) {
        clearTimeout(reconnectTimer)
        reconnectTimer = setTimeout(reconnect, 2000)
      }
    }
  }

  function reconnect() {
    const current = nickname.value
    if (current === null || leftByUser) {
      return
    }
    connect(() => send({ type: 'join', nickname: current }))
  }

  function join(wanted: string) {
    leftByUser = false
    joinError.value = null
    joining.value = true
    const sendJoin = () => send({ type: 'join', nickname: wanted })

    if (socket && socket.readyState === WebSocket.OPEN) {
      sendJoin()
    } else {
      connect(sendJoin)
    }
  }

  function sendMessage(text: string): boolean {
    const sent = send({ type: 'message', text })
    if (!sent) {
      showError('Нет соединения с сервером, сообщение не отправлено')
    }
    return sent
  }

  function toggleReaction(messageId: number, emoji: string) {
    send({ type: 'reaction', messageId, emoji })
  }

  function leave() {
    leftByUser = true
    clearTimeout(reconnectTimer)
    nickname.value = null
    users.value = []
    messages.value = []
    botTasks.value = []
    socket?.close()
    socket = null
  }

  let errorTimer: number | undefined

  function showError(error: string) {
    lastError.value = error
    clearTimeout(errorTimer)
    errorTimer = setTimeout(() => (lastError.value = null), 5000)
  }

  function handle(event: ServerEvent) {
    switch (event.type) {
      case 'join_ok':
        joining.value = false
        nickname.value = event.nickname
        users.value = event.users
        messages.value = event.history
        localStorage.setItem(NICKNAME_KEY, event.nickname)
        break
      case 'join_error':
        joining.value = false
        joinError.value = event.error
        if (nickname.value !== null) {
          nickname.value = null
          joinError.value = 'Не получилось вернуться в чат: ' + event.error
          socket?.close()
        }
        break
      case 'users':
        users.value = event.users
        break
      case 'message':
        messages.value.push(event.message)
        break
      case 'reactions': {
        const message = messages.value.find((m) => m.id === event.messageId)
        if (message) {
          message.reactions = event.reactions
        }
        break
      }
      case 'bot_thinking':
        botTasks.value.push({ id: event.id, nickname: event.nickname, command: event.command })
        break
      case 'bot_done':
        botTasks.value = botTasks.value.filter((task) => task.id !== event.id)
        break
      case 'bot_private':
        messages.value.push({
          id: -Date.now(),
          type: 'bot',
          nickname: null,
          text: event.text,
          createdAt: new Date().toISOString(),
          command: null,
          replyTo: null,
          reactions: [],
          private: true,
        })
        break
      case 'error':
        showError(event.error)
        break
    }
  }

  return {
    status,
    nickname,
    joining,
    joinError,
    lastError,
    users,
    messages,
    botTasks,
    savedNickname,
    join,
    sendMessage,
    toggleReaction,
    leave,
  }
})
