<template>
  <view class="p-page">
    <picker :range="projects" range-key="name" @change="onProjectChange">
      <view class="yy-input picker-val mb-md">{{ projects[projectIndex]?.name || '选择项目' }}</view>
    </picker>

    <view class="yy-card" v-if="projectStats">
      <text class="yy-title">今日统计</text>
      <view class="stat-row">
        <StatCard label="参与人数" :value="projectStats.participant_count" type="success" />
        <StatCard label="发放积分" :value="projectStats.total_gain" />
        <StatCard label="核销积分" :value="projectStats.total_consume" type="danger" />
      </view>
    </view>

    <view class="yy-card">
      <text class="yy-title">今日记录</text>
      <view v-for="r in records" :key="r.id" class="record-item">
        <view class="flex-between">
          <text>{{ r.user_name }}</text>
          <text :class="isScoreMinus(r) ? 'minus' : 'plus'">{{ formatScoreChange(r) }}</text>
        </view>
        <text class="meta">{{ formatBeijingDateTime(r.created_at) }} | {{ r.reason }}</text>
      </view>
      <view v-if="!records.length" class="yy-empty">暂无记录</view>
    </view>
  </view>
</template>

<script setup>
import { ref } from 'vue'
import { get } from '@/utils/request.js'
import StatCard from '@/components/StatCard/StatCard.vue'
import { formatScoreChange, isScoreMinus } from '@/utils/scoreRecord.js'
import { formatBeijingDateTime } from '@/utils/datetime.js'
import { useProjects, syncProjectIndex } from '@/composables/useProjects.js'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

const projectIndex = ref(0)
const records = ref([])
const projectStats = ref(null)

const { projects } = useProjects({
  getSelectedId: () => projects.value[projectIndex.value]?.id,
  onUpdated: (list, selectedId) => {
    syncProjectIndex(list, projectIndex, selectedId)
    if (list.length) loadRecords()
  }
})

useAutoRefresh(() => {
  if (projects.value.length) loadRecords()
}, { intervalMs: 5000, silent: true })

function onProjectChange(e) {
  projectIndex.value = e.detail.value
  loadRecords()
}

async function loadRecords() {
  const project = projects.value[projectIndex.value]
  if (!project) return
  const [recRes, statRes] = await Promise.all([
    get('/api/scores/records', { project_id: project.id, today: 1 }),
    get('/api/scores/project-stats', { project_id: project.id })
  ])
  records.value = recRes.data.list
  projectStats.value = statRes.data
}
</script>

<style scoped>
.picker-val { line-height: 88rpx; }
.stat-row { display: flex; gap: 16rpx; margin-top: 16rpx; }
.record-item { padding: 20rpx 0; border-bottom: 1px solid var(--border); }
.meta { font-size: 22rpx; color: var(--text-secondary); }
.plus { color: var(--success); font-weight: 600; }
.minus { color: var(--danger); font-weight: 600; }
</style>
