<template>
  <view class="p-page">
    <view class="yy-card">
      <button
        class="yy-btn yy-btn-primary"
        :class="{ 'is-loading': scanning || submitting }"
        :disabled="scanning || submitting"
        @click="scanOnce"
      >{{ scanning ? '扫码中…' : submitting ? '提交中…' : '扫描玩家二维码' }}</button>
      <text class="scan-tip">扫码后填写信息并确认核销，成功后自动进入下一次扫码</text>
    </view>

    <view v-if="player" class="yy-card">
      <text class="yy-title">玩家信息</text>
      <view class="info-row"><text>姓名</text><text>{{ player.real_name }}</text></view>
      <view v-if="player.group_name" class="info-row"><text>分组</text><text>{{ player.group_name }}</text></view>
      <view class="info-row"><text>当前积分</text><text class="score">{{ player.score }}</text></view>
    </view>

    <view v-if="player" class="yy-card">
      <text class="yy-label">核销项目</text>
      <picker :range="projects" range-key="name" @change="onProjectChange">
        <view class="yy-input picker-val">{{ currentProject?.name || '选择项目' }}</view>
      </picker>
      <text class="yy-label mt-md">扣除积分</text>
      <input
        class="yy-input"
        :class="{ 'input-locked': scoreLocked }"
        v-model="score"
        type="number"
        :disabled="scoreLocked"
        placeholder="输入积分"
      />
      <text class="score-hint">{{ scoreHintText }}</text>

      <text class="yy-label mt-md">核销原因 (必填)</text>
      <FormSelect
        v-if="showPresetReason"
        v-model="reasonIndex"
        :options="reasonSelectOptions"
        label-key="label"
        placeholder="选择核销原因"
        @change="onReasonSelect"
      />
      <input
        v-if="showCustomReason"
        class="yy-input mt-sm"
        v-model="customReason"
        :placeholder="showPresetReason ? '或自定义输入原因' : '如：礼品兑换'"
      />
      <text class="score-hint">{{ reasonHintText }}</text>

      <button
        class="yy-btn yy-btn-danger mt-md"
        :class="{ 'is-loading': submitting }"
        :disabled="submitting"
        @click="submit"
      >{{ submitting ? '提交中…' : '确认核销' }}</button>
    </view>
  </view>
</template>

<script setup>
import { ref, computed } from 'vue'
import { post } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import { scanQRCode } from '@/utils/qrScan.js'
import FormSelect from '@/components/FormSelect/FormSelect.vue'
import {
  applyProjectScore,
  scoreHint,
  isScoreLocked,
  getConsumeReasonOptions,
  showPresetConsumeReason,
  showCustomConsumeReason,
  applyConsumeReason,
  consumeReasonHint,
  resolveConsumeReason
} from '@/utils/projectScore.js'
import { useSubmitLock } from '@/composables/useSubmitLock.js'
import { useProjects, syncProjectIndex } from '@/composables/useProjects.js'

const player = ref(null)
const qrToken = ref('')
const projectIndex = ref(0)
const score = ref('')
const presetReason = ref('')
const customReason = ref('')
const reasonIndex = ref(-1)
const scanning = ref(false)
const { submitting, runSubmit } = useSubmitLock()

const currentProject = computed(() => projects.value[projectIndex.value])
const scoreHintText = computed(() => scoreHint(currentProject.value, 'consume'))
const scoreLocked = computed(() => isScoreLocked(currentProject.value, 'consume'))
const reasonSelectOptions = computed(() =>
  getConsumeReasonOptions(currentProject.value).map((label) => ({ label }))
)
const showPresetReason = computed(() => showPresetConsumeReason(currentProject.value))
const showCustomReason = computed(() => showCustomConsumeReason(currentProject.value))
const reasonHintText = computed(() => consumeReasonHint(currentProject.value))

const { projects } = useProjects({
  getSelectedId: () => projects.value[projectIndex.value]?.id,
  onUpdated: (list, selectedId) => {
    syncProjectIndex(list, projectIndex, selectedId)
    if (player.value) fillFromProject()
  }
})

function syncReasonFromProject() {
  const options = getConsumeReasonOptions(currentProject.value)
  const preset = applyConsumeReason(currentProject.value)
  const idx = options.findIndex((r) => r === preset)
  reasonIndex.value = idx >= 0 ? idx : (options.length ? 0 : -1)
  presetReason.value = idx >= 0 ? preset : (options[0] || '')
  customReason.value = ''
}

function fillFromProject() {
  score.value = applyProjectScore(currentProject.value, 'consume')
  syncReasonFromProject()
}

function onProjectChange(e) {
  projectIndex.value = Number(e.detail.value)
  fillFromProject()
}

function onReasonSelect({ item }) {
  presetReason.value = item?.label || ''
}

async function scanOnce() {
  if (scanning.value) return
  scanning.value = true
  try {
    const result = await scanQRCode()
    const verify = await post('/api/qr/verify', { token: result })
    player.value = verify.data
    qrToken.value = result
    fillFromProject()
  } catch (e) {
    if (e.message !== '扫码取消') {
      uni.showToast({ title: e.message || '扫码失败', icon: 'none' })
    }
  } finally {
    scanning.value = false
  }
}

async function submit() {
  if (submitting.value) return
  const finalReason = resolveConsumeReason(currentProject.value, presetReason.value, customReason.value)
  if (!score.value || !finalReason) {
    uni.showToast({ title: '请填写完整', icon: 'none' })
    return
  }
  const project = currentProject.value
  const points = parseInt(score.value)
  await runSubmit(async () => {
    try {
      await post('/api/scores/consume', {
        qr_token: qrToken.value,
        project_id: project?.id,
        score: points,
        reason: finalReason,
        csrf_token: getCsrfToken()
      })
      uni.showToast({ title: `已为玩家核销 -${points} 积分`, icon: 'success' })
      player.value = null
      score.value = ''
      presetReason.value = ''
      customReason.value = ''
      reasonIndex.value = -1
      setTimeout(() => scanOnce(), 400)
    } catch (e) {
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
