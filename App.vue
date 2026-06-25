<script setup>
import { onLaunch, onShow } from '@dcloudio/uni-app'
import playerTabStore from '@/store/playerTab.js'
import { mountPlayerGlobalUI } from '@/utils/playerGlobalUi.js'
import { useSettingsStore } from '@/store/settings.js'
import config from '@/config/index.js'
import { probeApiAccess } from '@/utils/hostAccess.js'

onLaunch(async () => {
  // #ifdef H5
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((regs) => {
      regs.forEach((reg) => reg.unregister())
    })
  }
  const isStandalone = window.matchMedia('(display-mode: standalone)').matches
    || window.matchMedia('(display-mode: fullscreen)').matches
    || window.navigator.standalone === true
  if (isStandalone) {
    document.documentElement.classList.add('pwa-standalone')
  }
  const reloadKey = 'yyfss_host_verify_reload'
  const probe = await probeApiAccess(config.baseURL)
  if (!probe.ok && probe.reason === 'host_protection' && !sessionStorage.getItem(reloadKey)) {
    sessionStorage.setItem(reloadKey, '1')
    window.location.reload()
    return
  }
  if (probe.ok) {
    sessionStorage.removeItem(reloadKey)
  }
  // #endif

  useSettingsStore().loadPublic()
  mountPlayerGlobalUI()
})

onShow(() => {
  playerTabStore.syncFromRoute()
})
</script>

<style lang="scss">
@import '@/styles/theme.scss';
</style>
