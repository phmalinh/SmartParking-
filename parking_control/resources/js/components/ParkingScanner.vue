<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 p-4 md:p-8">
    <header class="max-w-5xl mx-auto mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">
          {{ t('app.title') }}
        </h1>
        <p class="text-slate-400 text-sm">{{ t('app.subtitle') }}</p>
      </div>
      <div class="text-right flex items-center gap-3">
        <select v-model="currentLocale" @change="changeLocale" class="bg-slate-800 text-slate-200 px-2 py-1 rounded">
          <option value="vi">🇻🇳 Việt</option>
          <option value="en">🇺🇸 EN</option>
          <option value="zh">tw 繁體中文</option>
        </select>
        <span
          @click="handleLogout"
          class="cursor-pointer px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs text-red-400 hover:text-red-300 hover:bg-slate-700 transition"
        >
          {{ t('logout') }}
        </span>
      </div>
    </header>
    <main class="max-w-6xl mx-auto space-y-8">
      <!-- Tabs -->
      <div class="flex gap-2 border-b border-slate-700 flex-wrap">
        <button
          @click="currentView = 'entry'"
          :class="currentView === 'entry' ? 'border-b-2 border-blue-400 text-blue-400' : 'text-slate-400 hover:text-slate-300'"
          class="px-4 py-2 transition"
        >
          {{ t('tabs.entry') }}
        </button>
        <button
          @click="currentView = 'exit'"
          :class="currentView === 'exit' ? 'border-b-2 border-emerald-400 text-emerald-400' : 'text-slate-400 hover:text-slate-300'"
          class="px-4 py-2 transition"
        >
          {{ t('tabs.exit') }}
        </button>
        <button
          @click="currentView = 'whitelist'"
          :class="currentView === 'whitelist' ? 'border-b-2 border-blue-400 text-blue-400' : 'text-slate-400 hover:text-slate-300'"
          class="px-4 py-2 transition"
        >
          {{ t('tabs.management') }}
        </button>
        <button
          @click="currentView = 'vehicleList'"
          :class="currentView === 'vehicleList' ? 'border-b-2 border-blue-400 text-blue-400' : 'text-slate-400 hover:text-slate-300'"
          class="px-4 py-2 transition"
        >
          {{ t('tabs.vehicleList') }}
        </button>
      </div>

      <!-- View Entry (Xe vào) -->
      <template v-if="currentView === 'entry'">
        <div class="bg-gradient-to-br from-blue-900/30 to-slate-800 p-6 rounded-xl border border-blue-700/50">
          <h2 class="text-2xl font-bold text-blue-400 mb-4">{{ t('entry.record') }}</h2>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-4">
              <div>
                <label class="text-sm text-slate-400">{{ t('placeholders.plate') }}</label>
                <div class="flex gap-2 mt-2">
                  <input
                    v-model="entryPlate"
                    :placeholder="t('placeholders.plate_example')"
                    class="flex-1 px-3 py-2 rounded bg-slate-900 border border-slate-700 focus:outline-none"
                  />
                  <button
                    @click="checkEntry"
                    class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700"
                  >
                    {{ t('buttons.check') }}
                  </button>
                  <button
                    @click="startEntryCamera"
                    class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700"
                  >
                    {{ t('buttons.camera') }}
                  </button>
                </div>
              </div>
              <template v-if="isEntryCameraOpen">
                <div class="relative bg-slate-800 rounded-lg overflow-hidden border border-slate-700 shadow-lg">
                  <video ref="entryCameraRef" autoplay playsinline class="w-full h-[300px] object-cover"></video>
                  <button @click="stopEntryCamera" class="absolute top-2 right-2 p-2 bg-red-500 hover:bg-red-600 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-2/3 h-1/2 border-2 border-blue-500/50 rounded-lg"></div>
                  </div>
                  <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2">
                    <button @click="scanEntry" :disabled="entryLoading" class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700">
                      {{ t('buttons.scan') }}
                    </button>
                  </div>
                </div>
                <canvas ref="entryCanvasRef" class="hidden"></canvas>
                <button 
                  @click="openEntryFilePicker"
                  class="w-full mt-2 px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-sm"
                >
                  {{ t('buttons.select_image') }}
                </button>
                <input 
                  ref="entryFileInputRef" 
                  type="file" 
                  accept="image/*" 
                  class="hidden" 
                  @change="handleEntryImageUpload"
                />
              </template>
            </div>

            <div class="bg-slate-800 p-4 rounded-lg space-y-3">
              <div
                class="p-4 rounded text-center font-semibold"
                :class="entryStatus === 'success'
                  ? 'bg-emerald-700/30 border border-emerald-500'
                  : entryStatus === 'denied'
                    ? 'bg-red-700/30 border border-red-500'
                    : 'bg-slate-900 border border-slate-700'"
              >
                <span v-if="entryStatus === 'success'">{{ t('status.entry_allowed') }}</span>
                <span v-else-if="entryStatus === 'denied'">{{ t('status.entry_denied') }}</span>
                <span v-else>{{ t('status.not_checked') }}</span>
              </div>
              <div v-if="entryReason" class="p-3 bg-yellow-900/30 border border-yellow-600 rounded text-sm text-yellow-400">
                ⚠️ {{ entryReason }}
              </div>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-400">{{ t('field.license_plate') }}</span>
                  <span class="font-mono font-bold text-emerald-400">{{ entryPlate || '---' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">{{ t('field.owner') }}</span>
                  <span class="font-mono text-blue-400">{{ entryOwner || '---' }}</span>
                </div>
                <div v-if="entryImage" class="mt-4">
                  <span class="text-slate-400">{{ t('field.captured_image') }}</span>
                  <img
                    :src="entryImage"
                    alt="Entry camera capture"
                    class="w-full rounded-xl border border-slate-700 object-contain mt-2"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template v-if="currentView === 'exit'">
        <div class="bg-gradient-to-br from-emerald-900/20 to-slate-800 p-6 rounded-xl border border-emerald-700/40">
          <h2 class="text-2xl font-bold text-emerald-400 mb-4">{{ t('exit.record') || 'Exit Record' }}</h2>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="space-y-4">
              <div>
                <label class="text-sm text-slate-400">{{ t('placeholders.plate') }}</label>
                <div class="flex gap-2 mt-2">
                  <input
                    v-model="exitPlate"
                    :placeholder="t('placeholders.plate_example')"
                    class="flex-1 px-3 py-2 rounded bg-slate-900 border border-slate-700 focus:outline-none"
                  />
                  <button @click="checkExit" class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700">{{ t('buttons.check') }}</button>
                  <button @click="startExitCamera" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700">{{ t('buttons.camera') }}</button>
                </div>
              </div>

              <template v-if="isExitCameraOpen">
                <div class="relative bg-slate-800 rounded-lg overflow-hidden border border-slate-700 shadow-lg">
                  <video ref="exitCameraRef" autoplay playsinline class="w-full h-[300px] object-cover"></video>
                  <button @click="stopExitCamera" class="absolute top-2 right-2 p-2 bg-red-500 hover:bg-red-600 rounded-full">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-2/3 h-1/2 border-2 border-emerald-500/50 rounded-lg"></div>
                  </div>
                  <div class="absolute bottom-4 left-0 right-0 flex justify-center gap-2">
                    <button @click="scanExit" :disabled="exitLoading" class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700">{{ t('buttons.scan') }}</button>
                  </div>
                </div>
                <canvas ref="exitCanvasRef" class="hidden"></canvas>
                <button @click="openExitFilePicker" class="w-full mt-2 px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-sm">{{ t('buttons.select_image') }}</button>
                <input ref="exitFileInputRef" type="file" accept="image/*" class="hidden" @change="handleExitImageUpload" />
              </template>
            </div>

            <div class="bg-slate-800 p-4 rounded-lg space-y-3">
              <div
                class="p-4 rounded text-center font-semibold"
                :class="exitStatus === 'success'
                  ? 'bg-emerald-700/30 border border-emerald-500'
                  : exitStatus === 'denied'
                    ? 'bg-red-700/30 border border-red-500'
                    : exitStatus === 'unknown'
                      ? 'bg-yellow-700/30 border border-yellow-500'
                      : 'bg-slate-900 border border-slate-700'"
              >
                <span v-if="exitStatus === 'success'">{{ t('status.entry_allowed') }}</span>
                <span v-else-if="exitStatus === 'denied'">{{ t('status.entry_denied') }}</span>
                <span v-else-if="exitStatus === 'unknown'">{{ t('status.not_checked') }}</span>
                <span v-else>{{ t('status.not_checked') }}</span>
              </div>
              <div v-if="exitReason" class="p-3 bg-yellow-900/30 border border-yellow-600 rounded text-sm text-yellow-400">⚠️ {{ exitReason }}</div>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span class="text-slate-400">{{ t('field.license_plate') }}</span>
                  <span class="font-mono font-bold text-emerald-400">{{ exitPlate || '---' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-slate-400">{{ t('field.owner') }}</span>
                  <span class="font-mono text-blue-400">{{ exitOwner || '---' }}</span>
                </div>
                <div v-if="exitImage" class="mt-4">
                  <span class="text-slate-400">{{ t('field.captured_image') }}</span>
                  <img :src="exitImage" alt="Exit camera capture" class="w-full rounded-xl border border-slate-700 object-contain mt-2" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <template v-if="currentView === 'whitelist'">
        <ParkingHistory />
      </template>

      <template v-if="currentView === 'vehicleList'">
        <div class="bg-slate-800 p-6 rounded-3xl border border-slate-700 shadow-xl">
          <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
            <div>
              <h2 class="text-xl font-semibold text-white">{{ t('vehicle_list.title') }}</h2>
              <p class="text-sm text-slate-400">{{ t('vehicle_list.desc') }}</p>
            </div>
            <button
              @click="openNewVehicle"
              class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400 transition"
            >
              {{ t('buttons.add_vehicle') }}
            </button>
          </div>

          <div class="grid gap-3 lg:grid-cols-[1fr_auto_auto] mb-6">
            <input
              v-model="searchQuery"
              :placeholder="t('placeholders.search_vehicle')"
              class="w-full rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-100 outline-none focus:border-blue-500"
            />
            <select
              v-model="filterStatus"
              class="w-full rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-100 outline-none focus:border-blue-500"
            >
              <option value="">{{ t('filters.all_status') }}</option>
              <option value="Activate">{{ t('status.activate') }}</option>
              <option value="Deactivate">{{ t('status.deactivate') }}</option>
            </select>
            <button
              @click="fetchParkingList"
              class="w-full rounded-2xl bg-slate-700 px-4 py-3 text-sm font-semibold text-slate-100 hover:bg-slate-600 transition"
            >
              {{ t('buttons.refresh') }}
            </button>
          </div>

          <div class="grid gap-3 sm:grid-cols-3 mb-6">
            <div class="rounded-3xl bg-slate-900 p-4 border border-slate-700">
              <div class="text-sm text-slate-400">{{ t('stats.total_vehicles') }}</div>
              <div class="text-3xl font-semibold text-white">{{ whitelist.length }}</div>
            </div>
            <div class="rounded-3xl bg-slate-900 p-4 border border-slate-700">
              <div class="text-sm text-slate-400">{{ t('stats.active') }}</div>
              <div class="text-3xl font-semibold text-emerald-400">{{ activeCount }}</div>
            </div>
            <div class="rounded-3xl bg-slate-900 p-4 border border-slate-700">
              <div class="text-sm text-slate-400">{{ t('stats.inactive') }}</div>
              <div class="text-3xl font-semibold text-amber-400">{{ inactiveCount }}</div>
            </div>
          </div>

          <div class="overflow-x-auto rounded-3xl border border-slate-700 bg-slate-950/40">
            <table class="min-w-full text-left text-sm text-slate-100">
              <thead class="border-b border-slate-700 bg-slate-900/90 text-slate-400 uppercase text-xs tracking-[0.12em]">
                <tr>
                  <th class="px-4 py-3">{{ t('table.owner') }}</th>
                  <th class="px-4 py-3">{{ t('table.plate') }}</th>
                  <th class="px-4 py-3">{{ t('table.status') }}</th>
                  <th class="px-4 py-3">{{ t('table.created_at') }}</th>
                  <th class="px-4 py-3">{{ t('table.actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in filteredWhitelist" :key="item.id" class="border-b border-slate-800/70 hover:bg-slate-900/70">
                  <td class="px-4 py-4">
                    <div class="text-white font-semibold">{{ item.car_owner }}</div>
                    <div class="text-xs text-slate-500">ID: {{ item.id }}</div>
                  </td>
                  <td class="px-4 py-4 font-mono text-emerald-300">{{ item.plate_number }}</td>
                  <td class="px-4 py-4">
                    <span :class="item.action === 'Activate' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-600/30 text-slate-300'" class="inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                      {{ item.action }}
                    </span>
                  </td>
                  <td class="px-4 py-4 text-slate-400">{{ item.createdAt || '—' }}</td>
                  <td class="px-4 py-4 flex flex-wrap gap-2">
                    <button
                      @click="openEdit(item)"
                      class="rounded-full bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 transition"
                    >{{ t('buttons.edit') }}</button>
                    <button
                      @click="toggleAction(item)"
                      class="rounded-full bg-amber-500 px-3 py-2 text-xs font-semibold text-slate-950 hover:bg-amber-400 transition"
                    >{{ item.action === 'Activate' ? t('buttons.lock') : t('buttons.unlock') }}</button>
                    <button
                      @click="deletePlate(item.id)"
                      class="rounded-full bg-red-500 px-3 py-2 text-xs font-semibold text-white hover:bg-red-400 transition"
                    >{{ t('buttons.delete') }}</button>
                  </td>
                </tr>
                <tr v-if="filteredWhitelist.length === 0" class="bg-slate-900">
                  <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('vehicle_list.empty') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Add/Edit Modal -->
          <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/50" @click="closeForm"></div>
            <div class="relative w-full max-w-xl bg-slate-900 rounded-3xl border border-slate-700 p-6 z-10">
              <h3 class="text-lg font-semibold text-white mb-4">{{ isEdit ? t('modal.edit_title') : t('modal.add_title') }}</h3>
              <div class="grid gap-3">
                <label class="text-sm text-slate-400">{{ t('modal.owner') }}</label>
                <input v-model="car_owner" class="w-full rounded-2xl border border-slate-700 bg-slate-800 px-4 py-3 text-slate-100 outline-none" />
                <label class="text-sm text-slate-400">{{ t('modal.plate') }}</label>
                <input v-model="plate" class="w-full rounded-2xl border border-slate-700 bg-slate-800 px-4 py-3 text-slate-100 outline-none font-mono" />
                <div class="flex items-center gap-3">
                  <label class="text-sm text-slate-400">{{ t('modal.status') }}</label>
                  <button @click="newAction = true" :class="newAction ? 'bg-emerald-500 text-slate-900' : 'bg-slate-700 text-slate-200'" class="px-3 py-1 rounded-2xl text-sm">{{ t('status.activate') }}</button>
                  <button @click="newAction = false" :class="!newAction ? 'bg-amber-400 text-slate-900' : 'bg-slate-700 text-slate-200'" class="px-3 py-1 rounded-2xl text-sm">{{ t('status.deactivate') }}</button>
                </div>
                <div class="flex justify-end gap-3 mt-4">
                  <button @click="closeForm" class="px-4 py-2 rounded-2xl bg-slate-700 text-slate-200">{{ t('modal.cancel') }}</button>
                  <button @click="submitPlate" :disabled="loading" class="px-4 py-2 rounded-2xl bg-emerald-500 text-slate-900 font-semibold">{{ loading ? t('modal.save') + '...' : (isEdit ? t('modal.save') : t('modal.save')) }}</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </main> 
    <canvas ref="canvasRef" class="hidden"></canvas>
    <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleImageUpload"/>
  </div>
  <div
    v-if="toast.show"
    class="fixed bottom-6 right-6 px-4 py-3 rounded-xl shadow-xl text-sm z-50"
    :class="toast.type === 'success'
      ? 'bg-emerald-600 text-white'
      : 'bg-red-600 text-white'"
  >
    {{ toast.message }}
  </div>
</template>
<script setup>
import { onMounted, ref, computed } from 'vue';
import api from '@/utils/axios';
import { useRouter } from 'vue-router';
import ParkingHistory from './ParkingHistory.vue';
import { t, locale, setLocale } from '../i18n';

const plate = ref('');
const car_owner = ref('');
const result = ref(null);
const currentView = ref('entry');
const whitelist = ref([]); // Đổi từ mảng cứng sang mảng rỗng để đợi dữ liệu từ API
const loading = ref(false);
const newAction = ref(true);
const showForm = ref(false);
const currentLocale = ref(locale.value);
const searchQuery = ref('');
const filterStatus = ref('');

const filteredWhitelist = computed(() => {
  return whitelist.value.filter(item => {
    const query = searchQuery.value.trim().toLowerCase();
    const matchesQuery = query
      ? item.car_owner.toLowerCase().includes(query) || item.plate_number.toLowerCase().includes(query)
      : true;
    const matchesStatus = filterStatus.value ? item.action === filterStatus.value : true;
    return matchesQuery && matchesStatus;
  });
});
const activeCount = computed(() => whitelist.value.filter(item => item.action === 'Activate').length);
const inactiveCount = computed(() => whitelist.value.filter(item => item.action === 'Deactivate').length);

// Entry (Xe vào)
const entryPlate = ref('');
const entryStatus = ref(null);
const entryOwner = ref('');
const entryReason = ref('');
const entryLoading = ref(false);
const isEntryCameraOpen = ref(false);
const entryCameraRef = ref(null);
const entryCanvasRef = ref(null);
const entryFileInputRef = ref(null);
const entryImage = ref(null);

// Exit (Xe ra)
const exitPlate = ref('');
const exitStatus = ref(null);
const exitOwner = ref('');
const exitReason = ref('');
const exitLoading = ref(false);
const isExitCameraOpen = ref(false);
const exitCameraRef = ref(null);
const exitCanvasRef = ref(null);
const exitFileInputRef = ref(null);
const exitImage = ref(null);
const videoRef = ref(null);
const canvasRef = ref(null);
const isCameraOpen = ref(false);
const fileInputRef = ref(null);
const isEdit = ref(false);
const editingId = ref(null);
const plateImage = ref(null)
const toast = ref({
  show: false,
  message: '',
  type: 'success'
});
let activeStream = null; // Lưu trữ stream để tắt sau này

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type };
  setTimeout(() => (toast.value.show = false), 3000);
}
const router = useRouter();
const handleLogout = async () => {
  if (!confirm('Are you sure you want to exit?')) return;

  try {
    await api.post('/logout');
  } finally {
    localStorage.clear();
    router.push('/login');
  }
};
// Hàm gọi API GET /api/parking
async function fetchParkingList() {
    try {
        const response = await api.get('/parking');
        whitelist.value = response.data.data;
    } catch (error) {
        console.error("Error while fetching the list:", error);
    }
}
function openEdit(item) {
  isEdit.value = true;
  editingId.value = item.id;

  car_owner.value = item.car_owner;
  plate.value = item.plate_number;
  newAction.value = item.action === 'Activate';

  showForm.value = true; // MỞ FORM THÊM
}
async function submitPlate() {
  if (!plate.value || !car_owner.value) return;

  loading.value = true;

  const payload = {
    car_owner: car_owner.value,
    plate_number: plate.value,
    action: newAction.value ? 'Activate' : 'Deactivate'
  };

  try {
    if (isEdit.value) {
      // UPDATE
      await api.put(`/parking/${editingId.value}`, payload);

      const index = whitelist.value.findIndex(w => w.id === editingId.value);
      if (index !== -1) {
        whitelist.value[index] = {
          ...whitelist.value[index],
          ...payload
        };
      }

      showToast('Vehicle updated successfully');
    } else {
      //ADD 
      const res = await api.post('/parking', payload);

      whitelist.value.unshift(res.data.data ?? payload);
      showToast('Vehicle added successfully');
    }
    closeForm(); //ĐÓNG FORM SAU KHI OK
  } catch (error) {
    console.error(error);
    showToast('Operation failed', 'error');
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  showForm.value = false;
  isEdit.value = false;
  editingId.value = null;

  plate.value = '';
  car_owner.value = '';
  newAction.value = true;
}
function closeForm() {
  showForm.value = false;
  isEdit.value = false;
  editingId.value = null;

  plate.value = '';
  car_owner.value = '';
  newAction.value = true;
}
function openNewVehicle() {
  resetForm();
  showForm.value = true;
}

function changeLocale() {
  setLocale(currentLocale.value);
}

async function deletePlate(id) {
  if (!confirm('Are you sure you want to delete this vehicle?')) return;

  loading.value = true;
  try {
    await api.delete(`/parking/${id}`);
    await fetchParkingList();
    showToast('Vehicle deleted successfully');
  } catch (error) {
    console.error('Delete failed:', error);
    alert('Cannot delete');
  } finally {
    loading.value = false;
  }
}

async function toggleAction(item) {
  loading.value = true;
  try {
    const newStatus = item.action === 'Activate' ? 'Deactivate' : 'Activate';
    const payload = {
      car_owner: item.car_owner,
      plate_number: item.plate_number,
      action: newStatus
    };
    await api.put(`/parking/${item.id}`, payload);
    item.action = newStatus;
    showToast('Vehicle status updated successfully');
  } catch (error) {
    console.error('Toggle action failed:', error);
    showToast('Cannot update status', 'error');
  } finally {
    loading.value = false;
  }
}
// Hàm kiểm tra biển số qua API /api/check-plate
async function checkPlate() {
    if (!plate.value) return;
    loading.value = true;
    try {
        const response = await api.post('/check-plate', {
            plate: plate.value 
        });        
        // Cập nhật giao diện dựa trên kết quả trả về từ Controller
        result.value = response.data.allowed ? 'ok' : 'fail';
        
        // Sau khi kiểm tra, nếu muốn danh sách cập nhật mới nhất thì gọi lại:
        fetchParkingList();
    } catch (error) {
        console.error("API connection error:", error);
    } finally {
        loading.value = false;
    }
}
// Hàm Bật Camera
async function startCamera() {
  try {
    isCameraOpen.value = true;
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'environment' } 
    });
    activeStream = stream;
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
    }
  } catch (err) {
    console.error("Cannot access camera:", err);
    alert("Please allow camera access!");
    isCameraOpen.value = false;
  }
}
function openFilePicker() {
  if (isCameraOpen.value) stopCamera();
  fileInputRef.value.click();
}

async function handleImageUpload(event) {
  const file = event.target.files[0];
  if (!file) return;

  loading.value = true;

  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await api.post('/process-ai-ocr', formData);
    plate.value = res.data.plate;
    result.value = res.data.allowed ? 'ok' : 'fail';
  } catch (e) {
    alert("OCR image error");
  } finally {
    loading.value = false;
    event.target.value = '';
  }
}

// Hàm Tắt Camera
function stopCamera() {
  if (activeStream) {
    activeStream.getTracks().forEach(track => track.stop()); // Dừng hẳn phần cứng camera
    activeStream = null;
  }
  isCameraOpen.value = false;
}

// ===== ENTRY (XE VÀO) =====
async function checkEntry() {
  entryLoading.value = true;
  try {
    const response = await api.post('/check-plate', { plate: entryPlate.value });
    entryStatus.value = response.data.allowed ? 'success' : 'denied';
    entryReason.value = response.data.reason || '';
    
    if (response.data.allowed) {
      // Lấy thông tin chủ xe
      const vehicle = whitelist.value.find(v => v.plate_number.includes(entryPlate.value));
      entryOwner.value = vehicle?.car_owner || 'Unknown';
    }
  } catch (error) {
    console.error('Error checking entry:', error);
    entryStatus.value = 'error';
    entryReason.value = '';
  } finally {
    entryLoading.value = false;
  }
}
// ===== ENTRY (XE VÀO) =====
async function startEntryCamera() {
  try {
    isEntryCameraOpen.value = true;
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'environment' } 
    });
    if (entryCameraRef.value) {
      entryCameraRef.value.srcObject = stream;
    }
  } catch (err) {
    console.error('Cannot access camera:', err);
    alert('Please allow camera access!');
    isEntryCameraOpen.value = false;
  }
}

