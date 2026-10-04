import { ref } from 'vue'
import { defineStore } from 'pinia'
import router from '@/router'

const TOKEN_KEY = 'admin.token'
const LOGIN_KEY = 'admin.login'

export class ApiError extends Error {
  status: number
  errors: Record<string, string>

  constructor(status: number, message: string, errors: Record<string, string> = {}) {
    super(message)
    this.status = status
    this.errors = errors
  }
}

export const useAdminStore = defineStore('admin', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const login = ref<string | null>(localStorage.getItem(LOGIN_KEY))

  function isLoggedIn() {
    return token.value !== null
  }

  function saveSession(newToken: string | null, newLogin: string | null) {
    token.value = newToken
    login.value = newLogin
    if (newToken && newLogin) {
      localStorage.setItem(TOKEN_KEY, newToken)
      localStorage.setItem(LOGIN_KEY, newLogin)
    } else {
      localStorage.removeItem(TOKEN_KEY)
      localStorage.removeItem(LOGIN_KEY)
    }
  }

  async function request<T>(method: string, url: string, body?: unknown): Promise<T> {
    const headers: Record<string, string> = { 'Content-Type': 'application/json' }
    if (token.value) {
      headers['Authorization'] = 'Bearer ' + token.value
    }

    const response = await fetch(url, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    })

    if (response.status === 401 && url !== '/api/admin/login') {
      saveSession(null, null)
      router.push('/admin/login')
      throw new ApiError(401, 'Сессия закончилась, войдите заново')
    }

    if (response.status === 204) {
      return null as T
    }

    const data = await response.json().catch(() => ({}))
    if (!response.ok) {
      throw new ApiError(response.status, data.error ?? 'Ошибка ' + response.status, data.errors)
    }
    return data as T
  }

  async function signIn(loginValue: string, password: string) {
    const data = await request<{ token: string; login: string }>('POST', '/api/admin/login', {
      login: loginValue,
      password,
    })
    saveSession(data.token, data.login)
  }

  async function signOut() {
    try {
      await request('POST', '/api/admin/logout')
    } catch {
    }
    saveSession(null, null)
    router.push('/admin/login')
  }

  return { token, login, isLoggedIn, request, signIn, signOut }
})
