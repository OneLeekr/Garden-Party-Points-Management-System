<template>
  <SuperLayout active="stats">
    <template #title>数据统计</template>

    <view class="stats-hero page-fade-in">
      <view class="stats-grid">
        <view class="stat-tile primary">
          <view class="tile-icon"><SvgIcon name="users" :size="20" color="#2563eb" /></view>
          <text class="tile-value">{{ stats.total_players ?? 0 }}</text>
          <text class="tile-label">总玩家数</text>
        </view>
        <view class="stat-tile success">
          <view class="tile-icon"><SvgIcon name="group" :size="20" color="#059669" /></view>
          <text class="tile-value">{{ stats.today_checkin ?? 0 }}</text>
          <text class="tile-label">今日签到</text>
        </view>
        <view class="stat-tile warning">
          <view class="tile-icon"><SvgIcon name="stats" :size="20" color="#d97706" /></view>
          <text class="tile-value">{{ stats.today_gain ?? 0 }}</text>
          <text class="tile-label">今日发放</text>
        </view>
        <view class="stat-tile danger">
          <view class="tile-icon"><SvgIcon name="points" :size="20" color="#dc2626" /></view>
          <text class="tile-value">{{ stats.today_consume ?? 0 }}</text>
          <text class="tile-label">今日核销</text>
        </view>
      </view>
    </view>

    <view class="yy-card records-card">
      <view class="records-head">
        <view>
          <text class="yy-title">积分流水</text>
          <text class="records-sub">最近 50 条 · 清除流水不影响玩家当前积分</text>
        </view>
        <view class="records-actions">
          <text class="action-link" @click="toggleSelectAll">{{ allSelected ? '取消全选' : '全选' }}</text>
          <text class="action-link danger" @click="clearSelected" v-if="selectedIds.length">删除选中({{ selectedIds.length }})</text>
          <text class="action-link danger" @click="clearAll">清空全部</text>
        </view>
      </view>
      <view v-if="!records.length" class="yy-empty">暂无流水记录</view>
      <view v-for="r in records" :key="r.id" class="record-item" @click="toggleSelect(r.id)">
        <view class="record-check" :class="{ checked: selectedIds.includes(r.id) }">
          <text v-if="selectedIds.includes(r.id)">✓</text>
        </view>
        <view class="record-body">
          <view class="record-main">
            <view class="record-left">
              <text class="record-user">{{ r.user_name }}</text>
              <text class="record-project">{{ r.project_name || '手动调整' }}</text>
            </view>
            <text :class="['record-score', isScoreMinus(r) ? 'minus' : 'plus']">
              {{ formatScoreChange(r) }}
            </text>
          </view>
          <view class="record-meta">
            <text class="type-tag" :class="getTypeClass(r)">{{ getRecordTypeLabel(r.type, r.reason) }}</text>
            <text>{{ formatBeijingDateTime(r.created_at) }}</text>
            <text v-if="r.admin_name"> · {{ r.admin_name }}</text>
          </view>
          <text v-if="r.reason" class="record-reason">{{ r.reason }}</text>
        </view>
      </view>
    </view>
  </SuperLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { get, post } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import { formatScoreChange, isScoreMinus, getRecordTypeLabel } from '@/utils/scoreRecord.js'
import { formatBeijingDateTime } from '@/utils/datetime.js'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const stats = ref({})
const records = ref([])
const selectedIds = ref([])

const allSelected = computed(() =>
  records.value.length > 0 && selectedIds.value.length === records.value.length
)

function getTypeClass(r) {
  if (r.type === 'adjust' && r.reason?.includes('[扣除]')) return 'consume'
  if (r.type === 'adjust' && r.reason?.includes('[增加]')) return 'gain'
  return r.type
}

async function loadData(preserveSelection = false) {
  const [statsRes, recordsRes] = await Promise.all([
    get('/api/dashboard/stats'),
    get('/api/scores/records', { page_size: 50 })
  ])
  stats.value = statsRes.data
  records.value = recordsRes.data.list
  if (!preserveSelection) selectedIds.value = []
  else {
    const idSet = new Set(records.value.map((r) => r.id))
    selectedIds.value = selectedIds.value.filter((id) => idSet.has(id))
  }
}

useAutoRefresh(() => loadData(true), { intervalMs: 5000, silent: true })

function toggleSelect(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx >= 0) selectedIds.value.splice(idx, 1)
  else selectedIds.value.push(id)
}

function toggleSelectAll() {
  if (allSelected.value) selectedIds.value = []
  else selectedIds.value = records.value.map((r) => r.id)
}