function stopEntryCamera() {
  const stream = entryCameraRef.value?.srcObject;
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
  }
  isEntryCameraOpen.value = false;
}

async function scanEntry() {
  if (!entryCameraRef.value || !entryCanvasRef.value) return;
  entryLoading.value = true;

  const video = entryCameraRef.value;
  const canvas = entryCanvasRef.value;
  const ctx = canvas.getContext('2d');

  canvas.width = video.videoWidth * 0.66;
  canvas.height = video.videoHeight * 0.5;

  const cropX = (video.videoWidth - canvas.width) / 2;
  const cropY = (video.videoHeight - canvas.height) / 2;

  ctx.drawImage(video, cropX, cropY, canvas.width, canvas.height, 0, 0, canvas.width, canvas.height);

  canvas.toBlob(async (blob) => {
    if (blob) {
      entryImage.value = URL.createObjectURL(blob);
    }
    const formData = new FormData();
    formData.append('file', blob, 'entry.jpg');

    try {
      const res = await api.post('/process-ai-ocr', formData);
      entryPlate.value = res.data.plate;
      entryStatus.value = res.data.allowed ? 'success' : 'denied';
      entryReason.value = res.data.reason || '';
      entryOwner.value = res.data.vehicle?.car_owner || 'Unknown';
    } catch (error) {
      console.error('OCR error:', error);
      entryStatus.value = 'error';
      entryReason.value = '';
    } finally {
      entryLoading.value = false;
    }
  }, 'image/jpeg');
}

