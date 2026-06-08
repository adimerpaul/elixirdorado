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

    const SUCURSAL_MODULES = [
        'productos', 'ventas.nueva', 'ventas.historial',
        'compras.nueva', 'compras.historial', 'proveedores',
        'stock.minimo', 'stock.maximo',
    ];

    function can(perm) {
        if (isSuperAdmin.value) return true;
        const perms = user.value?.permisos ?? [];
        return perms.includes('*') || perms.includes(perm);
    }

    function canSucursalModule(sucursalId, module) {
        return can(`sucursal.${sucursalId}`) || can(`sucursal.${sucursalId}.${module}`);
    }

    const sucursalesMenu = computed(() => {
        if (isSuperAdmin.value) return sucursales.value;
        return sucursales.value.filter(s =>
            can(`sucursal.${s.id}`) ||
            SUCURSAL_MODULES.some(m => can(`sucursal.${s.id}.${m}`))
        );
    });

    return { user, sucursales, loaded, fetchUser, isSuperAdmin, can, canSucursalModule, sucursalesMenu };
});
