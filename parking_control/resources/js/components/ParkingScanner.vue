<template>
  <div class="min-h-screen bg-slate-900 text-slate-100 p-4 md:p-8">
    <header class="max-w-5xl mx-auto mb-8 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-extrabold bg-gradient-to-r from-blue-400 to-emerald-400 bg-clip-text text-transparent">
          SMART PARKING 
        </h1>
        <p class="text-slate-400 text-sm">Access Control System</p>
      </div>
      <div class="text-right">
        <span class="px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-xs text-emerald-400 animate-pulse">
          ● Online System
        </span>
      </div>
    </header>
    <main class="max-w-6xl mx-auto space-y-8">
      <!-- Input -->
       <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-slate-800 p-6 rounded-xl">
          <label class="text-sm text-slate-400">Number Plate</label>
          <div class="flex gap-2 mt-2">
            <input
              v-model="plate"
              placeholder="VD: ABC-123"
              class="flex-1 px-3 py-2 rounded bg-slate-900 border border-slate-700 focus:outline-none"
            />
            <button
              @click="checkPlate"
              class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700"
            >
              Check
            </button>
            <button
              @click="startCamera"
              class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700"
            >
              CAMERA
            </button>                    
          </div>          
            <div v-if="!isCameraOpen" class="text-center space-y-4">
            </div>
              <template v-else>
              <div class="relative bg-slate-800 rounded-3xl overflow-hidden border border-slate-700 shadow-2xl flex gap-2 mt-2">  
                <video ref="videoRef" autoplay playsinline class="w-full h-[400px] object-cover"></video>            
                <button @click="stopCamera" class="absolute top-4 right-4 p-2 bg-red-500/80 hover:bg-red-600 rounded-full z-10">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                  </svg>
                </button>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                  <div class="w-2/3 h-1/2 border-2 border-blue-500/50 rounded-lg relative">
                      <div class="absolute -top-1 -left-1 w-6 h-6 border-t-4 border-l-4 border-blue-500"></div>
                      <div class="absolute -top-1 -right-1 w-6 h-6 border-t-4 border-r-4 border-blue-500"></div>
                      <div class="absolute -bottom-1 -left-1 w-6 h-6 border-b-4 border-l-4 border-blue-500"></div>
                      <div class="absolute -bottom-1 -right-1 w-6 h-6 border-b-4 border-r-4 border-blue-500"></div>
                  </div>
                </div>
                <div class="absolute bottom-6 left-0 right-0 flex justify-center flex gap-2 mt-2">
                  <button @click="processOCR" :disabled="loading" class="px-4 py-2 rounded bg-emerald-600 hover:bg-emerald-700">Scan
                  </button>
                  <button @click="openFilePicker" class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700">
                    Select Image
                  </button>
                </div>
              </div>  
              </template>
            <canvas ref="canvasRef" class="hidden"></canvas>
          <div
            v-if="result"
            class=""
            :class="result === 'ok' ? 'bg-emerald-700' : 'bg-red-600'"
          >
          </div>
        </div>
        <!-- THÔNG TIN XE -->
        <div class="bg-slate-800 p-6 rounded-xl space-y-4">
          <h2 class="text-lg font-bold text-blue-400 flex items-center gap-2">
            📋 Vehicle Information
          </h2>
          <!-- Trạng thái -->
          <div
            class="p-4 rounded-xl text-center font-semibold"
            :class="result === 'ok'
              ? 'bg-emerald-700/30 border border-emerald-500'
              : result === 'fail'
                ? 'bg-red-700/30 border border-red-500'
                : 'bg-slate-900 border border-slate-700'"
          >
            <span v-if="result === 'ok'">✔ Vehicle Allowed Entry</span>
            <span v-else-if="result === 'fail'">❌ Unauthorized Vehicle</span>
            <span v-else>⏳ Not Checked</span>
          </div>
          <!-- Thông tin chi tiết -->
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-slate-400">OCR License Plate:</span>
              <span class="font-mono text-emerald-400">{{ plate || '---' }}</span>
            </div>

            <div class="flex justify-between">
              <span class="text-slate-400">Vehicle Owner:</span>
              <span class="font-mono text-blue-400">
                {{
                  whitelist.find(w => w.plate_number === plate)?.car_owner || 'Unknown'
                }}
              </span>
            </div>

            <div class="flex justify-between">
              <span class="text-slate-400">Time:</span>
              <span class="font-mono text-slate-300">
                {{
                  whitelist.find(w => w.plate_number === plate)?.createdAt || 'Unknown'
                }}
              </span>
            </div>
          </div>

          <!-- Gợi ý -->
          <div class="text-xs text-slate-500 border-t border-slate-700 pt-3">
            ℹ️ Information is updated after each scan or check.
          </div>
        </div>
      </div>
      <div class="bg-slate-800 p-4 rounded-xl">
        <h2 class="font-semibold mb-3 flex justify-between items-center">
          <span>List of Authorized Vehicles</span>
          <button @click="showForm = true"  class="text-xs text-blue-400 hover:underline">
            Add New
          </button>
        </h2>
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
          <div class="bg-slate-800 w-full max-w-md rounded-3xl border border-slate-700 shadow-2xl overflow-hidden animate-in zoom-in duration-200">
            <div class="p-6">
              <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-blue-400">
                {{ isEdit ? 'Update Vehicle' : 'Add New' }}
              </h2>
                <button @click="showForm = false" class="text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
              </div>
              <div class="space-y-4">
                <div>
                    <label class="text-xs text-slate-400 uppercase font-bold">Vehicle Owner Name</label>
                    <input v-model="car_owner" placeholder="VD: Name"
                          class="w-full mt-1 px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 focus:border-blue-500 outline-none font-mono text-lg" />
                </div>
                <div>
                    <label class="text-xs text-slate-400 uppercase font-bold">Number Plate</label>
                    <input v-model="plate" placeholder="VD: 51G-123.45"
                          class="w-full mt-1 px-4 py-3 rounded-xl bg-slate-900 border border-slate-700 focus:border-blue-500 outline-none font-mono text-lg" />
                </div>
                <div class="flex items-center justify-between bg-slate-900 p-3 rounded-xl border border-slate-700">
                    <span class="text-sm text-slate-300">Status:</span>
                    <button @click="newAction = !newAction"
                            :class="newAction ? 'bg-emerald-600' : 'bg-slate-600'"
                            class="px-4 py-1 rounded-full text-[10px] font-bold">
                        {{ newAction ? 'Activate' : 'Deactivate' }}
                    </button>
                </div>
                <div class="flex gap-3 pt-4">
                    <button
                      @click="resetForm"
                      class="flex-1 py-3 rounded-xl bg-slate-700 font-bold"
                    >
                      Cancel
                    </button>
                    <button
                      @click="submitPlate"
                      :disabled="loading"
                      class="flex-[2] py-3 rounded-xl bg-blue-600 font-bold disabled:opacity-50"
                    >
                      {{ loading
                        ? 'Saving…...'
                        : isEdit ? 'Update' : 'Save System'
                      }}
                    </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <ul class="space-y-2 max-h-64 overflow-y-auto pr-2">
          <li
            v-for="item in whitelist"
            :key="item.id"
            class="flex justify-between bg-slate-900 px-3 py-2 rounded border border-slate-700 items-center"
          >
            <div>
              <span class="font-mono text-emerald-400">{{ item.car_owner }}</span>
              <p class="text-[10px] text-slate-500">{{ item.createdAt }}</p>
            </div>
            <div>
              <span class="font-mono text-emerald-400">{{ item.plate_number }}</span>
            </div>
            <div>
              <span class="font-mono text-emerald-400">{{ item.createdAt }}</span>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="text-[10px] uppercase px-2 py-0.5 rounded cursor-pointer"
                :class="item.action === 'Activate' ? 'bg-emerald-700' : 'bg-slate-600'"
                @click="toggleAction(item)"
              >
                {{ item.action }}
              </span>
              <button
              @click="openEdit(item)"
                class="text-blue-400 hover:text-blue-500 text-xs"
                :disabled="loading"
              >
                ✏️
              </button>
              <button
                @click="deletePlate(item.id)"
                class="text-red-400 hover:text-red-500 text-xs"
              >
                ✖
              </button>
            </div>            
          </li>
          <li v-if="whitelist.length === 0" class="text-center text-slate-500 py-4 text-sm italic">
            Empty List
          </li>
        </ul>
      </div> 
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
import { onMounted, ref } from 'vue';
import axios from 'axios';
import Tesseract from 'tesseract.js';