// ===== ENTRY FILE UPLOAD =====
function openEntryFilePicker() {
  entryFileInputRef.value?.click();
}

async function handleEntryImageUpload(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  entryLoading.value = true;
  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await api.post('/process-ai-ocr', formData);
    entryPlate.value = res.data.plate;
    entryStatus.value = res.data.allowed ? 'success' : 'denied';
    entryReason.value = res.data.reason || '';
    entryOwner.value = res.data.vehicle?.car_owner || 'Unknown';
  } catch (error) {
    console.error('Upload OCR error:', error);
    entryStatus.value = 'error';
    entryReason.value = '';
  } finally {
    if (file) {
      entryImage.value = URL.createObjectURL(file);
    }
    entryLoading.value = false;
    if (entryFileInputRef.value) {
      entryFileInputRef.value.value = '';
    }
  }
}

// ===== EXIT (XE RA) =====
async function checkExit() {
  exitLoading.value = true;
  try {
    const response = await api.post('/check-exit', { plate: exitPlate.value });
    if (response.data.success) {
      exitStatus.value = 'success';
      exitOwner.value = response.data.vehicle?.owner || 'Unknown';
    } else if (response.data.vehicle?.registered) {
      exitStatus.value = 'denied'; // Xe không ở trong bãi hoặc đã ra rồi
      exitOwner.value = response.data.vehicle?.owner || 'Unknown';
    } else {
      exitStatus.value = 'unknown'; // Xe không trong hệ thống
      exitOwner.value = 'Unknown';
    }
  } catch (error) {
    if (error.response?.status === 400 || error.response?.status === 404) {
      exitStatus.value = 'denied'; // Xe chưa vào hoặc đã ra rồi
      exitOwner.value = error.response?.data?.vehicle?.owner || 'Unknown';
    } else {
      console.error('Error checking exit:', error);
      exitStatus.value = 'error';
    }
  } finally {
    exitLoading.value = false;
  }
}

