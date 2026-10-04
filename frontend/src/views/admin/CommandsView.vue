<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { ApiError, useAdminStore } from '@/stores/admin'
import type { AdminCommand } from '@/types'

const admin = useAdminStore()
const commands = ref<AdminCommand[]>([])
const error = ref<string | null>(null)

const form = ref({
  id: null as number | null,
  name: '',
  description: '',
  prompt: '',
  enabled: true,
})
const showForm = ref(false)
const formErrors = ref<Record<string, string>>({})
const saving = ref(false)

const testText = ref('')
const testLoading = ref(false)
const testResult = ref<{ prompt: string; answer: string } | null>(null)
const testError = ref<string | null>(null)

async function load() {
  try {
    commands.value = await admin.request<AdminCommand[]>('GET', '/api/admin/commands')
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не удалось загрузить команды'
  }
}

function resetTest() {
  testText.value = ''
  testResult.value = null
  testError.value = null
}

function openCreate() {
  form.value = {
    id: null,
    name: '',
    description: '',
    prompt: 'Сделай что-нибудь с текстом: {text}',
    enabled: true,
  }
  formErrors.value = {}
  resetTest()
  showForm.value = true
}

function openEdit(command: AdminCommand) {
  form.value = { ...command }
  formErrors.value = {}
  resetTest()
  showForm.value = true
}

async function save() {
  saving.value = true
  formErrors.value = {}
  const body = {
    name: form.value.name,
    description: form.value.description,
    prompt: form.value.prompt,
    enabled: form.value.enabled,
  }
  try {
    if (form.value.id === null) {
      await admin.request('POST', '/api/admin/commands', body)
    } else {
      await admin.request('PUT', '/api/admin/commands/' + form.value.id, body)
    }
    showForm.value = false
    await load()
  } catch (e) {
    if (e instanceof ApiError && e.status === 400) {
      formErrors.value = e.errors
    } else {
      formErrors.value = { common: e instanceof ApiError ? e.message : 'Не удалось сохранить' }
    }
  } finally {
    saving.value = false
  }
}

async function remove(command: AdminCommand) {
  if (!confirm('Удалить команду !' + command.name + '?')) {
    return
  }
  try {
    await admin.request('DELETE', '/api/admin/commands/' + command.id)
    if (form.value.id === command.id) {
      showForm.value = false
    }
    await load()
  } catch (e) {
    error.value = e instanceof ApiError ? e.message : 'Не удалось удалить'
  }
}

async function testPrompt() {
  testLoading.value = true
  testResult.value = null
  testError.value = null
  try {
    testResult.value = await admin.request('POST', '/api/admin/commands/test', {
      prompt: form.value.prompt,
      text: testText.value,
    })
  } catch (e) {
    testError.value = e instanceof ApiError ? e.message : 'Ошибка'
  } finally {
    testLoading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Команды бота</h3>
    <button class="btn btn-success" @click="openCreate">Добавить команду</button>
  </div>

  <div v-if="error" class="alert alert-danger">{{ error }}</div>

  <div class="table-responsive">
    <table class="table table-bordered bg-white">
      <thead>
        <tr>
          <th>Команда</th>
          <th>Описание</th>
          <th>Промпт</th>
          <th>Включена</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="command in commands"
          :key="command.id"
          :class="{ 'text-muted': !command.enabled }"
        >
          <td>
            <code>!{{ command.name }}</code>
          </td>
          <td>{{ command.description }}</td>
          <td class="prompt">{{ command.prompt }}</td>
          <td>{{ command.enabled ? 'да' : 'нет' }}</td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-primary me-1" @click="openEdit(command)">
              Изменить
            </button>
            <button class="btn btn-sm btn-outline-danger" @click="remove(command)">Удалить</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <div v-if="showForm" class="card">
    <div class="card-header">
      {{ form.id === null ? 'Новая команда' : 'Команда !' + form.name }}
    </div>
    <div class="card-body">
      <form @submit.prevent="save">
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Имя (без !)</label>
            <input
              v-model="form.name"
              class="form-control"
              :class="{ 'is-invalid': formErrors.name }"
              placeholder="перевод"
            />
            <div class="invalid-feedback">{{ formErrors.name }}</div>
          </div>
          <div class="col-md-8 mb-3">
            <label class="form-label">Описание (видно в чате)</label>
            <input
              v-model="form.description"
              class="form-control"
              :class="{ 'is-invalid': formErrors.description }"
            />
            <div class="invalid-feedback">{{ formErrors.description }}</div>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Промпт</label>
          <textarea
            v-model="form.prompt"
            class="form-control"
            :class="{ 'is-invalid': formErrors.prompt }"
            rows="4"
          ></textarea>
          <div class="invalid-feedback">{{ formErrors.prompt }}</div>
          <div class="form-text">
            Вместо <code>{text}</code> подставится текст, который пользователь написал после
            команды.
          </div>
        </div>

        <div class="form-check mb-3">
          <input id="enabled" v-model="form.enabled" type="checkbox" class="form-check-input" />
          <label for="enabled" class="form-check-label">Команда включена</label>
        </div>

        <div v-if="formErrors.common" class="alert alert-danger py-2">{{ formErrors.common }}</div>

        <button type="submit" class="btn btn-primary me-2" :disabled="saving">Сохранить</button>
        <button type="button" class="btn btn-secondary" @click="showForm = false">Отмена</button>
      </form>

      <hr />

      <h6>Проверить промпт</h6>
      <p class="text-muted small mb-2">
        Отправляет промпт из формы (даже несохранённый) в модель вместе с системным промптом.
      </p>
      <div class="input-group mb-2">
        <input v-model="testText" class="form-control" placeholder="Текст после команды" />
        <button class="btn btn-outline-secondary" :disabled="testLoading" @click="testPrompt">
          {{ testLoading ? 'Жду ответ...' : 'Проверить' }}
        </button>
      </div>
      <div v-if="testError" class="alert alert-danger py-2">{{ testError }}</div>
      <div v-if="testResult">
        <div class="small text-muted">Итоговый промпт:</div>
        <pre class="test-box">{{ testResult.prompt }}</pre>
        <div class="small text-muted">Ответ модели:</div>
        <pre class="test-box">{{ testResult.answer }}</pre>
      </div>
    </div>
  </div>
</template>

<style scoped>
.prompt {
  white-space: pre-wrap;
  font-size: 13px;
}

.test-box {
  white-space: pre-wrap;
  background: #f8f9fa;
  border: 1px solid #dee2e6;
  padding: 8px;
  font-size: 13px;
}
</style>
