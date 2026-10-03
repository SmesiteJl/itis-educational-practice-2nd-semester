import { createRouter, createWebHistory } from 'vue-router'
import ChatView from '@/views/ChatView.vue'
import AdminLoginView from '@/views/admin/AdminLoginView.vue'
import AdminLayout from '@/views/admin/AdminLayout.vue'
import CommandsView from '@/views/admin/CommandsView.vue'
import SettingsView from '@/views/admin/SettingsView.vue'
import { useAdminStore } from '@/stores/admin'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'chat', component: ChatView },
    { path: '/admin/login', name: 'admin-login', component: AdminLoginView },
    {
      path: '/admin',
      component: AdminLayout,
      meta: { requiresAdmin: true },
      children: [
        { path: '', name: 'admin-commands', component: CommandsView },
        { path: 'settings', name: 'admin-settings', component: SettingsView },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const admin = useAdminStore()
  if (to.meta.requiresAdmin && !admin.isLoggedIn()) {
    return '/admin/login'
  }
})

export default router
