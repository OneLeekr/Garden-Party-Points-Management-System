<template>
  <view class="p-page">
    <view class="yy-card">
      <text class="yy-title">修改密码</text>
      <text class="yy-subtitle mt-sm">首次登录或密码重置后需修改密码</text>

      <text class="yy-label mt-md">原密码</text>
      <input class="yy-input" v-model="oldPassword" password placeholder="请输入原密码" />

      <text class="yy-label mt-md">新密码</text>
      <input class="yy-input" v-model="newPassword" password placeholder="至少8位，含大小写字母和数字" />

      <text class="yy-label mt-md">确认新密码</text>
      <input class="yy-input" v-model="confirmPassword" password placeholder="再次输入新密码" />

      <button class="yy-btn yy-btn-primary mt-md" :loading="loading" @click="submit">确认修改</button>
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { post } from '@/utils/request.js'
import { getToken, getCsrfToken } from '@/utils/auth.js'
import { useUserStore } from '@/store/user.js'

const userStore = useUserStore()
const oldPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const loading = ref(false)

onMounted(async () => {
  if (!getToken()) {
    uni.showToast({ title: '请先登录', icon: 'none' })
    setTimeout(() => uni.reLaunch({ url: '/pages/login/login' }), 800)
    return
  }
  try {
    await userStore.refreshCsrf()
  } catch (e) {
    // 忽略，提交时使用 storage 中的 csrf
  }
})

async function submit() {
  if (!getToken()) {
    uni.showToast({ title: '登录已失效，请重新登录', icon: 'none' })
    setTimeout(() => uni.reLaunch({ url: '/pages/login/login' }), 800)
    return
  }
  if (!oldPassword.value || !newPassword.value) {
    uni.showToast({ title: '请填写完整', icon: 'none' })
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    uni.showToast({ title: '两次密码不一致', icon: 'none' })
    return
  }
  loading.value = true
  try {
    await post('/api/auth/change-password', {
      old_password: oldPassword.value,
      new_password: newPassword.value,
      csrf_token: getCsrfToken() || userStore.csrfToken
    })
    if (userStore.userInfo) {
      userStore.userInfo.must_change_password = 0
    }
    uni.showToast({ title: '修改成功', icon: 'success' })
    setTimeout(() => userStore.navigateByRole(), 1000)
  } catch (e) {
    uni.showToast({ title: e.message || '修改失败', icon: 'none' })
  } finally {
    loading.value = false
  }
}
</script>
