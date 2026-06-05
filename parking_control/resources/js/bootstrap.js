import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const api = axios.create({
  baseURL: "http://127.0.0.1:8000/api", // URL backend
  headers: {
    "Content-Type": "application/json",
  },
});

// Gắn token tự động
api.interceptors.request.use((config) => {
  const token = localStorage.getItem("access_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export default api;
