<template>
  <view class="p-page">
    <view class="yy-card profile-card">
      <view class="avatar">{{ avatarChar }}</view>
      <text class="name">{{ userStore.userInfo?.real_name }}</text>
      <text class="role">{{ userStore.userInfo?.role_name }}</text>
    </view>

    <view class="yy-card">
      <view class="menu-item" @click="goChangePassword">
        <text>修改密码</text>
        <text class="arrow">></text>
      </view>
      <view class="menu-item" @click="logout">
        <text class="danger-text">退出登录</text>
        <text class="arrow">></text>
      </view>
    </view>
  </view>
</template>

<script setup>
import { computed } from 'vue'
import { useUserStore } from '@/store/user.js'

const userStore = useUserStore()
const avatarChar = computed(() => (userStore.userInfo?.real_name || 'A').charAt(0))

function goChangePassword() {
  uni.navigateTo({ url: '/pages/common/change-password' })
}

function logout() {
  userStore.logout()
  uni.reLaunch({ url: '/pages/login/login' })
}
</script>

<style scoped>
.profile-card { text-align: center; padding: 48rpx; }
.avatar {
  width: 120rpx; height: 120rpx; border-radius: 50%;
  background: var(--primary); color: #fff;
  font-size: 48rpx; font-weight: 600;
  line-height: 120rpx; margin: 0 auto 20rpx;
}
.name { display: block; font-size: 36rpx; font-weight: 600; }
.role { display: block; font-size: 26rpx; color: var(--text-secondary); margin-top: 8rpx; }
.menu-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 28rpx 0; border-bottom: 1px solid var(--border); font-size: 30rpx;
}
.arrow { color: var(--text-muted); }
.danger-text { color: var(--danger); }
</style>
