<template>
  <view v-if="visible" class="player-tabbar">
    <view
      v-for="item in tabs"
      :key="item.key"
      class="tab-item"
      :class="{ active: active === item.key }"
      @click="go(item.key)"
    >
      <SvgIcon :name="item.icon" :size="22" :color="active === item.key ? '#2563eb' : '#94a3b8'" />
      <text class="tab-text">{{ item.text }}</text>
    </view>
  </view>
</template>

<script setup>
import { computed } from 'vue'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import playerTabStore from '@/store/playerTab.js'

const tabs = [
  { key: 'home', text: '首页', icon: 'home' },
  { key: 'scores', text: '积分', icon: 'score' },
  { key: 'qrcode', text: '二维码', icon: 'qr' },
  { key: 'profile', text: '我的', icon: 'user' }
]

const active = computed(() => playerTabStore.activeTab.value)
const visible = computed(() => playerTabStore.tabVisible.value)

function go(key) {
  if (key === active.value) return
  playerTabStore.setActiveTab(key)
  uni.switchTab({ url: playerTabStore.tabRoutes[key] })
}
</script>

<style>
.player-tabbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 99999;
  display: flex;
  align-items: center;
  background: #ffffff;
  border-top: 1px solid #e8edf5;
  box-shadow: 0 -4px 24px rgba(15, 23, 42, 0.08);
  min-height: 56px;
  padding-top: 6px;
  padding-bottom: max(env(safe-area-inset-bottom, 0px), 6px);
  box-sizing: border-box;
}
.tab-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 2px;
  padding: 4px 0 2px;
  -webkit-tap-highlight-color: transparent;
}
.tab-item:active { opacity: 0.75; }
.tab-item.active .tab-text { color: #2563eb; font-weight: 600; }
.tab-text {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.2;
}
</style>
