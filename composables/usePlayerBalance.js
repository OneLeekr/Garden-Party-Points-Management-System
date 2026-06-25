import { computed, onUnmounted } from 'vue'
import { onShow, onHide } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user.js'
import playerTabStore from '@/store/playerTab.js'
import { mountPlayerGlobalUI } from '@/utils/playerGlobalUi.js'
import { showPlayerScoreFlash, usePlayerScoreFlash } from '@/store/playerFlash.js'
import { isAuthLocked, registerAuthPollStopper, hasActiveSession } from '@/utils/authGuard.js'

let pollTimer = null
let lastScore = null
let refreshAllImpl = async () => {}

export function stopPlayerPoll() {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
}

registerAuthPollStopper(stopPlayerPoll)

async function refreshProfile() {
  if (!hasActiveSession()) return
  try {
    const store = useUserStore()
    await store.fetchProfile()
    const newScore = store.userInfo?.score ?? 0
    if (lastScore !== null && newScore !== lastScore) {
      const diff = newScore - lastScore
      showPlayerScoreFlash(Math.abs(diff), diff > 0 ? 'gain' : 'consume')
    }
    lastScore = newScore
  } catch (_) {}
}

/**
 * 玩家端：自动刷新积分 + 积分变动时全屏动画
 */
export function usePlayerBalance(options = {}) {
  const { intervalMs = 2000, onRefresh } = options

  const balance = computed(() => useUserStore().userInfo?.score ?? 0)

  async function refreshAll() {
    if (isAuthLocked() || !hasActiveSession()) {
      stopPlayerPoll()
      return
    }
    mountPlayerGlobalUI()
    playerTabStore.syncFromRoute()
    uni.hideTabBar({ animation: false })
    await refreshProfile()
    if (onRefresh && hasActiveSession()) await onRefresh()
  }

  onShow(() => {
    if (isAuthLocked() || !hasActiveSession()) return
    refreshAllImpl = refreshAll
    refreshAllImpl()
    if (!pollTimer) {
      pollTimer = setInterval(() => refreshAllImpl(), intervalMs)
    }
  })

  onHide(() => {
    stopPlayerPoll()
  })

  onUnmounted(() => {
    stopPlayerPoll()
  })

  return { balance, refreshProfile, refreshAll }
}

/** 玩家页：积分全屏动画 + 自动刷新（TabBar 由全局单例挂载） */
export function usePlayerShell(extra = {}) {
  const flash = usePlayerScoreFlash()
  const { balance } = usePlayerBalance(extra)
  return { flash, balance }
}
