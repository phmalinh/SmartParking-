import { createRouter, createWebHistory } from 'vue-router';
import Login from '@/components/Login.vue';
import Register from '@/components/Register.vue';
import ParkingScanner from '@/components/ParkingScanner.vue';

const routes = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/login',
    component: Login,
    meta: { guest: true },
  },
  {
    path: '/register',
    component: Register,
    meta: { guest: true },
  },
  {
    path: '/dashboard',
    component: ParkingScanner,
    meta: { requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/login',
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

// 🔐 AUTH GUARD
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('access_token');

  // Chưa login mà vào route cần auth
  if (to.meta.requiresAuth && !token) {
    return next('/login');
  }

  // Đã login mà vào trang login
  if (to.meta.guest && token) {
    return next('/dashboard');
  }

  next();
});

export default router;

