<template>
  <view class="player-page page-fade-in">
    <view class="hero">
      <view class="hero-bg"></view>
      <view class="hero-content">
        <text class="greeting">欢迎参加游园会</text>
        <text class="name">{{ userStore.userInfo?.real_name || userStore.userInfo?.nickname }}</text>
        <view class="score-box">
          <text class="score-label">当前积分</text>
          <text class="score-value">{{ balance }}</text>
        </view>
      </view>
    </view>

    <view class="quick-grid">
      <view class="quick-item" @click="switchTab('qrcode')">
        <view class="quick-icon blue">
          <SvgIcon name="qr" :size="24" color="#2563eb" />
        </view>
        <text class="quick-title">出示二维码</text>
        <text class="quick-desc">管理员扫码识别</text>
      </view>
      <view class="quick-item" @click="switchTab('scores')">
        <view class="quick-icon green">
          <SvgIcon name="score" :size="24" color="#059669" />
        </view>
        <text class="quick-title">积分明细</text>
        <text class="quick-desc">查看变动记录</text>
      </view>
    </view>
    <!-- #ifndef H5 -->
    <PlayerTabBar />
    <ScoreFlash :visible="flash.visible" :score="flash.score" :type="flash.type" />
    <!-- #endif -->
  </view>
</template>

<script setup>
import { useUserStore } from '@/store/user.js'
import { usePlayerShell } from '@/composables/usePlayerBalance.js'
import playerTabStore from '@/store/playerTab.js'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
// #ifndef H5
import PlayerTabBar from '@/components/PlayerTabBar/PlayerTabBar.vue'
import ScoreFlash from '@/components/ScoreFlash/ScoreFlash.vue'
// #endif

const userStore = useUserStore()
const { flash, balance } = usePlayerShell()

function switchTab(key) {
  playerTabStore.setActiveTab(key)
  uni.switchTab({ url: playerTabStore.tabRoutes[key] })
}
</script>

<style scoped>
.hero {
  position: relative;
  padding: 48rpx 32rpx 40rpx;
  overflow: hidden;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: #fff;
  border-bottom: 1px solid var(--border);
}
.hero-content { position: relative; z-index: 1; }
.greeting { display: block; font-size: 26rpx; color: var(--text-secondary); }
.name { display: block; font-size: 44rpx; font-weight: 700; margin-top: 8rpx; color: var(--text-primary); }
.score-box {
  margin-top: 32rpx;
  padding: 28rpx;
  background: var(--bg-page);
  border-radius: 24rpx;
  border: 1px solid var(--border);
}
.score-label { display: block; font-size: 24rpx; color: var(--text-secondary); }
.score-value { display: block; font-size: 72rpx; font-weight: 800; color: var(--primary); line-height: 1.2; margin-top: 4rpx; }
.quick-grid {
  display: flex;
  gap: 24rpx;
  padding: 32rpx;
}
.quick-item {
  flex: 1;
  background: var(--bg-card);
  border-radius: 24rpx;
  padding: 32rpx 24rpx;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  transition: transform 0.2s;
}
.quick-item:active { transform: scale(0.97); }
.quick-icon {
  width: 72rpx; height: 72rpx; border-radius: 20rpx;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 16rpx;
}
.quick-icon.blue { background: rgba(37,99,235,0.1); }
.quick-icon.green { background: rgba(5,150,105,0.1); }
.quick-title { display: block; font-size: 28rpx; font-weight: 600; color: var(--text-primary); }
.quick-desc { display: block; font-size: 22rpx; color: var(--text-muted); margin-top: 6rpx; }
</style>