async function startExitCamera() {
  try {
    isExitCameraOpen.value = true;
    const stream = await navigator.mediaDevices.getUserMedia({ 
      video: { facingMode: 'environment' } 
    });
    if (exitCameraRef.value) {
      exitCameraRef.value.srcObject = stream;
    }
  } catch (err) {
    console.error('Cannot access camera:', err);
    alert('Please allow camera access!');
    isExitCameraOpen.value = false;
  }
}

function stopExitCamera() {
  const stream = exitCameraRef.value?.srcObject;
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
  }
  isExitCameraOpen.value = false;
}

async function scanExit() {
  if (!exitCameraRef.value || !exitCanvasRef.value) return;
  exitLoading.value = true;

  const video = exitCameraRef.value;
  const canvas = exitCanvasRef.value;
  const ctx = canvas.getContext('2d');

  canvas.width = video.videoWidth * 0.66;
  canvas.height = video.videoHeight * 0.5;

  const cropX = (video.videoWidth - canvas.width) / 2;
  const cropY = (video.videoHeight - canvas.height) / 2;

  ctx.drawImage(video, cropX, cropY, canvas.width, canvas.height, 0, 0, canvas.width, canvas.height);

  canvas.toBlob(async (blob) => {
    if (blob) {
      exitImage.value = URL.createObjectURL(blob);
    }
    const formData = new FormData();
    formData.append('file', blob, 'exit.jpg');

    try {
      const res = await api.post('/process-ai-ocr-exit', formData);
      exitPlate.value = res.data.plate;
      
      if (res.data.success) {
        exitStatus.value = 'success';
      } else if (res.data.vehicle?.registered) {
        exitStatus.value = 'denied'; // Xe không ở trong bãi
      } else {
        exitStatus.value = 'unknown'; // Xe không trong hệ thống
      }
      
      exitOwner.value = res.data.vehicle?.owner || 'Unknown';
    } catch (error) {
      console.error('OCR error:', error);
      exitStatus.value = 'error';
    } finally {
      exitLoading.value = false;
    }
  }, 'image/jpeg');
}

