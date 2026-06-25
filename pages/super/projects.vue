<template>
  <SuperLayout active="projects">
    <template #title>项目管理</template>
    <button class="yy-btn yy-btn-primary mb-md" @click="openForm()">创建项目</button>

    <view class="yy-card" v-for="p in list" :key="p.id">
      <view class="flex-between">
        <text class="yy-title">{{ p.name }}</text>
        <text :class="['yy-tag', p.status ? 'yy-tag-success' : 'yy-tag-danger']">{{ p.status ? '启用' : '禁用' }}</text>
      </view>
      <text class="yy-subtitle mt-sm">{{ p.description }}</text>
      <view class="meta mt-sm">
        <text>地点: {{ p.location || '-' }} | 负责人: {{ p.manager_name || p.admin_names || '-' }}</text>
      </view>
      <view class="score-meta mt-sm">
        <text>登记: {{ p.gain_score || 0 }}分 · {{ p.auto_fill_gain ? '自动填入' : '手动输入' }}{{ p.auto_fill_gain && p.lock_auto_fill_gain ? '·不可改' : '' }} · {{ p.allow_repeat_play ? '可重复' : '限玩一次' }}</text>
        <text class="sep"> | </text>
        <text>核销: {{ p.consume_score || 0 }}分 · {{ p.auto_fill_consume ? '自动填入' : '手动输入' }}{{ p.auto_fill_consume && p.lock_auto_fill_consume ? '·不可改' : '' }}</text>
      </view>
      <view v-if="p.consume_reasons?.length" class="score-meta">
        <text>核销原因: {{ p.consume_reasons.length }}项预设 · {{ p.use_preset_consume_reason ? '可选' : '未启用' }}{{ p.allow_custom_consume_reason ? ' · 可自定义' : '' }}</text>
      </view>
      <view class="action-row mt-sm">
        <text class="action-btn" @click="openForm(p)">编辑</text>
        <text class="action-btn danger" @click="remove(p)">删除</text>
      </view>
    </view>

    <AppModal :visible="showForm" @update:visible="showForm = $event" size="lg" :close-on-mask="false">
      <view class="form-body" @click.stop @tap.stop>
      <scroll-view scroll-y class="form-scroll" @click.stop @tap.stop>
        <text class="yy-title">{{ form.id ? '编辑项目' : '创建项目' }}</text>
        <text class="yy-label mt-md">项目名称</text>
        <input class="yy-input" v-model="form.name" placeholder="请输入项目名称" />
        <text class="yy-label mt-md">项目简介</text>
        <input class="yy-input" v-model="form.description" placeholder="选填" />
        <text class="yy-label mt-md">项目地点</text>
        <input class="yy-input" v-model="form.location" placeholder="选填" />
        <text class="yy-label mt-md">项目负责人</text>
        <FormSelect
          v-model="adminIndex"
          :options="adminList"
          label-key="label"
          placeholder="从普通管理员中选择"
          @change="onAdminSelect"
        />

        <view class="section-divider mt-md">
          <text class="section-title">登记积分（扫码发放）</text>
        </view>
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">自动填入默认积分</text>
            <text class="switch-desc">开启后管理员登记时自动填写默认积分</text>
          </view>
          <switch :checked="form.auto_fill_gain === 1" @change="onGainSwitch" @click.stop color="#2563eb" />
        </view>
        <view v-if="form.auto_fill_gain === 1" class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">锁定自动填入积分</text>
            <text class="switch-desc">开启后管理员不可修改自动填入的分值</text>
          </view>
          <switch :checked="form.lock_auto_fill_gain === 1" @change="onLockGainSwitch" @click.stop color="#2563eb" />
        </view>
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">允许重复游玩</text>
            <text class="switch-desc">关闭后每位玩家在同一活动只能登记一次积分</text>
          </view>
          <switch :checked="form.allow_repeat_play === 1" @change="onRepeatPlaySwitch" @click.stop color="#2563eb" />
        </view>
        <text class="yy-label mt-md">登记默认积分</text>
        <input class="yy-input" v-model="form.gain_score" type="number" placeholder="如：5" />

        <view class="section-divider mt-md">
          <text class="section-title">核销积分（扫码扣除）</text>
        </view>
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">自动填入默认积分</text>
            <text class="switch-desc">开启后管理员核销时自动填写默认积分</text>
          </view>
          <switch :checked="form.auto_fill_consume === 1" @change="onConsumeSwitch" @click.stop color="#2563eb" />
        </view>
        <view v-if="form.auto_fill_consume === 1" class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">锁定自动填入积分</text>
            <text class="switch-desc">开启后管理员不可修改自动填入的分值</text>
          </view>
          <switch :checked="form.lock_auto_fill_consume === 1" @change="onLockConsumeSwitch" @click.stop color="#2563eb" />
        </view>
        <text class="yy-label mt-md">核销默认积分</text>
        <input class="yy-input" v-model="form.consume_score" type="number" placeholder="如：10" />

        <view class="section-divider mt-md">
          <text class="section-title">核销原因</text>
        </view>
        <text class="yy-label mt-sm">预设原因（每行一个）</text>
        <textarea
          class="yy-textarea"
          v-model="consumeReasonsText"
          placeholder="礼品兑换&#10;纪念品领取&#10;活动奖品"
          :maxlength="500"
        />
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">启用预设原因选择</text>
            <text class="switch-desc">开启后核销时可从上方列表中选择</text>
          </view>
          <switch :checked="form.use_preset_consume_reason === 1" @change="onPresetReasonSwitch" @click.stop color="#2563eb" />
        </view>
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">允许自定义输入</text>
            <text class="switch-desc">开启后除选择外还可手动填写其他原因</text>
          </view>
          <switch :checked="form.allow_custom_consume_reason === 1" @change="onCustomReasonSwitch" @click.stop color="#2563eb" />
        </view>
        <view class="switch-row flex-between mt-sm">
          <view>
            <text class="switch-label">自动填入默认原因</text>
            <text class="switch-desc">开启后核销时自动选中默认原因</text>
          </view>
          <switch :checked="form.auto_fill_consume_reason === 1" @change="onAutoReasonSwitch" @click.stop color="#2563eb" />
        </view>
        <text v-if="parsedReasonOptions.length" class="yy-label mt-md">默认选中原因</text>
        <FormSelect
          v-if="parsedReasonOptions.length"
          v-model="defaultReasonIndex"
          :options="parsedReasonOptions"
          label-key="label"
          placeholder="选择默认原因"
          @change="onDefaultReasonSelect"
        />

        <view class="flex-row gap-sm mt-md">
          <button class="yy-btn yy-btn-primary flex-1" @click.stop="save">保存</button>
          <button class="yy-btn yy-btn-outline flex-1" @click.stop="closeForm">取消</button>
        </view>
      </scroll-view>
      </view>
    </AppModal>
  </SuperLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { get, post, put, del } from '@/utils/request.js'
