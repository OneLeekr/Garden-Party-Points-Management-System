<template>
  <SuperLayout active="users">
    <template #title>用户管理</template>

    <view class="toolbar flex-between mb-md">
      <input class="yy-input search-input" v-model="keyword" placeholder="搜索姓名/用户名/手机" @confirm="loadData" />
      <view class="toolbar-actions">
        <button class="yy-btn yy-btn-outline btn-sm" @click="downloadTemplate">下载模板</button>
        <button class="yy-btn yy-btn-outline btn-sm" @click="exportUsers">导出</button>
        <button class="yy-btn yy-btn-outline btn-sm" @click="importUsers">导入</button>
        <button class="yy-btn yy-btn-primary btn-sm" @click="openForm()">新增用户</button>
      </view>
    </view>

    <view class="yy-card user-card" v-for="user in list" :key="user.id">
      <view class="flex-between">
        <view class="user-head">
          <view class="avatar">{{ (user.real_name || user.username).charAt(0) }}</view>
          <view>
            <text class="user-name">{{ user.real_name || user.username }}</text>
            <view class="tags">
              <text class="yy-tag yy-tag-primary">{{ user.role_name }}</text>
              <text v-if="user.status === 0" class="yy-tag yy-tag-danger">已封禁</text>
            </view>
          </view>
        </view>
        <text class="user-score">{{ user.score }}分</text>
      </view>
      <view class="user-meta mt-sm">
        <text>用户名: {{ user.username }}</text>
        <text v-if="user.phone"> | 手机: {{ user.phone }}</text>
        <text v-if="user.group_name"> | 分组: {{ user.group_name }}</text>
      </view>
      <view class="login-meta mt-sm">
        <text class="login-label">最近登录:</text>
        <text>{{ formatLoginTime(user.last_login_at) }}</text>
        <text v-if="user.last_login_ip"> | IP: {{ user.last_login_ip }}</text>
        <text v-if="user.last_login_ua"> | 设备: {{ deviceLabel(user.last_login_ua) }}</text>
      </view>
      <view class="action-row mt-sm">
        <text class="action-btn" @click="openForm(user)">编辑</text>
        <text class="action-btn" @click="openReset(user)">重置密码</text>
        <text class="action-btn" @click="openLoginLogs(user)">登录记录</text>
        <text class="action-btn" @click="toggleBan(user)">{{ user.status ? '封禁' : '解封' }}</text>
        <text class="action-btn danger" @click="deleteUser(user)">删除</text>
      </view>
    </view>
    <view v-if="!list.length" class="yy-empty">暂无用户</view>

    <AppModal :visible="showForm" @update:visible="showForm = $event">
      <text class="yy-title">{{ form.id ? '编辑用户' : '新增用户' }}</text>
      <text class="yy-label mt-md">用户名</text>
      <input class="yy-input" v-model="form.username" :disabled="!!form.id" placeholder="登录用户名" />
      <text class="yy-label mt-md">姓名</text>
      <input class="yy-input" v-model="form.real_name" placeholder="真实姓名" />
      <text v-if="!form.id" class="yy-label mt-md">登录密码</text>
      <input v-if="!form.id" class="yy-input" v-model="form.password" password placeholder="请设置密码" />
      <text class="yy-label mt-md">手机号</text>
      <input class="yy-input" v-model="form.phone" placeholder="选填" />
      <text class="yy-label mt-md">分组</text>
      <FormSelect
        v-model="groupIndex"
        :options="groups"
        label-key="name"
        placeholder="选择分组"
        @change="onGroupSelect"
      />
      <text class="yy-label mt-md">角色</text>
      <FormSelect
        v-model="roleIndex"
        :options="roles"
        label-key="name"
        placeholder="选择角色"
        @change="onRoleSelect"
      />
      <view class="flex-row gap-sm mt-md">
        <button class="yy-btn yy-btn-primary flex-1" @click="saveUser">保存</button>
        <button class="yy-btn yy-btn-outline flex-1" @click="showForm = false">取消</button>
      </view>
    </AppModal>

    <AppModal :visible="showReset" @update:visible="showReset = $event">
      <text class="yy-title">重置密码</text>
      <text class="yy-subtitle mt-sm">用户: {{ resetUser?.real_name || resetUser?.username }}</text>
      <text class="yy-label mt-md">新密码（留空则默认 Re123456）</text>
      <input class="yy-input" v-model="resetPassword" password placeholder="Re123456" />
      <view class="flex-row gap-sm mt-md">
        <button class="yy-btn yy-btn-primary flex-1" @click="confirmReset">确认重置</button>
        <button class="yy-btn yy-btn-outline flex-1" @click="showReset = false">取消</button>
      </view>
    </AppModal>

    <AppModal :visible="showLoginLogs" @update:visible="showLoginLogs = $event">
      <text class="yy-title">登录记录</text>
      <text class="yy-subtitle mt-sm">{{ logUser?.real_name || logUser?.username }}</text>
      <view class="log-list mt-md">
        <view v-for="item in loginLogs" :key="item.id" class="log-item">
          <view class="log-row">
            <text class="log-time">{{ formatBeijingDateTime(item.created_at) }}</text>
            <text class="log-status" :class="item.status ? 'ok' : 'fail'">{{ item.status ? '成功' : '失败' }}</text>
          </view>
          <text class="log-detail">IP: {{ item.ip || '-' }} | 设备: {{ deviceLabel(item.user_agent) }}</text>
        </view>
        <view v-if="!loginLogs.length" class="yy-empty">暂无登录记录</view>
      </view>
      <button class="yy-btn yy-btn-outline mt-md" @click="showLoginLogs = false">关闭</button>
    </AppModal>
  </SuperLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post, put, upload } from '@/utils/request.js'