const plate = ref('');
const car_owner = ref('');
const result = ref(null);
const whitelist = ref([]); // Đổi từ mảng cứng sang mảng rỗng để đợi dữ liệu từ API
const loading = ref(false);
const newAction = ref(true);
const showForm = ref(false);
const videoRef = ref(null);
const canvasRef = ref(null);
const isCameraOpen = ref(false);
const fileInputRef = ref(null);
const isEdit = ref(false);
const editingId = ref(null);
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
// Hàm gọi API GET /api/parking
async function fetchParkingList() {
    try {
        const response = await axios.get('/api/parking');
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
      await axios.put(`/api/parking/${editingId.value}`, payload);

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
      const res = await axios.post('/api/parking', payload);

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

async function deletePlate(id) {
  if (!confirm('Are you sure you want to delete this vehicle?')) return;

  loading.value = true;
  try {
    await axios.delete(`/api/parking/${id}`);
    await fetchParkingList();
    showToast('Vehicle deleted successfully');
  } catch (error) {
    console.error('Delete failed:', error);
    alert('Cannot delete');
  } finally {
    loading.value = false;
  }
}
// Hàm kiểm tra biển số qua API /api/check-plate
async function checkPlate() {
    if (!plate.value) return;
    loading.value = true;
    try {
        const response = await axios.post('/api/check-plate', {
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
    const res = await axios.post('/api/process-ai-ocr', formData);
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
      const res = await axios.post('/api/process-ai-ocr', formData);
      
      plate.value = res.data.plate; // Hiển thị biển số lên ô input
      result.value = res.data.allowed ? 'ok' : 'fail'; // Hiển thị xanh/đỏ
    } catch (error) {
      console.error("AI system connection error:", error);
      alert("The AI system is busy or not yet started!");
    } finally {
      loading.value = false;
    }
  }, 'image/jpeg');
}
</script>



