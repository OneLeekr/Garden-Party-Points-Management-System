<template>
  <SuperLayout active="settings">
    <template #title>系统设置</template>
    <view class="yy-card">
      <text class="yy-label">系统名称</text>
      <input class="yy-input mt-sm" v-model="settings.site_name" placeholder="如：栗壳游园会积分管理系统" />
      <text class="yy-label mt-md">英文名称</text>
      <input class="yy-input mt-sm" v-model="settings.site_name_en" placeholder="如：YuYuan Fair Score System" />
      <text class="yy-label mt-md">二维码有效期(分钟)</text>
      <input class="yy-input mt-sm" v-model="settings.qr_expire_minutes" type="number" placeholder="1-60" />
      <button class="yy-btn yy-btn-primary mt-md" :loading="saving" @click="save">保存设置</button>
    </view>

    <view class="yy-card">
      <text class="yy-title">安全提示</text>
      <text class="yy-subtitle mt-sm">请在部署后修改 backend/config/app.php 中的 jwt_secret 和 qr_secret</text>
      <text class="yy-subtitle mt-sm">安装完成后请删除 install.php 文件</text>
    </view>
  </SuperLayout>
</template>

<script setup>
import { ref } from 'vue'
import { get, post, put } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import { useUserStore } from '@/store/user.js'
import { useSettingsStore } from '@/store/settings.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const userStore = useUserStore()
const settingsStore = useSettingsStore()
const settings = ref({ site_name: '', site_name_en: '', qr_expire_minutes: '5' })
const saving = ref(false)

async function loadSettings() {
  try {
    const res = await get('/api/settings')
    settings.value = {
      site_name: res.data.site_name || '',
      site_name_en: res.data.site_name_en || '',
      qr_expire_minutes: String(res.data.qr_expire_minutes ?? '5')
    }
    settingsStore.apply(res.data)
  } catch (e) {
    uni.showToast({ title: e.message || '加载失败', icon: 'none' })
  }
}

useAutoRefresh(loadSettings, { intervalMs: 10000, silent: true })

function isMissingApiError(e) {
  const msg = e?.message || ''
  return msg.includes('404') || msg.includes('接口不存在')
}

async function requestSaveSettings(payload) {
  const urls = ['/api/settings', '/api/settings/save']
  let lastError = null
  for (const url of urls) {
    try {
      return await post(url, payload)
    } catch (e) {
      lastError = e
      if (!isMissingApiError(e)) throw e
    }
  }
  try {
    return await put('/api/settings', payload)
  } catch (e) {
    throw lastError || e
  }
}

async function save() {
  if (!settings.value.site_name?.trim()) {
    uni.showToast({ title: '请填写系统名称', icon: 'none' })
    return
  }
  saving.value = true
  try {
    await userStore.refreshCsrf()
    const res = await requestSaveSettings({
      settings: {
        site_name: settings.value.site_name.trim(),
        site_name_en: settings.value.site_name_en?.trim() || '',
        qr_expire_minutes: String(settings.value.qr_expire_minutes || '5')
      },
      csrf_token: getCsrfToken()
    })
    settingsStore.apply(res.data || settings.value)
    await loadSettings()
    uni.showToast({ title: '保存成功', icon: 'success' })
  } catch (e) {
    uni.showToast({ title: e.message || '保存失败', icon: 'none' })
  } finally {
    saving.value = false
  }
}
</script>
