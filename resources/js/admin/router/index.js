import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../layouts/AdminLayout.vue';
import Dashboard from '../views/Dashboard.vue';
import Sucursales from '../views/Sucursales.vue';
import Users from '../views/Users.vue';
import Productos from '../views/Productos.vue';

const routes = [
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: '',           name: 'dashboard',  component: Dashboard },
            { path: 'sucursales', name: 'sucursales', component: Sucursales },
            { path: 'usuarios',   name: 'usuarios',   component: Users },
            { path: 'productos',  name: 'productos',  component: Productos },
        ],
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
