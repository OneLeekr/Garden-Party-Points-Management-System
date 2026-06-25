<template>
  <view class="player-page page-fade-in">
    <view class="profile-hero">
      <view class="avatar">{{ avatarChar }}</view>
      <text class="nickname">{{ displayName }}</text>
      <text class="sub" v-if="profile.group_name">分组: {{ profile.group_name }}</text>
      <text class="sub" v-if="profile.phone">手机: {{ profile.phone }}</text>
    </view>

    <view class="yy-card section menu-card">
      <view class="menu-item" @click="goChangePassword">
        <view class="menu-left">
          <SvgIcon name="settings" :size="18" color="#64748b" />
          <text>修改密码</text>
        </view>
        <text class="arrow">></text>
      </view>
      <view class="menu-item" @click="logout">
        <view class="menu-left danger-text">
          <SvgIcon name="logout" :size="18" color="#dc2626" />
          <text>退出登录</text>
        </view>
        <text class="arrow">></text>
      </view>
    </view>
    <!-- #ifndef H5 -->
    <PlayerTabBar />
    <ScoreFlash :visible="flash.visible" :score="flash.score" :type="flash.type" />
    <!-- #endif -->
  </view>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/store/user.js'
import { usePlayerShell } from '@/composables/usePlayerBalance.js'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
// #ifndef H5
import PlayerTabBar from '@/components/PlayerTabBar/PlayerTabBar.vue'
import ScoreFlash from '@/components/ScoreFlash/ScoreFlash.vue'
// #endif

const { flash } = usePlayerShell()

const userStore = useUserStore()
const profile = ref({})

const displayName = computed(() =>
  userStore.userInfo?.real_name || userStore.userInfo?.nickname || profile.value.real_name || profile.value.nickname || ''
)
const avatarChar = computed(() => (displayName.value || '玩').charAt(0))

onMounted(async () => {
  profile.value = await userStore.fetchProfile()
})

function goChangePassword() {
  uni.navigateTo({ url: '/pages/common/change-password' })
}

function logout() {
  userStore.logout()
  uni.reLaunch({ url: '/pages/login/login' })
}
</script>

<style scoped>
.profile-hero {
  padding: 56rpx 32rpx 40rpx;
  text-align: center;
  background: var(--bg-card);
  border-bottom: 1px solid var(--border);
}
.avatar {
  width: 128rpx; height: 128rpx; border-radius: 32rpx;
  background: var(--primary); color: #fff;
  font-size: 52rpx; font-weight: 700;
  line-height: 128rpx; margin: 0 auto 20rpx;
  box-shadow: 0 8px 24px rgba(37,99,235,0.25);
}
.nickname { display: block; font-size: 40rpx; font-weight: 700; }
.sub { display: block; font-size: 24rpx; color: var(--text-secondary); margin-top: 8rpx; }
.section { margin: 24rpx 32rpx 0; }
.menu-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 28rpx 0; border-bottom: 1px solid var(--border); font-size: 30rpx;
}
.menu-item:last-child { border-bottom: none; }
.menu-left { display: flex; align-items: center; gap: 16rpx; }
.arrow { color: var(--text-muted); }
.danger-text { color: var(--danger); }
</style>
