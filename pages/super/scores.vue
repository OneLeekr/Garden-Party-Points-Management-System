<template>
  <SuperLayout active="scores">
    <template #title>积分管理</template>

    <input class="yy-input mb-md" v-model="keyword" placeholder="搜索玩家姓名" @confirm="searchPlayers" />

    <view v-if="!players.length" class="yy-empty">暂无玩家数据</view>
    <view class="yy-card" v-for="p in players" :key="p.id">
      <view class="flex-between">
        <view>
          <text class="name">{{ p.real_name }}</text>
          <text v-if="p.group_name" class="sub"> {{ p.group_name }}</text>
        </view>
        <text class="score">{{ p.score }}分</text>
      </view>
      <view class="action-row mt-sm">
        <text class="action-btn" @click="adjust(p, 'add')">增加积分</text>
        <text class="action-btn danger" @click="adjust(p, 'subtract')">扣除积分</text>
      </view>
    </view>

    <AppModal :visible="showAdjust" @update:visible="showAdjust = $event">
      <text class="yy-title">{{ adjustDir === 'add' ? '增加' : '扣除' }}积分 - {{ currentPlayer?.real_name }}</text>
      <text class="yy-label mt-md">积分数量</text>
      <input class="yy-input" v-model="adjustScore" type="number" />
      <text class="yy-label mt-md">原因 (必填)</text>
      <input class="yy-input" v-model="adjustReason" placeholder="如：开幕式奖励" />
      <view class="flex-row gap-sm mt-md">
        <button class="yy-btn yy-btn-primary flex-1" @click="submitAdjust">确认</button>
        <button class="yy-btn yy-btn-outline flex-1" @click="showAdjust = false">取消</button>
      </view>
    </AppModal>
  </SuperLayout>
</template>

<script setup>
import { ref } from 'vue'
import { get, post } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import AppModal from '@/components/AppModal/AppModal.vue'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const players = ref([])
const keyword = ref('')
const showAdjust = ref(false)
const currentPlayer = ref(null)
const adjustDir = ref('add')
const adjustScore = ref('')
const adjustReason = ref('')

async function searchPlayers(silent = false) {
  try {
    const res = await get('/api/scores/players', { keyword: keyword.value, page_size: 50 })
    players.value = res.data.list
    if (currentPlayer.value) {
      const updated = players.value.find((p) => p.id === currentPlayer.value.id)
      if (updated) currentPlayer.value = updated
    }
  } catch (e) {
    if (!silent) uni.showToast({ title: e.message, icon: 'none' })
  }
}

useAutoRefresh(() => {
  if (!showAdjust.value) searchPlayers(true)
}, { intervalMs: 5000, silent: true })

function adjust(player, dir) {
  currentPlayer.value = player
  adjustDir.value = dir
  adjustScore.value = ''
  adjustReason.value = ''
  showAdjust.value = true
}

async function submitAdjust() {
  if (!adjustScore.value || !adjustReason.value) {
    uni.showToast({ title: '请填写完整', icon: 'none' })
    return
  }
  await post('/api/scores/adjust', {
    user_id: currentPlayer.value.id,
    score: parseInt(adjustScore.value),
    direction: adjustDir.value,
    reason: adjustReason.value,
    csrf_token: getCsrfToken()
  })
  showAdjust.value = false
  searchPlayers()
  uni.showToast({ title: '操作成功', icon: 'success' })
}
</script>

<style scoped>
.name { font-weight: 600; font-size: 30rpx; }
.sub { font-size: 24rpx; color: var(--text-secondary); }
.score { color: var(--primary); font-weight: 700; font-size: 32rpx; }
.action-row { display: flex; gap: 24rpx; }
.action-btn { color: var(--primary); font-size: 26rpx; }
.action-btn.danger { color: var(--danger); }
</style>
