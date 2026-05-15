<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';

const auth  = useAuthStore();
const route = useRoute();

// ── Mobile / sidebar ──────────────────────────────────────────
const isMobile    = ref(window.innerWidth < 1024);
const sidebarOpen = ref(!isMobile.value);

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

// ── Sucursal groups (collapsible) ─────────────────────────────
const openGroups = ref({});

function toggleGroup(id) {
    openGroups.value = { ...openGroups.value, [id]: !openGroups.value[id] };
}

// Auto-open the group that matches the current route
watch(() => route.params.sucursalId, (id) => {
    if (id) openGroups.value = { ...openGroups.value, [id]: true };
}, { immediate: true });

// ── User display ──────────────────────────────────────────────
const initials = computed(() => {
    if (!auth.user) return '?';
    return auth.user.name.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
});

const displayName = computed(() => auth.user?.nickname || auth.user?.name || '...');

const rolLabel = computed(() => {
    const map = { super_admin: 'Super Admin', admin: 'Administrador', cajero: 'Cajero' };
    return map[auth.user?.rol] ?? auth.user?.rol ?? '';
});

// ── Page title ────────────────────────────────────────────────
const pageTitle = computed(() => {
    if (route.path === '/admin') return 'Dashboard';
    if (route.path.startsWith('/admin/sucursales')) return 'Sucursales';
    if (route.path.startsWith('/admin/usuarios'))   return 'Usuarios';
    if (route.params.sucursalId) {
        const s   = auth.sucursales.find(s => s.id == route.params.sucursalId);
        const mod = { productos: 'Productos', ventas: 'Ventas', compras: 'Compras', proveedores: 'Proveedores' };
        const seg = route.path.split('/').pop();
        return s ? `${s.nombre} — ${mod[seg] ?? seg}` : (mod[seg] ?? 'Admin');
    }
    return 'Admin';
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

// ── Nav helpers ───────────────────────────────────────────────
const isActive   = (path) => route.path === path;
const isSubActive = (id)  => route.params.sucursalId == id;

const navBase  = 'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors';
const navOn    = 'bg-blue-600 text-white';
const navOff   = 'text-slate-300 hover:bg-slate-700 hover:text-white';
const subBase  = 'flex items-center gap-2.5 pl-9 pr-3 py-2 rounded-lg text-xs font-medium transition-colors';
const subOn    = 'text-blue-400 bg-slate-700';
const subOff   = 'text-slate-400 hover:text-white hover:bg-slate-700';
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-gray-100">

    <!-- Mobile overlay -->
    <div v-if="sidebarOpen && isMobile" class="fixed inset-0 z-20 bg-black/50" @click="sidebarOpen = false"/>

    <!-- Sidebar -->
    <Transition name="slide">
      <aside v-show="sidebarOpen"
        class="fixed lg:relative z-30 h-full w-64 flex-shrink-0 flex flex-col bg-slate-800 shadow-xl lg:shadow-none">

        <!-- Brand -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700">
          <div>
            <p class="text-white font-bold text-base leading-tight">Elixir Dorado</p>
            <p class="text-slate-400 text-xs">Panel de Administración</p>
          </div>
          <button class="lg:hidden text-slate-400 hover:text-white p-1.5 rounded-lg hover:bg-slate-700 transition-colors"
            @click="sidebarOpen = false">
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
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-4">

          <!-- ADMINISTRACIÓN -->
          <div v-if="auth.can('dashboard') || auth.can('sucursales') || auth.can('usuarios')">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider px-2 mb-2">Administración</p>
            <ul class="space-y-0.5">

              <li v-if="auth.can('dashboard')">
                <RouterLink to="/admin" :class="[navBase, isActive('/admin') ? navOn : navOff]"
                  @click="closeSidebarOnMobile">
                  <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
                  </svg>
                  Dashboard
                </RouterLink>
              </li>

              <li v-if="auth.can('sucursales')">
                <RouterLink to="/admin/sucursales" :class="[navBase, route.path.startsWith('/admin/sucursales') ? navOn : navOff]"
                  @click="closeSidebarOnMobile">
                  <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/>
                  </svg>
                  Sucursales
                </RouterLink>
              </li>

              <li v-if="auth.can('usuarios')">
                <RouterLink to="/admin/usuarios" :class="[navBase, route.path.startsWith('/admin/usuarios') ? navOn : navOff]"
                  @click="closeSidebarOnMobile">
                  <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                  </svg>
                  Usuarios
                </RouterLink>
              </li>

            </ul>
          </div>

          <!-- SUCURSALES (grupos) -->
          <div v-if="auth.sucursalesMenu.length">
            <p class="text-slate-500 text-xs font-semibold uppercase tracking-wider px-2 mb-2">Sucursales</p>
            <ul class="space-y-0.5">

              <li v-for="s in auth.sucursalesMenu" :key="s.id">
                <!-- Group header -->
                <button @click="toggleGroup(s.id)"
                  :class="['w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                    isSubActive(s.id) ? 'bg-slate-700 text-white' : 'text-slate-300 hover:bg-slate-700 hover:text-white']">
                  <span class="flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 1 .75.75V21H6v-2.25a.75.75 0 0 1 .75-.75Z"/>
                    </svg>
                    <span class="truncate">{{ s.nombre }}</span>
                  </span>
                  <svg :class="['w-3.5 h-3.5 flex-shrink-0 transition-transform', openGroups[s.id] ? 'rotate-90' : '']"
                    fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                  </svg>
                </button>

                <!-- Sub-items -->
                <ul v-if="openGroups[s.id]" class="mt-0.5 space-y-0.5">
                  <li>
                    <RouterLink :to="`/admin/s/${s.id}/productos`"
                      :class="[subBase, route.path === `/admin/s/${s.id}/productos` ? subOn : subOff]"
                      @click="closeSidebarOnMobile">
                      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/>
                      </svg>
                      Productos
                    </RouterLink>
                  </li>
                  <li>
                    <RouterLink :to="`/admin/s/${s.id}/ventas`"
                      :class="[subBase, route.path === `/admin/s/${s.id}/ventas` ? subOn : subOff]"
                      @click="closeSidebarOnMobile">
                      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                      </svg>
                      Ventas
                    </RouterLink>
                  </li>
                  <li>
                    <RouterLink :to="`/admin/s/${s.id}/compras`"
                      :class="[subBase, route.path === `/admin/s/${s.id}/compras` ? subOn : subOff]"
                      @click="closeSidebarOnMobile">
                      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                      </svg>
                      Compras
                    </RouterLink>
                  </li>
                  <li>
                    <RouterLink :to="`/admin/s/${s.id}/proveedores`"
                      :class="[subBase, route.path === `/admin/s/${s.id}/proveedores` ? subOn : subOff]"
                      @click="closeSidebarOnMobile">
                      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                      </svg>
                      Proveedores
                    </RouterLink>
                  </li>
                </ul>
              </li>

            </ul>
          </div>

        </nav>

        <!-- Logout -->
        <div class="px-3 py-4 border-t border-slate-700">
          <form method="POST" action="/logout">
            <input type="hidden" name="_token" :value="csrfToken">
            <button type="submit"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-700 text-sm font-medium transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
              </svg>
              Cerrar sesión
            </button>
          </form>
        </div>

      </aside>
    </Transition>

    <!-- Main -->
    <div class="flex-1 flex flex-col overflow-hidden min-w-0">
      <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center gap-3 flex-shrink-0 z-10">
        <button @click="sidebarOpen = !sidebarOpen"
          class="p-2 rounded-xl text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors flex-shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
          </svg>
        </button>
        <h1 class="text-gray-700 font-semibold text-sm flex-1 truncate">{{ pageTitle }}</h1>
        <span class="text-slate-400 text-xs hidden sm:block flex-shrink-0">Elixir Dorado</span>
      </header>
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
