<template>
  <view class="menu-card" @click="$emit('click')">
    <view class="menu-icon" :style="{ background: color + '14' }">
      <SvgIcon :name="icon" :size="22" :color="color" />
    </view>
    <view class="menu-info">
      <text class="menu-title">{{ title }}</text>
      <text class="menu-desc">{{ desc }}</text>
    </view>
    <SvgIcon name="chevronRight" :size="16" color="#cbd5e1" />
  </view>
</template>

<script setup>
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'

defineProps({
  icon: { type: String, default: 'home' },
  title: { type: String, default: '' },
  desc: { type: String, default: '' },
  color: { type: String, default: '#2563eb' }
})
defineEmits(['click'])
</script>

<style scoped>
.menu-card {
  display: flex;
  align-items: center;
  background: var(--bg-card);
  border-radius: 24rpx;
  padding: 32rpx;
  margin-bottom: 24rpx;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.menu-card:active { transform: scale(0.98); }
.menu-icon {
  width: 88rpx;
  height: 88rpx;
  border-radius: 22rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 24rpx;
}
.menu-info { flex: 1; }
.menu-title {
  display: block;
  font-size: 32rpx;
  font-weight: 600;
  color: var(--text-primary);
}
.menu-desc {
  display: block;
  font-size: 24rpx;
  color: var(--text-secondary);
  margin-top: 6rpx;
}
</style>
