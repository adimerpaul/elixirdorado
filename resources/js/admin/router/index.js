import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../layouts/AdminLayout.vue';
import Dashboard from '../views/Dashboard.vue';
import Sucursales from '../views/Sucursales.vue';
import Users from '../views/Users.vue';
import SucursalProductos from '../views/sucursal/Productos.vue';
import SucursalVentas from '../views/sucursal/Ventas.vue';
import SucursalCompras from '../views/sucursal/Compras.vue';
import SucursalProveedores from '../views/sucursal/Proveedores.vue';

const routes = [
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            { path: '',            name: 'dashboard',           component: Dashboard },
            { path: 'sucursales',  name: 'sucursales',          component: Sucursales },
            { path: 'usuarios',    name: 'usuarios',            component: Users },
            { path: 's/:sucursalId/productos',   name: 'sucursal.productos',   component: SucursalProductos },
            { path: 's/:sucursalId/ventas',      name: 'sucursal.ventas',      component: SucursalVentas },
            { path: 's/:sucursalId/compras',     redirect: to => `/admin/s/${to.params.sucursalId}/compras/nueva` },
            { path: 's/:sucursalId/compras/nueva',     name: 'sucursal.compras.nueva',     component: SucursalCompras },
            { path: 's/:sucursalId/compras/historial', name: 'sucursal.compras.historial', component: SucursalCompras },
            { path: 's/:sucursalId/proveedores', name: 'sucursal.proveedores', component: SucursalProveedores },
        ],
    },
];

export default createRouter({
    history: createWebHistory(),
    routes,
});
