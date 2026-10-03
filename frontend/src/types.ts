export type MessageType = 'user' | 'system' | 'bot'

export interface Reaction {
  emoji: string
  users: string[]
}

export interface ChatMessage {
  id: number
  type: MessageType
  nickname: string | null
  text: string
  createdAt: string
  command: string | null
  replyTo: { id: number; nickname: string | null; text: string } | null
  reactions: Reaction[]
  private?: boolean
}

export interface BotCommand {
  name: string
  description: string
}

export interface BotTask {
  id: number
  nickname: string
  command: string
}

export type ServerEvent =
  | { type: 'join_ok'; nickname: string; users: string[]; history: ChatMessage[] }
  | { type: 'join_error'; error: string }
  | { type: 'users'; users: string[] }
  | { type: 'message'; message: ChatMessage }
  | { type: 'reactions'; messageId: number; reactions: Reaction[] }
  | ({ type: 'bot_thinking' } & BotTask)
  | { type: 'bot_done'; id: number }
  | { type: 'bot_private'; text: string }
  | { type: 'error'; error: string }

export interface AdminCommand {
  id: number
  name: string
  description: string
  prompt: string
  enabled: boolean
}
