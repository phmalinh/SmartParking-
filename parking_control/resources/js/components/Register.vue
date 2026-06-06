<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="max-w-md w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl">
      <div class="text-center">
        <h2 class="mt-6 text-3xl font-extrabold text-gray-900">Tạo tài khoản mới</h2>
        <p class="mt-2 text-sm text-gray-600">Đăng ký và bắt đầu sử dụng hệ thống</p>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div class="rounded-md shadow-sm space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Tên</label>
            <input v-model="form.name" type="text" required
              class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="Nguyễn Văn A">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input v-model="form.email" type="email" required
              class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="admin@example.com">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Mật khẩu</label>
            <input v-model="form.password" type="password" required
              class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="••••••••">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Xác nhận mật khẩu</label>
            <input v-model="form.password_confirmation" type="password" required
              class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              placeholder="••••••••">
          </div>
        </div>

        <div v-if="error" class="text-red-500 text-sm bg-red-50 p-2 rounded border border-red-200">
          {{ error }}
        </div>

        <div>
          <button :disabled="loading" type="submit"
            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-all">
            <span v-if="loading">Processing...</span>
            <span v-else>Register</span>
          </button>
        </div>

        <div class="text-center text-sm text-gray-600">
          Đã có tài khoản?
          <router-link to="/login" class="text-indigo-600 hover:text-indigo-800 font-semibold">Đăng nhập</router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '@/utils/axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});
const loading = ref(false);
const error = ref(null);

const handleRegister = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await api.post('/register', form.value);

    localStorage.setItem('access_token', response.data.access_token);
    localStorage.setItem('user', JSON.stringify(response.data.user));

    router.push('/dashboard');
  } catch (err) {
    error.value = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : err.response?.data?.message || 'Đăng ký thất bại. Vui lòng thử lại.';
  } finally {
    loading.value = false;
  }
};
</script>
