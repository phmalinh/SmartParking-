<template>
  <div class="bg-slate-800 p-6 rounded-3xl border border-slate-700 shadow-xl">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white">{{ t('history.title') }}</h2>
        <p class="text-sm text-slate-400">{{ t('history.description') }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          @click="activeTab = 'today'"
          :class="activeTab === 'today' ? 'bg-blue-500 text-white' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
          class="px-4 py-2 rounded-2xl text-sm font-semibold transition"
        >
          {{ t('history.today') }}
        </button>
        <button
          @click="activeTab = 'all'"
          :class="activeTab === 'all' ? 'bg-blue-500 text-white' : 'bg-slate-700 text-slate-200 hover:bg-slate-600'"
          class="px-4 py-2 rounded-2xl text-sm font-semibold transition"
        >
          {{ t('history.all') }}
        </button>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_0.8fr] mb-6">
        <input
          v-model="filterPlate"
          :placeholder="t('history.filter_plate')"
          class="w-full rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-100 outline-none focus:border-blue-500"
        />
      <select
        v-model="filterAction"
        class="w-full rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-100 outline-none focus:border-blue-500"
      >
        <option value="">{{ t('history.all_actions') }}</option>
        <option value="Entry">{{ t('history.entry') }}</option>
        <option value="Exit">{{ t('history.exit') }}</option>
      </select>
      <input
        v-model="filterStartDate"
        type="date"
        class="w-full rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-slate-100 outline-none focus:border-blue-500"
      />
        <button
          @click="loadHistory"
          :disabled="loading"
          class="w-full rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-slate-950 hover:bg-emerald-400 transition disabled:opacity-50"
        >
          {{ loading ? t('history.loading') : t('history.filter_data') }}
        </button>
    </div>

    <div v-if="statistics" class="grid gap-4 sm:grid-cols-3 mb-6">
      <div class="rounded-3xl border border-slate-700 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ t('stats.entries') }}</p>
        <p class="mt-3 text-3xl font-bold text-emerald-400">{{ statistics.entries }}</p>
      </div>
      <div class="rounded-3xl border border-slate-700 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ t('stats.exits') }}</p>
        <p class="mt-3 text-3xl font-bold text-blue-400">{{ statistics.exits }}</p>
      </div>
      <div class="rounded-3xl border border-slate-700 bg-slate-900/80 p-5">
        <p class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ t('stats.total') }}</p>
        <p class="mt-3 text-3xl font-bold text-amber-400">{{ statistics.total }}</p>
      </div>
    </div>

    <div class="overflow-x-auto rounded-3xl border border-slate-700 bg-slate-950/40 shadow-inner">
      <table class="min-w-full text-left text-sm text-slate-100">
        <thead class="bg-slate-900/95 text-slate-400 uppercase text-xs tracking-[0.18em]">
          <tr>
            <th class="px-4 py-4">{{ t('modal.plate') }}</th>
            <th class="px-4 py-4">{{ t('modal.owner') }}</th>
            <th class="px-4 py-4">{{ t('history.entry') }}/{{ t('history.exit') }}</th>
            <th class="px-4 py-4">{{ t('modal.time') }}</th>
            <th class="px-4 py-4">{{ t('modal.notes') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in filteredHistory"
            :key="item.id"
            class="border-b border-slate-800/80 hover:bg-slate-900/80"
          >
            <td class="px-4 py-4 font-mono text-emerald-300 font-semibold">{{ item.plate_number }}</td>
            <td class="px-4 py-4 text-slate-300">{{ item.car_owner || 'N/A' }}</td>
            <td class="px-4 py-4">
              <span
                :class="item.action === 'Entry' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300'"
                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
              >
                {{ item.action === 'Entry' ? '📥 ' + t('history.entry') : '📤 ' + t('history.exit') }}
              </span>
            </td>
            <td class="px-4 py-4 text-slate-400 text-xs">{{ formatTime(item.action_time) }}</td>
            <td class="px-4 py-4 text-slate-400 text-xs">{{ item.notes || '-' }}</td>
          </tr>
          <tr v-if="filteredHistory.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-slate-500">{{ t('history.no_data') }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="history.length > 0" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between text-sm">
      <p class="text-slate-400">{{ t('history.total_records') }}: {{ history.length }}</p>
      <div class="flex flex-wrap items-center gap-2">
        <button
          @click="currentPage = Math.max(1, currentPage - 1)"
          :disabled="currentPage === 1"
          class="rounded-2xl bg-slate-700 px-4 py-2 text-slate-200 hover:bg-slate-600 disabled:opacity-50"
        >
          ← {{ t('history.previous') }}
        </button>
        <span class="px-4 py-2 rounded-2xl bg-slate-900 text-slate-300">{{ currentPage }} / {{ totalPages }}</span>
        <button
          @click="currentPage = Math.min(totalPages, currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="rounded-2xl bg-slate-700 px-4 py-2 text-slate-200 hover:bg-slate-600 disabled:opacity-50"
        >
          {{ t('history.next') }} →
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { t } from '../i18n';

export default {
  name: 'ParkingHistory',
  data() {
    return {
      history: [],
      statistics: null,
      loading: false,
      activeTab: 'today',
      currentPage: 1,
      pageSize: 10,
      filterPlate: '',
      filterAction: '',
      filterStartDate: new Date().toISOString().split('T')[0],
    };
  },
  computed: {
    filteredHistory() {
      let filtered = this.history;

      if (this.filterPlate) {
        filtered = filtered.filter(h =>
          h.plate_number.toUpperCase().includes(this.filterPlate.toUpperCase())
        );
      }

      if (this.filterAction) {
        filtered = filtered.filter(h => h.action === this.filterAction);
      }

      return filtered.slice(
        (this.currentPage - 1) * this.pageSize,
        this.currentPage * this.pageSize
      );
    },
    totalPages() {
      return Math.ceil(this.history.length / this.pageSize);
    },
  },
  methods: {
    t,
    async loadHistory() {
      this.loading = true;
      try {
        const token = localStorage.getItem('access_token');

        let url = '/api/history';
        let params = {};

        if (this.activeTab === 'today') {
          url = '/api/history/today';
          if (this.filterAction) {
            params.action = this.filterAction;
          }
        } else if (this.activeTab === 'all') {
          params.limit = 200;
        }

        const response = await axios.get(url, {
          params,
          headers: {
            Authorization: `Bearer ${token}`,
            'Content-Type': 'application/json',
          },
        });

        this.history = response.data.data || [];
        this.currentPage = 1;

        // Tải thống kê
        if (this.activeTab === 'today') {
          await this.loadStatistics();
        }
      } catch (error) {
        console.error('Lỗi tải lịch sử:', error);
        this.$emit('error', 'Không thể tải lịch sử');
      } finally {
        this.loading = false;
      }
    },

    async loadStatistics() {
      try {
        const token = localStorage.getItem('access_token');
        const response = await axios.get('/api/history/statistics', {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        this.statistics = response.data.data;
      } catch (error) {
        console.error('Lỗi tải thống kê:', error);
      }
    },

    formatTime(datetime) {
      if (!datetime) return '';
      const date = new Date(datetime);
      return date.toLocaleString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    },
  },
  watch: {
    activeTab() {
      this.currentPage = 1;
      this.loadHistory();
    },
  },
  mounted() {
    this.loadHistory();
  },
};
</script>

<style scoped>
/* Animation */
</style>
