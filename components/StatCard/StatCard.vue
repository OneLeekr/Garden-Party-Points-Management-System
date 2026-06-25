<template>
  <view class="stat-card" :class="type">
    <view class="stat-top">
      <view class="stat-icon-wrap" :class="type">
        <SvgIcon :name="iconName" :size="18" :color="iconColor" />
      </view>
    </view>
    <view class="stat-value">{{ value }}</view>
    <view class="stat-label">{{ label }}</view>
  </view>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'

const props = defineProps({
  value: { type: [String, Number], default: 0 },
  label: { type: String, default: '' },
  type: { type: String, default: 'primary' }
})

const iconName = computed(() => {
  const map = { primary: 'users', success: 'group', warning: 'stats', danger: 'points' }
  return map[props.type] || 'dashboard'
})

const iconColor = computed(() => {
  const map = { primary: '#2563eb', success: '#059669', warning: '#d97706', danger: '#dc2626' }
  return map[props.type] || '#2563eb'
})
</script>

<style scoped>
.stat-card {
  background: var(--bg-card);
  border-radius: 24rpx;
  padding: 28rpx;
  border: 1px solid var(--border);
  flex: 1;
  min-width: 0;
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease;
}
.stat-card:active { transform: translateY(2rpx); }
.stat-icon-wrap {
  width: 56rpx; height: 56rpx; border-radius: 16rpx;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16rpx;
}
.stat-icon-wrap.primary { background: rgba(37,99,235,0.1); }
.stat-icon-wrap.success { background: rgba(5,150,105,0.1); }
.stat-icon-wrap.warning { background: rgba(217,119,6,0.1); }
.stat-icon-wrap.danger { background: rgba(220,38,38,0.1); }
.stat-value {
  font-size: 44rpx;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1.2;
}
.stat-card.success .stat-value { color: var(--success); }
.stat-card.warning .stat-value { color: var(--warning); }
.stat-card.danger .stat-value { color: var(--danger); }
.stat-card.primary .stat-value { color: var(--primary); }
.stat-label {
  font-size: 24rpx;
  color: var(--text-secondary);
  margin-top: 8rpx;
}
</style>
