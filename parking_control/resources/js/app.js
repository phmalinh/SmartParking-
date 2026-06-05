// import './bootstrap';
// import { createApp } from 'vue';
// import ParkingScanner from './components/ParkingScanner.vue';

// createApp(ParkingScanner).mount('#app');
import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

createApp(App)
  .use(router)
  .mount('#app');