import { getCsrfToken } from '@/utils/auth.js'
import SuperLayout from '@/components/SuperLayout/SuperLayout.vue'
import AppModal from '@/components/AppModal/AppModal.vue'
import FormSelect from '@/components/FormSelect/FormSelect.vue'
import { useProjectsStore } from '@/store/projects.js'

const projectsStore = useProjectsStore()
const list = ref([])
const adminList = ref([])
const showForm = ref(false)
const adminIndex = ref(-1)
const consumeReasonsText = ref('')
const defaultReasonIndex = ref(-1)

const defaultForm = () => ({
  id: 0,
  name: '',
  description: '',
  location: '',
  manager_name: '',
  status: 1,
  admin_ids: [],
  gain_score: 0,
  auto_fill_gain: 0,
  lock_auto_fill_gain: 0,
  allow_repeat_play: 1,
  consume_score: 0,
  auto_fill_consume: 0,
  lock_auto_fill_consume: 0,
  consume_reasons: [],
  use_preset_consume_reason: 0,
  allow_custom_consume_reason: 1,
  auto_fill_consume_reason: 0,
  default_consume_reason: ''
})

const parsedReasonOptions = computed(() =>
  consumeReasonsText.value
    .split(/[\n,，;；]+/)
    .map((s) => s.trim())
    .filter(Boolean)
    .map((label) => ({ label }))
)

const form = ref(defaultForm())

function toFlag(v) {
  return v === 1 || v === '1' || v === true ? 1 : 0
}

function setFormFlag(key, e) {
  const on = !!(e.detail && e.detail.value)
  form.value = { ...form.value, [key]: on ? 1 : 0 }
}

onMounted(async () => {
  await loadData()
  await loadAdmins()
})

async function loadData() {
  const res = await get('/api/projects')
  list.value = res.data
  projectsStore.applyList(res.data)
}

async function loadAdmins() {
  const res = await get('/api/projects/admins')
  adminList.value = res.data.map((a) => ({
    id: a.id,
    label: a.real_name || a.username,
    real_name: a.real_name || a.username
  }))
}

function syncReasonFieldsFromForm() {
  const reasons = Array.isArray(form.value.consume_reasons) ? form.value.consume_reasons : []
  consumeReasonsText.value = reasons.join('\n')
  const idx = reasons.findIndex((r) => r === form.value.default_consume_reason)
  defaultReasonIndex.value = idx >= 0 ? idx : (reasons.length ? 0 : -1)
}

