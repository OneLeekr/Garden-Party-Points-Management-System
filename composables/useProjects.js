import { computed } from 'vue'
import { useProjectsStore } from '@/store/projects.js'
import { useAutoRefresh } from '@/composables/useAutoRefresh.js'

/**
 * 活动项目列表：进入页面自动拉取，并定时同步后台配置变更
 */
export function useProjects(options = {}) {
  const { intervalMs = 8000, onUpdated } = options
  const projectsStore = useProjectsStore()
  const projects = computed(() => projectsStore.list)

  async function refreshProjects() {
    const selectedBefore = options.getSelectedId?.()
    await projectsStore.fetchProjects()
    if (typeof onUpdated === 'function') {
      onUpdated(projectsStore.list, selectedBefore)
    }
  }

  const { refresh } = useAutoRefresh(refreshProjects, { intervalMs, silent: true })

  return { projects, refreshProjects: refresh, projectsStore }
}

/** 按 id 保留当前选中项，列表变化时回退到第一项 */
export function syncProjectIndex(projects, projectIndex, preferredId) {
  if (!projects?.length) {
    projectIndex.value = 0
    return null
  }
  const id = preferredId ?? projects[projectIndex.value]?.id
  const idx = projects.findIndex((p) => p.id === id)
  projectIndex.value = idx >= 0 ? idx : 0
  return projects[projectIndex.value] ?? null
}
