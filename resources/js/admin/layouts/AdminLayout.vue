<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';

const auth  = useAuthStore();
const route = useRoute();

// ── Mobile detection ──────────────────────────────────────────
const isMobile     = ref(window.innerWidth < 1024);
const sidebarOpen  = ref(!isMobile.value);

function onResize() {
    isMobile.value = window.innerWidth < 1024;
    if (!isMobile.value) sidebarOpen.value = true;
}
onMounted(() => {
    window.addEventListener('resize', onResize);
    auth.fetchUser();
});
onUnmounted(() => window.removeEventListener('resize', onResize));

function closeSidebarOnMobile() {
    if (isMobile.value) sidebarOpen.value = false;
}

// ── User info ─────────────────────────────────────────────────
const initials = computed(() => {
    if (!auth.user) return '?';
    return auth.user.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
});

const displayName = computed(() =>
    auth.user?.nickname || auth.user?.name || '...'
);

const rolLabel = computed(() => {
    const map = { super_admin: 'Super Admin', admin: 'Administrador', cajero: 'Cajero' };
    return map[auth.user?.rol] ?? auth.user?.rol ?? '';
});

const pageTitle = computed(() => {
    if (route.path === '/admin') return 'Dashboard';
    if (route.path.startsWith('/admin/sucursales')) return 'Sucursales';
    if (route.path.startsWith('/admin/usuarios'))   return 'Usuarios';
    return 'Admin';
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-gray-100">

    <!-- Mobile overlay (CSS lg:hidden handles desktop) -->
    <div
      v-if="sidebarOpen && isMobile"
      class="fixed inset-0 z-20 bg-black/50"
      @click="sidebarOpen = false"
    />

    <!-- Sidebar -->
    <Transition name="slide">
      <aside
        v-show="sidebarOpen"
        class="fixed lg:relative z-30 h-full w-64 flex-shrink-0 flex flex-col bg-slate-800 shadow-xl lg:shadow-none"
      >
        <!-- Brand -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700">
          <div>
            <p class="text-white font-bold text-base leading-tight">Elixir Dorado</p>
            <p class="text-slate-400 text-xs">Panel de Administración</p>
          </div>
          <button
            class="lg:hidden text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-700 transition-colors"
            @click="sidebarOpen = false"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- User card -->
        <div class="flex items-center gap-3 px-5 py-4 border-b border-slate-700">
          <div class="w-10 h-10 rounded-full bg-amber-500 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
            {{ initials }}
          </div>
          <div class="min-w-0">
            <p class="text-white text-sm font-semibold truncate">{{ displayName }}</p>
            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full bg-blue-600 text-white text-xs font-medium">
              {{ rolLabel }}
            </span>
          </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 overflow-y-auto px-3 py-4">
          <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider px-2 mb-3">Navegación</p>
          <ul class="space-y-0.5">

            <li>
              <RouterLink
                to="/admin"
                :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                  route.path === '/admin'
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-300 hover:bg-slate-700 hover:text-white']"
                @click="closeSidebarOnMobile"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Dashboard
              </RouterLink>
            </li>

            <li>
              <RouterLink
                to="/admin/sucursales"
                :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                  route.path.startsWith('/admin/sucursales')
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-300 hover:bg-slate-700 hover:text-white']"
                @click="closeSidebarOnMobile"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                </svg>
                Sucursales
              </RouterLink>
            </li>

            <li>
              <RouterLink
                to="/admin/usuarios"
                :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                  route.path.startsWith('/admin/usuarios')
                    ? 'bg-blue-600 text-white'
                    : 'text-slate-300 hover:bg-slate-700 hover:text-white']"
                @click="closeSidebarOnMobile"
              >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
                Usuarios
              </RouterLink>
            </li>

          </ul>
        </nav>

        <!-- Logout -->
        <div class="px-3 py-4 border-t border-slate-700">
          <form method="POST" action="/logout">
            <input type="hidden" name="_token" :value="csrfToken">
            <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-700 text-sm font-medium transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
              </svg>
              Cerrar sesión
            </button>
          </form>
        </div>

      </aside>
    </Transition>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">

      <!-- Top bar -->
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0 z-10">
        <button
          @click="sidebarOpen = !sidebarOpen"
          class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors flex-shrink-0"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
          </svg>
        </button>
        <h1 class="text-gray-700 font-semibold text-sm flex-1 truncate">{{ pageTitle }}</h1>
        <span class="text-slate-400 text-xs hidden sm:block flex-shrink-0">Elixir Dorado</span>
      </header>

      <!-- Page -->
      <main class="flex-1 overflow-auto p-4 lg:p-6">
        <RouterView />
      </main>

    </div>

  </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.25s ease; }
.slide-enter-from, .slide-leave-to       { transform: translateX(-100%); }
</style>