async function clearSelected() {
  if (!selectedIds.value.length) return
  uni.showModal({
    title: '确认删除',
    content: `确定清除选中的 ${selectedIds.value.length} 条流水？此操作不可恢复。`,
    success: async (res) => {
      if (!res.confirm) return
      try {
        const result = await post('/api/scores/records/clear', {
          ids: selectedIds.value,
          csrf_token: getCsrfToken()
        })
        uni.showToast({ title: `已清除 ${result.data.deleted} 条`, icon: 'success' })
        loadData()
      } catch (e) {
        uni.showToast({ title: e.message, icon: 'none' })
      }
    }
  })
}

function clearAll() {
  uni.showModal({
    title: '清空全部流水',
    content: '确定清除全部积分流水记录？此操作不可恢复，不会修改玩家当前积分余额。',
    success: async (res) => {
      if (!res.confirm) return
      try {
        const result = await post('/api/scores/records/clear', {
          all: true,
          csrf_token: getCsrfToken()
        })
        uni.showToast({ title: `已清除 ${result.data.deleted} 条`, icon: 'success' })
        loadData()
      } catch (e) {
        uni.showToast({ title: e.message, icon: 'none' })
      }
    }
  })
}
</script>

<style scoped>
.stats-hero { margin-bottom: 24rpx; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
}
@media (min-width: 768px) {
  .stats-grid { grid-template-columns: repeat(4, 1fr); }
}
.stat-tile {
  background: var(--bg-card);
  border-radius: 24rpx;
  padding: 28rpx;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  position: relative;
  overflow: hidden;
}
.stat-tile::after {
  content: '';
  position: absolute;
  top: 0; right: 0;
  width: 120rpx; height: 120rpx;
  border-radius: 50%;
  opacity: 0.08;
  transform: translate(30%, -30%);
}
.stat-tile.primary::after { background: #2563eb; }
.stat-tile.success::after { background: #059669; }
.stat-tile.warning::after { background: #d97706; }
.stat-tile.danger::after { background: #dc2626; }
.tile-icon {
  width: 56rpx; height: 56rpx; border-radius: 16rpx;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16rpx;
}
.stat-tile.primary .tile-icon { background: rgba(37,99,235,0.1); }
.stat-tile.success .tile-icon { background: rgba(5,150,105,0.1); }
.stat-tile.warning .tile-icon { background: rgba(217,119,6,0.1); }
.stat-tile.danger .tile-icon { background: rgba(220,38,38,0.1); }
.tile-value {
  display: block;
  font-size: 48rpx;
  font-weight: 800;
  line-height: 1.2;
}
.stat-tile.primary .tile-value { color: #2563eb; }
.stat-tile.success .tile-value { color: #059669; }
.stat-tile.warning .tile-value { color: #d97706; }
.stat-tile.danger .tile-value { color: #dc2626; }
.tile-label {
  display: block;
  font-size: 24rpx;
  color: var(--text-secondary);
  margin-top: 8rpx;
}
.records-card { padding: 32rpx; }
.records-head {
  margin-bottom: 24rpx;
  padding-bottom: 20rpx;
  border-bottom: 1px solid var(--border);
}
.records-sub { display: block; font-size: 22rpx; color: var(--text-muted); margin-top: 6rpx; }
.records-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 20rpx;
  margin-top: 16rpx;
}
.action-link { font-size: 26rpx; color: var(--primary); font-weight: 500; }
.action-link.danger { color: var(--danger); }
.record-item {
  display: flex;
  gap: 16rpx;
  padding: 24rpx 0;
  border-bottom: 1px solid var(--border);
  align-items: flex-start;
}
.record-item:last-child { border-bottom: none; }
.record-check {
  width: 40rpx; height: 40rpx; border-radius: 10rpx;
  border: 2rpx solid var(--border);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; margin-top: 4rpx;
  font-size: 22rpx; color: #fff;
}
.record-check.checked { background: var(--primary); border-color: var(--primary); }
.record-body { flex: 1; min-width: 0; }
.record-main { display: flex; justify-content: space-between; align-items: flex-start; gap: 16rpx; }
.record-user { display: block; font-size: 30rpx; font-weight: 600; }
.record-project { display: block; font-size: 24rpx; color: var(--text-secondary); margin-top: 4rpx; }
.record-score { font-size: 34rpx; font-weight: 800; flex-shrink: 0; }
.plus { color: var(--success); }
.minus { color: var(--danger); }
.record-meta {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-top: 12rpx;
  font-size: 22rpx;
  color: var(--text-muted);
  flex-wrap: wrap;
}
.type-tag {
  padding: 4rpx 12rpx;
  border-radius: 8rpx;
  font-size: 20rpx;
  font-weight: 600;
}
.type-tag.gain { background: rgba(5,150,105,0.12); color: #059669; }
.type-tag.consume { background: rgba(220,38,38,0.12); color: #dc2626; }
.type-tag.adjust { background: rgba(37,99,235,0.12); color: #2563eb; }
.record-reason {
  display: block;
  margin-top: 8rpx;
  font-size: 24rpx;
  color: var(--text-secondary);
}
</style>
