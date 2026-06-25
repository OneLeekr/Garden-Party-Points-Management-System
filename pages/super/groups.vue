<template>
  <SuperLayout active="groups">
    <template #title>分组管理</template>
    <button class="yy-btn yy-btn-primary mb-md" @click="openForm()">创建分组</button>

    <view class="yy-card" v-for="g in list" :key="g.id">
      <view class="flex-between">
        <text class="yy-title">{{ g.name }}</text>
        <text class="yy-tag yy-tag-primary">{{ g.user_count }}人</text>
      </view>
      <text class="yy-subtitle mt-sm">{{ g.description || '暂无描述' }}</text>
      <view class="action-row mt-sm">
        <text class="action-btn" @click="openForm(g)">编辑</text>
        <text class="action-btn danger" @click="remove(g)">删除</text>
      </view>
    </view>

    <AppModal :visible="showForm" @update:visible="showForm = $event">
      <text class="yy-title">{{ form.id ? '编辑分组' : '创建分组' }}</text>
      <text class="yy-label mt-md">分组名称</text>
      <input class="yy-input" v-model="form.name" placeholder="如：一年级" />
      <text class="yy-label mt-md">描述</text>
      <input class="yy-input" v-model="form.description" />
      <view class="flex-row gap-sm mt-md">
        <button class="yy-btn yy-btn-primary flex-1" @click="save">保存</button>
        <button class="yy-btn yy-btn-outline flex-1" @click="showForm = false">取消</button>
      </view>
    </AppModal>
  </SuperLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post, put, del } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import AppModal from '@/components/AppModal/AppModal.vue'

const list = ref([])
const showForm = ref(false)
const form = ref({ id: 0, name: '', description: '' })

onMounted(loadData)

async function loadData() {
  const res = await get('/api/groups')
  list.value = res.data
}

function openForm(g) {
  form.value = g ? { ...g } : { id: 0, name: '', description: '' }
  showForm.value = true
}

async function save() {
  const data = { ...form.value, csrf_token: getCsrfToken() }
  if (form.value.id) await put('/api/groups', data)
  else await post('/api/groups', data)
  showForm.value = false
  loadData()
}

function remove(g) {
  uni.showModal({
    title: '确认删除',
    content: `删除分组 ${g.name}？`,
    success: async (res) => {
      if (res.confirm) {
        await del('/api/groups', { id: g.id, csrf_token: getCsrfToken() })
        loadData()
      }
    }
  })
}
</script>

<style scoped>
.action-row { display: flex; gap: 24rpx; }
.action-btn { color: var(--primary); font-size: 26rpx; }
.action-btn.danger { color: var(--danger); }
</style>
