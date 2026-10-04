<script setup lang="ts">
import { useAdminStore } from '@/stores/admin'

const admin = useAdminStore()
</script>

<template>
  <nav class="navbar navbar-expand navbar-dark bg-dark px-3">
    <span class="navbar-brand">Админка чата</span>
    <ul class="navbar-nav me-auto">
      <li class="nav-item">
        <RouterLink to="/admin" class="nav-link" exact-active-class="active">Команды</RouterLink>
      </li>
      <li class="nav-item">
        <RouterLink to="/admin/settings" class="nav-link" active-class="active">
          Системный промпт
        </RouterLink>
      </li>
      <li class="nav-item">
        <RouterLink to="/" class="nav-link">В чат</RouterLink>
      </li>
    </ul>
    <span class="text-white-50 me-2">{{ admin.login }}</span>
    <button class="btn btn-sm btn-outline-light" @click="admin.signOut()">Выйти</button>
  </nav>

  <div class="container my-4">
    <RouterView />
  </div>
</template>