// ===== EXIT FILE UPLOAD =====
function openExitFilePicker() {
  exitFileInputRef.value?.click();
}

async function handleExitImageUpload(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  exitLoading.value = true;
  const formData = new FormData();
  formData.append('file', file);

  try {
    const res = await api.post('/process-ai-ocr-exit', formData);
    exitPlate.value = res.data.plate;
    
    if (res.data.success) {
      exitStatus.value = 'success';
    } else if (res.data.vehicle?.registered) {
      exitStatus.value = 'denied'; // Xe không ở trong bãi
    } else {
      exitStatus.value = 'unknown'; // Xe không trong hệ thống
    }
    
    exitOwner.value = res.data.vehicle?.owner || 'Unknown';
  } catch (error) {
    console.error('Upload OCR error:', error);
    exitStatus.value = 'error';
  } finally {
    if (file) {
      exitImage.value = URL.createObjectURL(file);
    }
    exitLoading.value = false;
    if (exitFileInputRef.value) {
      exitFileInputRef.value.value = '';
    }
  }
}

onMounted(async () => {
  fetchParkingList();
});

//  Hàm chụp ảnh từ video và nhận diện biển số
  async function processOCR() {
  if (!videoRef.value || !canvasRef.value) return;

  loading.value = true;

  const video = videoRef.value;
  const canvas = canvasRef.value;
  const ctx = canvas.getContext('2d');

  const vw = video.videoWidth;
  const vh = video.videoHeight;

  // ROI CHỈ DÙNG CHO CAMERA
  const cropWidth = vw * 0.66;
  const cropHeight = vh * 0.5;
  const cropX = (vw - cropWidth) / 2;
  const cropY = (vh - cropHeight) / 2;

  canvas.width = cropWidth;
  canvas.height = cropHeight;

  ctx.drawImage(
    video,
    cropX,
    cropY,
    cropWidth,
    cropHeight,
    0,
    0,
    cropWidth,
    cropHeight
  );
  // Chuyển ảnh thành Blob để gửi đi
  canvas.toBlob(async (blob) => {
    const formData = new FormData();
    formData.append('file', blob, 'capture.jpg');

    try {
      // Gọi đến Laravel (Laravel sẽ tự gọi sang Python)
      const res = await api.post('/process-ai-ocr', formData);
      
      plate.value = res.data.plate; // Hiển thị biển số lên ô input
      result.value = res.data.allowed ? 'ok' : 'fail'; // Hiển thị xanh/đỏ
      if (res.data.plate_image) {
        plateImage.value = `data:image/jpeg;base64,${res.data.plate_image}`
      } else {
        plateImage.value = null
      }
    } catch (error) {
      console.error("AI system connection error:", error);
      alert("The AI system is busy or not yet started!");
    } finally {
      loading.value = false;
    }
  }, 'image/jpeg');
}
</script>



