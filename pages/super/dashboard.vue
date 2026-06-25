<template>
  <SuperLayout active="dashboard">
    <template #title>仪表盘</template>

    <view class="dash-hero page-fade-in">
      <view class="stats-grid">
        <view class="stat-tile primary">
          <view class="tile-icon"><SvgIcon name="users" :size="20" color="#2563eb" /></view>
          <text class="tile-value">{{ stats.total_players ?? 0 }}</text>
          <text class="tile-label">总玩家数</text>
        </view>
        <view class="stat-tile success">
          <view class="tile-icon"><SvgIcon name="group" :size="20" color="#059669" /></view>
          <text class="tile-value">{{ stats.total_admins ?? 0 }}</text>
          <text class="tile-label">总管理员</text>
        </view>
        <view class="stat-tile warning">
          <view class="tile-icon"><SvgIcon name="stats" :size="20" color="#d97706" /></view>
          <text class="tile-value">{{ stats.today_checkin ?? 0 }}</text>
          <text class="tile-label">今日签到</text>
        </view>
        <view class="stat-tile info">
          <view class="tile-icon"><SvgIcon name="points" :size="20" color="#2563eb" /></view>
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

    <view class="panels-row">
      <view class="yy-card panel">
        <view class="panel-head">
          <SvgIcon name="project" :size="18" color="#2563eb" />
          <text class="yy-title">项目排行榜</text>
        </view>
        <view v-for="(item, i) in stats.project_rank" :key="i" class="rank-item">
          <view class="rank-badge" :class="'top-' + (i + 1)">{{ i + 1 }}</view>
          <text class="rank-name">{{ item.name }}</text>
          <text class="rank-score">{{ item.total_score }}分</text>
        </view>
        <view v-if="!stats.project_rank?.length" class="yy-empty">暂无数据</view>
      </view>

      <view class="yy-card panel">
        <view class="panel-head">
          <SvgIcon name="score" :size="18" color="#059669" />
          <text class="yy-title">玩家积分排行</text>
        </view>
        <view v-for="(item, i) in stats.player_rank" :key="i" class="rank-item">
          <view class="rank-badge" :class="'top-' + (i + 1)">{{ i + 1 }}</view>
          <view class="rank-info">
            <text class="rank-name">{{ item.real_name }}</text>
            <text v-if="item.group_name" class="rank-sub">{{ item.group_name }}</text>
          </view>
          <text class="rank-score">{{ item.score }}分</text>
        </view>
        <view v-if="!stats.player_rank?.length" class="yy-empty">暂无数据</view>
      </view>
    </view>
  </SuperLayout>
</template>

<script setup>
import { ref } from 'vue'
import { get } from '@/utils/request.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const stats = ref({
  total_players: 0, total_admins: 0, today_checkin: 0,
  today_gain: 0, today_consume: 0, project_rank: [], player_rank: []
})

async function loadStats() {
  try {
    const res = await get('/api/dashboard/stats')
    stats.value = res.data
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

useAutoRefresh(loadStats, { intervalMs: 5000, silent: true })
</script>

<style scoped>
.dash-hero { margin-bottom: 24rpx; }
.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20rpx;
}
@media (min-width: 768px) {
  .stats-grid { grid-template-columns: repeat(5, 1fr); }
}
.stat-tile {
  background: var(--bg-card);
  border-radius: 24rpx;
  padding: 28rpx;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
}
.tile-icon {
  width: 56rpx; height: 56rpx; border-radius: 16rpx;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16rpx;
}
.stat-tile.primary .tile-icon { background: rgba(37,99,235,0.1); }
.stat-tile.success .tile-icon { background: rgba(5,150,105,0.1); }
.stat-tile.warning .tile-icon { background: rgba(217,119,6,0.1); }
.stat-tile.info .tile-icon { background: rgba(37,99,235,0.1); }
.stat-tile.danger .tile-icon { background: rgba(220,38,38,0.1); }
.tile-value {
  display: block;
  font-size: 48rpx;
  font-weight: 800;
  line-height: 1.2;
}
.stat-tile.primary .tile-value, .stat-tile.info .tile-value { color: #2563eb; }
.stat-tile.success .tile-value { color: #059669; }
.stat-tile.warning .tile-value { color: #d97706; }
.stat-tile.danger .tile-value { color: #dc2626; }
.tile-label {
  display: block;
  font-size: 24rpx;
  color: var(--text-secondary);
  margin-top: 8rpx;
}
.panels-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24rpx;
}
@media (min-width: 768px) {
  .panels-row { grid-template-columns: 1fr 1fr; }
}
.panel { padding: 32rpx; }
.panel-head {
  display: flex;
  align-items: center;
  gap: 12rpx;
  margin-bottom: 24rpx;
  padding-bottom: 20rpx;
  border-bottom: 1px solid var(--border);
}
.rank-item {
  display: flex;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1px solid var(--border);
  gap: 16rpx;
}
.rank-item:last-child { border-bottom: none; }
.rank-badge {
  width: 48rpx; height: 48rpx; border-radius: 14rpx;
  background: var(--bg-page); color: var(--text-secondary);
  text-align: center; line-height: 48rpx; font-size: 24rpx; font-weight: 700;
  flex-shrink: 0;
}
.rank-badge.top-1 { background: #fef3c7; color: #d97706; }
.rank-badge.top-2 { background: #f1f5f9; color: #64748b; }
.rank-badge.top-3 { background: #ffedd5; color: #ea580c; }
.rank-info { flex: 1; min-width: 0; }
.rank-name { display: block; font-size: 28rpx; font-weight: 600; }
.rank-sub { display: block; font-size: 22rpx; color: var(--text-secondary); margin-top: 4rpx; }
.rank-score { color: var(--primary); font-weight: 700; font-size: 28rpx; flex-shrink: 0; }
</style>
