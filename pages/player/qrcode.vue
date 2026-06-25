<template>
  <view class="player-page page-fade-in qrcode-page">
  <view v-if="!isFullscreen" class="yy-card qr-card">
      <view class="qr-header">
        <SvgIcon name="qr" :size="22" color="#2563eb" />
        <text class="tip">向管理员出示此二维码</text>
      </view>
      <view class="qr-wrapper">
        <canvas canvas-id="qrcode" id="qrcode" class="qr-canvas" :style="{ width: qrSize + 'px', height: qrSize + 'px' }"></canvas>
      </view>
      <text class="expire-tip">有效期至: {{ expiresAt }}</text>
      <view class="btn-row">
        <button class="yy-btn yy-btn-outline flex-1" @click="refresh">刷新二维码</button>
        <button class="yy-btn yy-btn-primary flex-1" @click="enterFullscreen">全屏展示</button>
      </view>
    </view>

    <!-- 全屏层：右上角 × 关闭 -->
    <view v-if="isFullscreen" class="qr-fullscreen" @click.stop>
      <view class="fs-header">
        <text class="fs-title">我的二维码</text>
        <view class="fs-close-btn" @click="exitFullscreen">
          <SvgIcon name="close" :size="22" color="#64748b" />
        </view>
      </view>
      <view class="fs-body">
        <canvas canvas-id="qrcode-fs" id="qrcode-fs" class="qr-canvas fs-canvas" :style="{ width: fsSize + 'px', height: fsSize + 'px' }"></canvas>
      </view>
      <text class="fs-expire">有效期至: {{ expiresAt }}</text>
    </view>
    <!-- #ifndef H5 -->
    <PlayerTabBar />
    <ScoreFlash :visible="flash.visible" :score="flash.score" :type="flash.type" />
    <!-- #endif -->
  </view>
</template>

<script setup>
import { ref, onMounted, onUnmounted, getCurrentInstance, nextTick } from 'vue'
import { get } from '@/utils/request.js'
import UQRCode from 'uqrcodejs'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import { usePlayerShell } from '@/composables/usePlayerBalance.js'
import playerTabStore from '@/store/playerTab.js'
// #ifndef H5
import PlayerTabBar from '@/components/PlayerTabBar/PlayerTabBar.vue'
import ScoreFlash from '@/components/ScoreFlash/ScoreFlash.vue'
// #endif

const { flash } = usePlayerShell()

const qrToken = ref('')
const expiresAt = ref('')
const isFullscreen = ref(false)
const qrSize = ref(200)
const fsSize = ref(280)
let refreshTimer = null
const instance = getCurrentInstance()

onMounted(() => {
  refresh()
  refreshTimer = setInterval(refresh, 4 * 60 * 1000)
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
  // #ifdef H5
  if (typeof document !== 'undefined') document.body.style.overflow = ''
  // #endif
})

async function refresh() {
  try {
    const res = await get('/api/qr/generate')
    qrToken.value = res.data.token
    expiresAt.value = res.data.expires_at
    drawQR('qrcode', qrSize.value)
    if (isFullscreen.value) {
      await nextTick()
      drawQR('qrcode-fs', fsSize.value)
    }
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

function drawQR(canvasId, size) {
  if (!qrToken.value) return
  const qr = new UQRCode()
  qr.data = qrToken.value
  qr.size = size
  qr.margin = 10
  qr.backgroundColor = '#ffffff'
  qr.foregroundColor = '#000000'
  qr.make()

  const ctx = uni.createCanvasContext(canvasId, instance)
  qr.canvasContext = ctx
  qr.drawCanvas()
}

async function enterFullscreen() {
  isFullscreen.value = true
  playerTabStore.hideTabBar()
  // #ifdef H5
  if (typeof document !== 'undefined') document.body.style.overflow = 'hidden'
  // #endif
  await nextTick()
  setTimeout(() => drawQR('qrcode-fs', fsSize.value), 150)
}

function exitFullscreen() {
  isFullscreen.value = false
  playerTabStore.showTabBar()
  playerTabStore.setActiveTab('qrcode')
  // #ifdef H5
  if (typeof document !== 'undefined') document.body.style.overflow = ''
  // #endif
  setTimeout(() => drawQR('qrcode', qrSize.value), 100)
}
</script>

<style scoped>
.qrcode-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32rpx;
  padding-bottom: calc(var(--player-tabbar-height) + env(safe-area-inset-bottom, 0px) + 20px);
  box-sizing: border-box;
  min-height: 100vh;
  min-height: 100dvh;
}
.qr-card { width: 100%; text-align: center; }
.qr-header { display: flex; align-items: center; justify-content: center; gap: 12rpx; margin-bottom: 24rpx; }
.tip { font-size: 28rpx; color: var(--text-secondary); font-weight: 500; }
.qr-wrapper {
  display: flex; justify-content: center; margin: 24rpx 0;
  padding: 24rpx; background: #fff; border-radius: 16rpx;
}
.qr-canvas { margin: 0 auto; }
.expire-tip { display: block; font-size: 24rpx; color: var(--text-muted); margin: 16rpx 0; }
.btn-row { display: flex; gap: 16rpx; margin-top: 24rpx; }

.qr-fullscreen {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  width: 100vw; height: 100vh;
  z-index: 10000;
  background: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: env(safe-area-inset-top) 32rpx env(safe-area-inset-bottom);
  box-sizing: border-box;
}
.fs-header {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24rpx 0;
  flex-shrink: 0;
}
.fs-title { font-size: 34rpx; font-weight: 700; }
.fs-close-btn {
  width: 72rpx; height: 72rpx;
  display: flex; align-items: center; justify-content: center;
  background: #f1f5f9; border-radius: 50%;
}
.fs-body {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}
.fs-canvas { max-width: 90vw; max-height: 70vh; }
.fs-expire { font-size: 26rpx; color: var(--text-muted); margin: 24rpx 0 48rpx; flex-shrink: 0; }
</style>