import { getCsrfToken, getToken } from '@/utils/auth.js'
import config from '@/config/index.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import AppModal from '@/components/AppModal/AppModal.vue'
import FormSelect from '@/components/FormSelect/FormSelect.vue'
import { parseDeviceLabel } from '@/utils/device.js'
import { formatBeijingDateTime } from '@/utils/datetime.js'

const list = ref([])
const keyword = ref('')
const showForm = ref(false)
const showReset = ref(false)
const showLoginLogs = ref(false)
const resetUser = ref(null)
const logUser = ref(null)
const loginLogs = ref([])
const resetPassword = ref('')
const roles = [
  { id: 1, name: '超级管理员', slug: 'super_admin' },
  { id: 2, name: '普通管理员', slug: 'admin' },
  { id: 3, name: '玩家', slug: 'player' }
]
const roleIndex = ref(2)
const groupIndex = ref(0)
const groups = ref([{ id: null, name: '无分组' }])
const form = ref({ id: 0, username: '', real_name: '', phone: '', role_id: 3, group_id: null, password: '' })

onMounted(async () => {
  await loadGroups()
  loadData()
})

async function loadGroups() {
  try {
    const res = await get('/api/groups')
    groups.value = [{ id: null, name: '无分组' }, ...res.data]
  } catch (_) {
    groups.value = [{ id: null, name: '无分组' }]
  }
}

