import { onUnmounted } from 'vue'
import { onShow, onHide } from '@dcloudio/uni-app'
import { isAuthLocked } from '@/utils/authGuard.js'

/**
 * 页面可见时自动刷新 + 定时轮询（配置/统计变更后无需手动刷新）
 */
export function useAutoRefresh(refreshFn, options = {}) {
  const {
    intervalMs = 5000,
    refreshOnShow = true,
    silent = true
  } = options

  let timer = null
  let pageVisible = false

  async function refresh() {
    if (isAuthLocked()) return
    try {
      await refreshFn()
    } catch (e) {
      if (!silent) {
        uni.showToast({ title: e.message || '刷新失败', icon: 'none' })
      }
    }
  }

  function startPoll() {
    stopPoll()
    if (intervalMs > 0) {
      timer = setInterval(() => {
        if (pageVisible) refresh()
      }, intervalMs)
    }
  }

  function stopPoll() {
    if (timer) {
      clearInterval(timer)
      timer = null
    }
  }

  onShow(() => {
    pageVisible = true
    if (refreshOnShow) refresh()
    startPoll()
  })

  onHide(() => {
    pageVisible = false
    stopPoll()
  })

  onUnmounted(stopPoll)

  // #ifdef H5
  if (typeof document !== 'undefined') {
    const onVisible = () => {
      if (document.visibilityState === 'visible' && pageVisible) refresh()
    }
    document.addEventListener('visibilitychange', onVisible)
    onUnmounted(() => document.removeEventListener('visibilitychange', onVisible))
  }
  // #endif

  return { refresh, startPoll, stopPoll }
}
