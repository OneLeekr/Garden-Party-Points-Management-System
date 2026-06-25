<template>
  <view class="player-page page-fade-in">
    <view class="header-card">
      <text class="label">积分余额</text>
      <text class="balance">{{ balance }}</text>
      <text class="unit">分</text>
    </view>

    <view class="section yy-card">
      <text class="section-title">近7日趋势</text>
      <view v-for="t in trend" :key="t.date" class="trend-item">
        <text class="date">{{ t.date }}</text>
        <text class="gain">+{{ t.gained || 0 }}</text>
        <text class="consume">-{{ t.consumed || 0 }}</text>
      </view>
      <view v-if="!trend.length" class="yy-empty">暂无趋势数据</view>
    </view>

    <view class="section yy-card">
      <text class="section-title">积分明细</text>
      <view v-for="r in records" :key="r.id" class="record-item">
        <view class="record-left">
          <view class="record-icon" :class="isScoreMinus(r) ? 'minus' : 'plus'">
            <SvgIcon :name="isScoreMinus(r) ? 'points' : 'score'" :size="16" :color="isScoreMinus(r) ? '#dc2626' : '#059669'" />
          </view>
          <view>
            <text class="project">{{ r.project_name || '系统调整' }}</text>
            <text class="reason">{{ r.reason }}</text>
          </view>
        </view>
        <view class="record-right">
          <text :class="isScoreMinus(r) ? 'val-minus' : 'val-plus'">
            {{ formatScoreChange(r) }}
          </text>
          <text class="meta">{{ formatBeijingDateTime(r.created_at, 'short') }}</text>
        </view>
      </view>
      <view v-if="!records.length" class="yy-empty">暂无记录</view>
    </view>
    <!-- #ifndef H5 -->
    <PlayerTabBar />
    <ScoreFlash :visible="flash.visible" :score="flash.score" :type="flash.type" />
    <!-- #endif -->
  </view>
</template>

<script setup>
import { ref } from 'vue'
import { get } from '@/utils/request.js'
import { usePlayerShell } from '@/composables/usePlayerBalance.js'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
// #ifndef H5
import PlayerTabBar from '@/components/PlayerTabBar/PlayerTabBar.vue'
import ScoreFlash from '@/components/ScoreFlash/ScoreFlash.vue'
// #endif
import { formatScoreChange, isScoreMinus } from '@/utils/scoreRecord.js'
import { formatBeijingDateTime } from '@/utils/datetime.js'

const trend = ref([])
const records = ref([])

async function loadData() {
  const [trendRes, recRes] = await Promise.all([
    get('/api/scores/trend'),
    get('/api/scores/records', { page_size: 30 })
  ])
  trend.value = trendRes.data
  records.value = recRes.data.list
}

const { balance, flash } = usePlayerShell({ onRefresh: loadData })
</script>

<style scoped>
.header-card {
  margin: 32rpx;
  padding: 40rpx;
  background: var(--bg-card);
  border-radius: 28rpx;
  border: 1px solid var(--border);
  text-align: center;
  box-shadow: var(--shadow-sm);
}
.label { display: block; font-size: 26rpx; color: var(--text-secondary); }
.balance { display: block; font-size: 80rpx; font-weight: 800; color: var(--primary); line-height: 1.1; }
.unit { font-size: 28rpx; color: var(--text-muted); }
.section { margin: 0 32rpx 24rpx; }
.section-title { display: block; font-size: 30rpx; font-weight: 700; margin-bottom: 20rpx; }
.trend-item { display: flex; padding: 16rpx 0; border-bottom: 1px solid var(--border); font-size: 26rpx; }
.date { flex: 1; color: var(--text-secondary); }
.gain { color: var(--success); margin-right: 24rpx; font-weight: 600; }
.consume { color: var(--danger); font-weight: 600; }
.record-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 24rpx 0; border-bottom: 1px solid var(--border);
}
.record-left { display: flex; align-items: center; gap: 16rpx; flex: 1; }
.record-icon {
  width: 56rpx; height: 56rpx; border-radius: 16rpx;
  display: flex; align-items: center; justify-content: center;
}
.record-icon.plus { background: rgba(5,150,105,0.1); }
.record-icon.minus { background: rgba(220,38,38,0.1); }
.project { display: block; font-weight: 600; font-size: 28rpx; }
.reason { display: block; font-size: 22rpx; color: var(--text-muted); margin-top: 4rpx; }
.record-right { text-align: right; }
.val-plus { color: var(--success); font-weight: 700; font-size: 32rpx; }
.val-minus { color: var(--danger); font-weight: 700; font-size: 32rpx; }
.meta { display: block; font-size: 20rpx; color: var(--text-muted); margin-top: 4rpx; }
</style>