async function loadData() {
  try {
    const res = await get('/api/users', { keyword: keyword.value, page_size: 50 })
    list.value = res.data.list
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

function openForm(user) {
  if (user) {
    form.value = {
      id: user.id,
      username: user.username,
      real_name: user.real_name,
      phone: user.phone || '',
      role_id: user.role_id,
      group_id: user.group_id || null,
      password: ''
    }
    roleIndex.value = roles.findIndex(r => r.id === user.role_id)
    groupIndex.value = groups.value.findIndex(g => g.id === user.group_id)
    if (groupIndex.value < 0) groupIndex.value = 0
  } else {
    form.value = { id: 0, username: '', real_name: '', phone: '', role_id: 3, group_id: null, password: '' }
    roleIndex.value = 2
    groupIndex.value = 0
  }
  showForm.value = true
}

function onGroupSelect({ item }) {
  form.value.group_id = item?.id ?? null
}

function onRoleSelect({ index }) {
  roleIndex.value = index
  form.value.role_id = roles[index].id
}

async function saveUser() {
  if (!form.value.username || !form.value.real_name) {
    uni.showToast({ title: '请填写用户名和姓名', icon: 'none' })
    return
  }
  if (!form.value.id && !form.value.password) {
    uni.showToast({ title: '请设置登录密码', icon: 'none' })
    return
  }
  try {
    const data = { ...form.value, csrf_token: getCsrfToken() }
    if (form.value.id) await put('/api/users', data)
    else await post('/api/users', data)
    showForm.value = false
    loadData()
    uni.showToast({ title: '保存成功', icon: 'success' })
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

async function deleteUser(user) {
  uni.showModal({
    title: '确认删除',
    content: `确定删除 ${user.real_name || user.username}？`,
    success: (res) => {
      if (res.confirm) confirmDeleteUser(user)
    }
  })
}

async function confirmDeleteUser(user) {
  try {
    await post('/api/users/delete', { id: user.id, csrf_token: getCsrfToken() })
    uni.showToast({ title: '删除成功', icon: 'success' })
    loadData()
  } catch (e) {
    uni.showToast({ title: e.message || '删除失败', icon: 'none', duration: 3000 })
  }
}

function openReset(user) {
  resetUser.value = user
  resetPassword.value = ''
  showReset.value = true
}

async function confirmReset() {
  try {
    const res = await post('/api/auth/reset-password', {
      user_id: resetUser.value.id,
      new_password: resetPassword.value,
      csrf_token: getCsrfToken()
    })
    showReset.value = false
    uni.showToast({ title: `密码已重置为 ${res.data.password}`, icon: 'none', duration: 3000 })
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

async function toggleBan(user) {
  await post('/api/users/ban', { user_id: user.id, status: user.status ? 0 : 1, csrf_token: getCsrfToken() })
  loadData()
}

function deviceLabel(ua) {
  return parseDeviceLabel(ua)
}

function formatLoginTime(value) {
  if (!value) return '从未登录'
  return formatBeijingDateTime(value)
}

async function openLoginLogs(user) {
  logUser.value = user
  showLoginLogs.value = true
  loginLogs.value = []
  try {
    const res = await get('/api/users/login-logs', { user_id: user.id, page_size: 20 })
    loginLogs.value = res.data.list || []
  } catch (e) {
    uni.showToast({ title: e.message, icon: 'none' })
  }
}

const USER_IMPORT_TEMPLATE_CSV = '\uFEFF' + [
  '姓名,手机号,分组,初始积分',
  '张三,13800000001,一年级,0',
  '李四,13800000002,二年级,10',
  '王老师,13800000003,教师组,0'
].join('\n') + '\n'

function downloadTemplate() {
  // #ifdef H5
  const blob = new Blob([USER_IMPORT_TEMPLATE_CSV], { type: 'text/csv;charset=utf-8;' })
  const objectUrl = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = objectUrl
  a.download = 'users_import_template.csv'
  a.click()
  URL.revokeObjectURL(objectUrl)
  // #endif
  // #ifndef H5
  const url = '/static/templates/users_import_template.csv'
  uni.downloadFile({
    url,
    success: (res) => {
      if (res.statusCode === 200) {
        uni.openDocument({ filePath: res.tempFilePath, showMenu: true })
      }
    },
    fail: () => uni.showToast({ title: '下载失败', icon: 'none' })
  })
  // #endif
}

function exportUsers() {
  const token = getToken()
  const url = `${config.baseURL || ''}/api/users/export?access_token=${encodeURIComponent(token)}`
  // #ifdef H5
  window.open(url, '_blank')
  // #endif
  // #ifndef H5
  uni.downloadFile({
    url,
    success: (res) => {
      if (res.statusCode === 200) {
        uni.saveFile({ tempFilePath: res.tempFilePath })
        uni.showToast({ title: '导出成功', icon: 'success' })
      }
    },
    fail: () => uni.showToast({ title: '导出失败', icon: 'none' })
  })
  // #endif
}

function importUsers() {
  // #ifdef H5
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = '.csv,.xlsx,.xls'
  input.onchange = async () => {
    const file = input.files?.[0]
    if (!file) return
    try {
      const formData = new FormData()
      formData.append('file', file)
      formData.append('csrf_token', getCsrfToken())
      const token = getToken()
      const res = await fetch(`${config.baseURL || ''}/api/users/import?access_token=${encodeURIComponent(token)}`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      const data = await res.json()
      if (data.code === 200) {
        uni.showToast({ title: `导入成功 ${data.data?.success || 0} 人`, icon: 'success' })
        loadData()
      } else {
        uni.showToast({ title: data.message || '导入失败', icon: 'none' })
      }
    } catch (e) {
      uni.showToast({ title: '导入失败', icon: 'none' })
    }
  }
  input.click()
  return
  // #endif
  uni.chooseFile({
    count: 1,
    extension: ['.csv', '.xlsx', '.xls'],
    success: async (res) => {
      try {
        const path = res.tempFilePaths?.[0] || res.tempFiles?.[0]?.path
        if (!path) return
        await upload('/api/users/import', path)
        uni.showToast({ title: '导入成功', icon: 'success' })
        loadData()
      } catch (e) {
        uni.showToast({ title: e.message || '导入失败', icon: 'none' })
      }
    }
  })
}
</script>

<style scoped>
.toolbar { flex-wrap: wrap; gap: 16rpx; }
.toolbar .search-input { min-width: 200rpx; }
.toolbar-actions { display: flex; gap: 12rpx; flex-shrink: 0; }
.btn-sm { height: 64rpx; font-size: 26rpx; padding: 0 28rpx; width: auto; display: inline-flex; }
.user-card { transition: transform 0.2s; }
.user-head { display: flex; align-items: center; gap: 20rpx; }
.avatar {
  width: 72rpx; height: 72rpx; border-radius: 20rpx;
  background: var(--primary-soft); color: var(--primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 30rpx; font-weight: 700;
}
.user-name { display: block; font-size: 30rpx; font-weight: 600; }
.tags { display: flex; gap: 8rpx; margin-top: 8rpx; }
.user-score { color: var(--primary); font-weight: 700; font-size: 34rpx; }
.user-meta { font-size: 24rpx; color: var(--text-secondary); }
.login-meta { font-size: 22rpx; color: var(--text-muted); line-height: 1.6; }
.login-label { color: var(--text-secondary); margin-right: 8rpx; }
.log-list { max-height: 50vh; overflow-y: auto; }
.log-item {
  padding: 20rpx 0;
  border-bottom: 1px solid var(--border);
}
.log-item:last-child { border-bottom: none; }
.log-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8rpx; }
.log-time { font-size: 26rpx; font-weight: 600; color: var(--text-primary); }
.log-status { font-size: 22rpx; padding: 4rpx 12rpx; border-radius: 8rpx; }
.log-status.ok { background: rgba(5,150,105,0.1); color: #059669; }
.log-status.fail { background: rgba(220,38,38,0.1); color: #dc2626; }
.log-detail { display: block; font-size: 22rpx; color: var(--text-secondary); }
.action-row { display: flex; gap: 28rpx; flex-wrap: wrap; }
.action-btn { font-size: 26rpx; color: var(--primary); font-weight: 500; }
.action-btn.danger { color: var(--danger); }
.picker-val { line-height: 88rpx; }
</style>
