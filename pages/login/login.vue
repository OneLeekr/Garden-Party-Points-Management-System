<template>
  <view class="login-page">
    <view class="login-shell">
      <view class="brand-panel">
        <view class="brand-icon">
          <SvgIcon name="logo" :size="36" color="#2563eb" />
        </view>
        <text class="brand-title">{{ settingsStore.site_name }}</text>
        <text class="brand-desc">{{ settingsStore.site_name_en }}</text>
      </view>

      <view class="form-panel">
        <view v-if="hostTip" class="host-tip">{{ hostTip }}</view>
        <text class="form-title">欢迎登录</text>
        <text class="form-sub">请输入您的账号信息</text>

        <view class="field">
          <text class="field-label">用户名</text>
          <view class="input-wrap">
            <SvgIcon name="user" :size="18" color="#94a3b8" />
            <input class="field-input" v-model="username" placeholder="请输入用户名" />
          </view>
        </view>

        <view class="field">
          <text class="field-label">密码</text>
          <view class="input-wrap">
            <SvgIcon name="lock" :size="18" color="#94a3b8" />
            <input class="field-input" v-model="password" password placeholder="请输入密码" />
          </view>
        </view>

        <label class="remember-row">
          <checkbox :checked="remember" @click="remember = !remember" color="#2563eb" />
          <text>记住密码</text>
        </label>

        <button class="login-btn" :class="{ 'is-loading': loading }" :disabled="loading" @click="handleLogin">
          {{ loading ? '登录中…' : '登 录' }}
        </button>
      </view>
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user.js'
import { useSettingsStore } from '@/store/settings.js'
import { getToken } from '@/utils/auth.js'
import playerTabStore from '@/store/playerTab.js'
import { hidePlayerGlobalUI } from '@/utils/playerGlobalUi.js'
import { unlockAuth } from '@/utils/authGuard.js'
import SvgIcon from '@/components/SvgIcon/SvgIcon.vue'
import config from '@/config/index.js'
import { hostAccessMessage, probeApiAccess } from '@/utils/hostAccess.js'
import { useSubmitLock } from '@/composables/useSubmitLock.js'

const userStore = useUserStore()
const settingsStore = useSettingsStore()
const username = ref('')
const password = ref('')
const remember = ref(false)
const hostTip = ref('')
const { submitting: loading, runSubmit } = useSubmitLock()

function hidePlayerNav() {
  playerTabStore.hideTabBar()
  hidePlayerGlobalUI()
}

onMounted(async () => {
  hidePlayerNav()
  unlockAuth()
  settingsStore.loadPublic()
  const probe = await probeApiAccess(config.baseURL)
  if (!probe.ok) {
    hostTip.value = hostAccessMessage(probe.reason)
  }
  const saved = userStore.loadRemember()
  if (saved) {
    username.value = saved.username || ''
    password.value = saved.password || ''
    remember.value = true
  }
  if (getToken() && userStore.userInfo) {
    userStore.navigateByRole()
  }
})

onShow(() => {
  hidePlayerNav()
  unlockAuth()
})

async function handleLogin() {
  if (loading.value) return
  if (!username.value || !password.value) {
    uni.showToast({ title: '请输入用户名和密码', icon: 'none' })
    return
  }
  await runSubmit(async () => {
    try {
      await userStore.login(username.value, password.value, remember.value)
      userStore.navigateByRole()
    } catch (e) {
      uni.showToast({ title: e.message || '登录失败', icon: 'none' })
    }
  })
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  background: #f4f6fb;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40rpx;
}
.login-shell {
  width: 100%;
  max-width: 920rpx;
  background: #fff;
  border-radius: 32rpx;
  border: 1px solid #e8edf5;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
@media (min-width: 768px) {
  .login-shell { flex-direction: row; min-height: 640rpx; }
}
.brand-panel {
  padding: 56rpx 48rpx;
  background: #f8fafc;
  border-bottom: 1px solid #e8edf5;
}
@media (min-width: 768px) {
  .brand-panel {
    flex: 1;
    border-bottom: none;
    border-right: 1px solid #e8edf5;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}
.brand-icon {
  width: 96rpx;
  height: 96rpx;
  border-radius: 24rpx;
  background: #fff;
  border: 1px solid #e8edf5;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 28rpx;
}
.brand-title {
  display: block;
  font-size: 36rpx;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.4;
}
.brand-desc {
  display: block;
  margin-top: 12rpx;
  font-size: 24rpx;
  color: #64748b;
}
.form-panel {
  flex: 1;
  padding: 56rpx 48rpx;
}
.host-tip {
  margin-bottom: 24rpx;
  padding: 20rpx 24rpx;
  border-radius: 16rpx;
  background: #fff7ed;
  border: 1px solid #fed7aa;
  color: #9a3412;
  font-size: 24rpx;
  line-height: 1.6;
}
.form-title {
  display: block;
  font-size: 40rpx;
  font-weight: 700;
  color: #0f172a;
}
.form-sub {
  display: block;
  margin-top: 8rpx;
  margin-bottom: 40rpx;
  font-size: 26rpx;
  color: #64748b;
}
.field { margin-bottom: 28rpx; }
.field-label {
  display: block;
  font-size: 26rpx;
  color: #475569;
  margin-bottom: 12rpx;
  font-weight: 500;
}
.input-wrap {
  display: flex;
  align-items: center;
  gap: 16rpx;
  height: 92rpx;
  padding: 0 24rpx;
  background: #f8fafc;
  border: 2rpx solid #e8edf5;
  border-radius: 16rpx;
  transition: border-color 0.2s;
}
.input-wrap:focus-within {
  border-color: #2563eb;
  background: #fff;
}
.field-input {
  flex: 1;
  height: 100%;
  font-size: 28rpx;
  color: #0f172a;
}
.remember-row {
  display: flex;
  align-items: center;
  gap: 12rpx;
  font-size: 26rpx;
  color: #64748b;
  margin-bottom: 36rpx;
}
.login-btn {
  height: 92rpx;
  line-height: 92rpx;
  background: #2563eb;
  color: #fff;
  border-radius: 16rpx;
  font-size: 30rpx;
  font-weight: 600;
  border: none;
  box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);
}
.login-btn:active:not([disabled]) { opacity: 0.9; transform: scale(0.99); }
.login-btn[disabled],
.login-btn.is-loading {
  opacity: 0.65;
  pointer-events: none;
}
</style>
