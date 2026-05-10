import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loaded = ref(false);

    async function fetchUser() {
        try {
            const { data } = await axios.get('/api/admin/me');
            user.value = data;
        } catch {
            user.value = null;
        } finally {
            loaded.value = true;
        }
    }

    return { user, loaded, fetchUser };
});
