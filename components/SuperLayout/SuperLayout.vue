<script setup>
import { ref } from 'vue'
import { useUserStore } from '@/store/user.js'
import { useSettingsStore } from '@/store/settings.js'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const props = defineProps({
  active: { type: String, default: 'dashboard' }
})

const userStore = useUserStore()
const settingsStore = useSettingsStore()
useAutoRefresh(() => settingsStore.loadPublic(), { intervalMs: 15000, silent: true })
const showSidebar = ref(false)
const transitioning = ref(false)

const menus = [
  { key: 'dashboard', label: '仪表盘', icon: 'dashboard', path: '/pages/super/dashboard' },
  { key: 'users', label: '用户管理', icon: 'users', path: '/pages/super/users' },
  { key: 'groups', label: '分组管理', icon: 'group', path: '/pages/super/groups' },
  { key: 'projects', label: '项目管理', icon: 'project', path: '/pages/super/projects' },
  { key: 'scores', label: '积分管理', icon: 'points', path: '/pages/super/scores' },
  { key: 'stats', label: '数据统计', icon: 'stats', path: '/pages/super/stats' },
  { key: 'settings', label: '系统设置', icon: 'settings', path: '/pages/super/settings' }
]

function navigate(item) {
  if (item.key === props.active || transitioning.value) return
  transitioning.value = true
  showSidebar.value = false
  uni.redirectTo({
    url: item.path,
    animationType: 'slide-in-right',
    animationDuration: 280,
    complete: () => { transitioning.value = false }
  })
}

function logout() {
  userStore.logout()
  uni.reLaunch({ url: '/pages/login/login' })
}
</script>

<template>
  <view class="super-layout">
    <view class="sidebar" :class="{ open: showSidebar }">
      <view class="sidebar-brand">
        <view class="brand-icon">
          <SvgIcon name="logo" :size="22" color="#ffffff" />
        </view>
        <view>
          <text class="logo-text">YYFSS</text>
          <text class="logo-sub">{{ settingsStore.site_name }}</text>
        </view>
      </view>
      <view class="menu-list">
        <view
          v-for="item in menus"
          :key="item.key"
          class="menu-item"
          :class="{ active: active === item.key }"
          @click="navigate(item)"
        >
          <view class="menu-icon-wrap">
            <SvgIcon :name="item.icon" :size="20" :color="active === item.key ? '#2563eb' : '#64748b'" />
          </view>
          <text class="menu-label">{{ item.label }}</text>
        </view>
      </view>
      <view class="menu-item logout" @click="logout">
        <view class="menu-icon-wrap">
          <SvgIcon name="logout" :size="20" color="#64748b" />
        </view>
        <text class="menu-label">退出登录</text>
      </view>
    </view>

    <view class="main-area">
      <view class="top-bar">
        <view class="toggle-btn" @click="showSidebar = !showSidebar">
          <SvgIcon name="menu" :size="22" color="#334155" />
        </view>
        <text class="top-title"><slot name="title">管理后台</slot></text>
        <view class="user-chip">
          <text class="user-name">{{ userStore.userInfo?.real_name }}</text>
        </view>
      </view>
      <view class="content-area page-fade-in">
        <slot></slot>
      </view>
    </view>
    <view v-if="showSidebar" class="overlay" @click="showSidebar = false"></view>
  </view>
</template>

<style scoped>
.super-layout {
  display: flex;
  min-height: 100vh;
  background: var(--bg-page);
}
.sidebar {
  width: 440rpx;
  background: var(--bg-sidebar);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
  transform: translateX(-100%);
  transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: var(--shadow-lg);
}
.sidebar.open { transform: translateX(0); }
@media (min-width: 768px) {
  .sidebar {
    transform: translateX(0);
    position: sticky;
    top: 0;
    height: 100vh;
    box-shadow: none;
  }
  .toggle-btn { display: none; }
}
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 20rpx;
  padding: 40rpx 28rpx;
  border-bottom: 1px solid var(--border);
}
.brand-icon {
  width: 72rpx;
  height: 72rpx;
  border-radius: 20rpx;
  background: var(--primary);
  display: flex;
  align-items: center;
  justify-content: center;
}
.logo-text {
  display: block;
  font-size: 34rpx;
  font-weight: 800;
  color: var(--text-primary);
  letter-spacing: 0.04em;
}
.logo-sub {
  display: block;
  font-size: 22rpx;
  color: var(--text-secondary);
  margin-top: 4rpx;
}
.menu-list { flex: 1; padding: 16rpx 12rpx; overflow-y: auto; }
.menu-item {
  display: flex;
  align-items: center;
  gap: 16rpx;
  padding: 22rpx 20rpx;
  margin-bottom: 8rpx;
  border-radius: 16rpx;
  color: var(--text-secondary);
  transition: all 0.24s ease;
}
.menu-item:active { transform: scale(0.98); }
.menu-item.active {
  background: var(--primary-soft);
  color: var(--primary);
}
.menu-icon-wrap {
  width: 40rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}
.menu-label { font-size: 28rpx; font-weight: 500; }
.logout {
  margin-top: auto;
  border-top: 1px solid var(--border);
  margin: 0 12rpx 12rpx;
  border-radius: 16rpx;
}
.main-area { flex: 1; width: 100%; min-width: 0; }
.top-bar {
  display: flex;
  align-items: center;
  padding: 20rpx 32rpx;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(12px);
}
.toggle-btn {
  padding: 12rpx;
  margin-right: 12rpx;
  border-radius: 12rpx;
}
.top-title {
  flex: 1;
  font-size: 34rpx;
  font-weight: 700;
  color: var(--text-primary);
}
.user-chip {
  padding: 10rpx 20rpx;
  background: var(--bg-page);
  border-radius: 999rpx;
  border: 1px solid var(--border);
}
.user-name { font-size: 24rpx; color: var(--text-secondary); font-weight: 500; }
.content-area { padding: 32rpx; }
.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 99;
  animation: fadeIn 0.25s ease;
}
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@media (min-width: 768px) { .overlay { display: none; } }
</style>
