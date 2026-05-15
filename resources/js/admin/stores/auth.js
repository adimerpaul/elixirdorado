import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user       = ref(null);
    const sucursales = ref([]);
    const loaded     = ref(false);

    async function fetchUser() {
        try {
            const [meRes, sucRes] = await Promise.all([
                axios.get('/api/admin/me'),
                axios.get('/api/admin/sucursales'),
            ]);
            user.value       = meRes.data;
            sucursales.value = sucRes.data.filter(s => s.activa !== false);
        } catch {
            user.value = null;
        } finally {
            loaded.value = true;
        }
    }

    const isSuperAdmin = computed(() => user.value?.rol === 'super_admin');

    function can(perm) {
        if (isSuperAdmin.value) return true;
        const perms = user.value?.permisos ?? [];
        return perms.includes('*') || perms.includes(perm);
    }

    const sucursalesMenu = computed(() => {
        if (isSuperAdmin.value) return sucursales.value;
        return sucursales.value.filter(s => can(`sucursal.${s.id}`));
    });

    return { user, sucursales, loaded, fetchUser, isSuperAdmin, can, sucursalesMenu };
});