function openForm(p) {
  if (p) {
    const adminIds = p.admin_ids ? String(p.admin_ids).split(',').map(Number).filter(Boolean) : []
    form.value = {
      ...defaultForm(),
      ...p,
      admin_ids: adminIds,
      gain_score: p.gain_score ?? 0,
      auto_fill_gain: toFlag(p.auto_fill_gain),
      lock_auto_fill_gain: toFlag(p.lock_auto_fill_gain),
      allow_repeat_play: p.allow_repeat_play === 0 || p.allow_repeat_play === '0' ? 0 : 1,
      consume_score: p.consume_score ?? 0,
      auto_fill_consume: toFlag(p.auto_fill_consume),
      lock_auto_fill_consume: toFlag(p.lock_auto_fill_consume),
      consume_reasons: Array.isArray(p.consume_reasons) ? p.consume_reasons : [],
      use_preset_consume_reason: toFlag(p.use_preset_consume_reason),
      allow_custom_consume_reason: p.allow_custom_consume_reason === 0 || p.allow_custom_consume_reason === '0' ? 0 : 1,
      auto_fill_consume_reason: toFlag(p.auto_fill_consume_reason),
      default_consume_reason: p.default_consume_reason || ''
    }
    syncReasonFieldsFromForm()
    const firstId = adminIds[0]
    adminIndex.value = adminList.value.findIndex((a) => a.id === firstId)
  } else {
    form.value = defaultForm()
    consumeReasonsText.value = ''
    defaultReasonIndex.value = -1
    adminIndex.value = -1
  }
  showForm.value = true
}

function closeForm() {
  showForm.value = false
}

function onAdminSelect({ item }) {
  if (item) {
    form.value.manager_name = item.real_name
    form.value.admin_ids = [item.id]
  }
}

function onGainSwitch(e) {
  setFormFlag('auto_fill_gain', e)
}

function onRepeatPlaySwitch(e) {
  setFormFlag('allow_repeat_play', e)
}

function onLockGainSwitch(e) {
  setFormFlag('lock_auto_fill_gain', e)
}

function onLockConsumeSwitch(e) {
  setFormFlag('lock_auto_fill_consume', e)
}

function onConsumeSwitch(e) {
  setFormFlag('auto_fill_consume', e)
}

function onPresetReasonSwitch(e) {
  setFormFlag('use_preset_consume_reason', e)
}

function onCustomReasonSwitch(e) {
  setFormFlag('allow_custom_consume_reason', e)
}

function onAutoReasonSwitch(e) {
  setFormFlag('auto_fill_consume_reason', e)
}

function onDefaultReasonSelect({ item }) {
  form.value.default_consume_reason = item?.label || ''
}

async function save() {
  if (!form.value.name.trim()) {
    uni.showToast({ title: '请输入项目名称', icon: 'none' })
    return
  }
  const reasons = consumeReasonsText.value
    .split(/[\n,，;；]+/)
    .map((s) => s.trim())
    .filter(Boolean)
  const data = {
    ...form.value,
    gain_score: parseInt(form.value.gain_score) || 0,
    consume_score: parseInt(form.value.consume_score) || 0,
    auto_fill_gain: form.value.auto_fill_gain ? 1 : 0,
    lock_auto_fill_gain: form.value.lock_auto_fill_gain ? 1 : 0,
    allow_repeat_play: form.value.allow_repeat_play ? 1 : 0,
    auto_fill_consume: form.value.auto_fill_consume ? 1 : 0,
    lock_auto_fill_consume: form.value.lock_auto_fill_consume ? 1 : 0,
    consume_reasons: reasons,
    use_preset_consume_reason: form.value.use_preset_consume_reason ? 1 : 0,
    allow_custom_consume_reason: form.value.allow_custom_consume_reason ? 1 : 0,
    auto_fill_consume_reason: form.value.auto_fill_consume_reason ? 1 : 0,
    default_consume_reason: form.value.default_consume_reason || reasons[0] || '',
    csrf_token: getCsrfToken()
  }
  try {
    if (form.value.id) await put('/api/projects', data)
    else await post('/api/projects', data)
    showForm.value = false
    uni.showToast({ title: '保存成功', icon: 'success' })
    loadData()
  } catch (e) {
    uni.showToast({ title: e.message || '保存失败', icon: 'none' })
  }
}

function remove(p) {
  uni.showModal({
    title: '确认删除',
    success: async (res) => {
      if (res.confirm) {
        await del('/api/projects', { id: p.id, csrf_token: getCsrfToken() })
        loadData()
      }
    }
  })
}
</script>

<style scoped>
.meta { font-size: 24rpx; color: var(--text-secondary); }
.score-meta { font-size: 22rpx; color: var(--text-muted); line-height: 1.6; }
.sep { color: var(--border); }
.action-row { display: flex; gap: 24rpx; }
.action-btn { color: var(--primary); font-size: 26rpx; }
.action-btn.danger { color: var(--danger); }
.form-scroll { max-height: 70vh; padding-right: 8rpx; }
.form-body { width: 100%; }
.section-divider {
  padding-top: 16rpx;
  border-top: 1px solid var(--border);
}
.section-title {
  display: block;
  font-size: 28rpx;
  font-weight: 700;
  color: var(--text-primary);
}
.switch-row { align-items: center; gap: 24rpx; }
.switch-label { display: block; font-size: 28rpx; font-weight: 600; color: var(--text-primary); }
.switch-desc { display: block; font-size: 22rpx; color: var(--text-muted); margin-top: 4rpx; max-width: 420rpx; }
.yy-textarea {
  width: 100%;
  min-height: 160rpx;
  padding: 20rpx 24rpx;
  border: 1px solid var(--border);
  border-radius: 16rpx;
  font-size: 28rpx;
  line-height: 1.5;
  box-sizing: border-box;
  background: var(--bg-page);
}
</style>
