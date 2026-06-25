<template>
  <view class="p-page">
    <view class="yy-card">
      <button
        class="yy-btn yy-btn-primary"
        :class="{ 'is-loading': scanning || submitting }"
        :disabled="scanning || submitting"
        @click="scanOnce"
      >{{ scanning ? '扫码中…' : submitting ? '提交中…' : '扫描玩家二维码' }}</button>
      <text class="scan-tip">扫码后填写信息并确认登记，成功后自动进入下一次扫码</text>
    </view>

    <view v-if="player" class="yy-card">
      <text class="yy-title">玩家信息</text>
      <view class="info-row"><text>姓名</text><text>{{ player.real_name }}</text></view>
      <view v-if="player.group_name" class="info-row"><text>分组</text><text>{{ player.group_name }}</text></view>
      <view class="info-row"><text>当前积分</text><text class="score">{{ player.score }}</text></view>
    </view>

    <view v-if="player && !gainBlocked" class="yy-card">
      <text class="yy-label">活动项目</text>
      <picker :range="projects" range-key="name" @change="onProjectChange">
        <view class="yy-input picker-val">{{ currentProject?.name || '选择项目' }}</view>
      </picker>
      <text class="yy-label mt-md">获得积分</text>
      <input
        class="yy-input"
        :class="{ 'input-locked': scoreLocked }"
        v-model="score"
        type="number"
        :disabled="scoreLocked"
        placeholder="输入积分"
      />
      <text class="score-hint">{{ scoreHintText }}</text>
      <text class="yy-label mt-md">备注</text>
      <input class="yy-input" v-model="reason" placeholder="可选" />
      <button
        class="yy-btn yy-btn-primary mt-md"
        :class="{ 'is-loading': submitting }"
        :disabled="submitting"
        @click="submit"
      >{{ submitting ? '提交中…' : '确认登记' }}</button>
    </view>
  </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { post } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import { scanQRCode } from '@/utils/qrScan.js'
import { applyProjectScore, scoreHint, allowsRepeatPlay, isScoreLocked } from '@/utils/projectScore.js'
import { useSubmitLock } from '@/composables/useSubmitLock.js'
import { useProjects, syncProjectIndex } from '@/composables/useProjects.js'

const player = ref(null)
const qrToken = ref('')
const projectIndex = ref(0)
const score = ref('')
const reason = ref('')
const scanning = ref(false)
const gainBlocked = ref(false)
const { submitting, runSubmit } = useSubmitLock()

const currentProject = computed(() => projects.value[projectIndex.value])
const scoreHintText = computed(() => scoreHint(currentProject.value, 'gain'))
const scoreLocked = computed(() => isScoreLocked(currentProject.value, 'gain'))

const { projects } = useProjects({
  getSelectedId: () => projects.value[projectIndex.value]?.id,
  onUpdated: (list, selectedId) => {
    syncProjectIndex(list, projectIndex, selectedId)
    if (player.value && !gainBlocked.value) {
      fillScoreFromProject()
      const project = projects.value[projectIndex.value]
      if (project?.id && qrToken.value && !allowsRepeatPlay(project)) {
        checkRepeatPlay()
      }
    }
  }
})

function fillScoreFromProject() {
  score.value = applyProjectScore(currentProject.value, 'gain')
}

function resetScanState() {
  player.value = null
  qrToken.value = ''
  score.value = ''
  reason.value = ''
  gainBlocked.value = false
}

function showRepeatBlockedModal(message) {
  gainBlocked.value = true
  uni.showModal({
    title: '不可重复游玩',
    content: message,
    showCancel: false,
    success: () => {
      resetScanState()
      setTimeout(() => scanOnce(), 400)
    }
  })
}

function handleGainEligibility(data) {
  if (data.gain_allowed === false) {
    const name = data.real_name || '该玩家'
    const msg = data.gain_block_message
      || `${name} 已参与过「${data.project_name || currentProject.value?.name || '该活动'}」，不可重复登记积分`
    showRepeatBlockedModal(msg)
    return false
  }
  return true
}

async function verifyPlayer(token, projectId) {
  const payload = { token }
  if (projectId) payload.project_id = projectId
  const res = await post('/api/qr/verify', payload)
  return res.data
}

async function checkRepeatPlay() {
  gainBlocked.value = false
  const project = currentProject.value
  if (!qrToken.value || !project?.id) return
  if (allowsRepeatPlay(project)) return

  try {
    const data = await verifyPlayer(qrToken.value, project.id)
    player.value = { ...player.value, ...data }
    handleGainEligibility(data)
  } catch (e) {
    // 旧版后端不支持重复校验，跳过预检，登记时由 gain 接口兜底
    if (e.message && (e.message.includes('404') || e.message.includes('接口不存在'))) return
    uni.showToast({ title: e.message || '校验失败', icon: 'none' })
  }
}

function onProjectChange(e) {
  projectIndex.value = Number(e.detail.value)
  fillScoreFromProject()
  checkRepeatPlay()
}

async function scanOnce() {
  if (scanning.value) return
  scanning.value = true
  try {
    const result = await scanQRCode()
    qrToken.value = result
    const project = currentProject.value
    const projectId = project?.id && !allowsRepeatPlay(project) ? project.id : 0
    const data = await verifyPlayer(result, projectId)
    player.value = data
    fillScoreFromProject()
    if (projectId > 0 && !handleGainEligibility(data)) return
    if (projectId === 0 && project?.id && !allowsRepeatPlay(project)) {
      await checkRepeatPlay()
    }
  } catch (e) {
    if (e.message !== '扫码取消') {
      uni.showToast({ title: e.message || '扫码失败', icon: 'none' })
    }
  } finally {
    scanning.value = false
  }
}

async function submit() {
  if (gainBlocked.value || submitting.value) return
  if (!score.value) {
    uni.showToast({ title: '请输入积分', icon: 'none' })
    return
  }
  const project = currentProject.value
  const points = parseInt(score.value)
  await runSubmit(async () => {
    try {
      await post('/api/scores/gain', {
        qr_token: qrToken.value,
        project_id: project?.id,
        score: points,
        reason: reason.value || `${project?.name || ''}活动积分`,
        csrf_token: getCsrfToken()
      })
      uni.showToast({ title: `已为玩家登记 +${points} 积分`, icon: 'success' })
      resetScanState()
      setTimeout(() => scanOnce(), 400)
    } catch (e) {
      if (e.message && e.message.includes('不可重复')) {
        showRepeatBlockedModal(e.message)
        return
      }
      uni.showToast({ title: e.message, icon: 'none' })
    }
  })
}
</script>

<style scoped>
.scan-tip {
  display: block;
  margin-top: 16rpx;
  font-size: 24rpx;
  color: var(--text-muted);
  text-align: center;
}
.score-hint {
  display: block;
  margin-top: 8rpx;
  font-size: 22rpx;
  color: var(--text-muted);
}
.info-row {
  display: flex; justify-content: space-between;
  padding: 16rpx 0; border-bottom: 1px solid var(--border);
  font-size: 28rpx;
}
.score { color: var(--primary); font-weight: 700; }
.picker-val { line-height: 88rpx; }
.input-locked {
  background: var(--bg-page, #f1f5f9);
  color: var(--text-secondary);
}
</style>
