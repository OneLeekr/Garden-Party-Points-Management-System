import { ref, computed } from 'vue'

const activeTab = ref('home')
const visible = ref(false)

const tabRoutes = {
  home: '/pages/player/home',
  scores: '/pages/player/scores',
  qrcode: '/pages/player/qrcode',
  profile: '/pages/player/profile'
}

const routeToTab = Object.fromEntries(
  Object.entries(tabRoutes).map(([key, path]) => [path.replace(/^\//, ''), key])
)

export function usePlayerTabStore() {
  const currentActive = computed(() => activeTab.value)

  function setActiveTab(key) {
    if (tabRoutes[key]) activeTab.value = key
  }

  function syncFromRoute() {
    const pages = getCurrentPages()
    const page = pages[pages.length - 1]
    const route = page?.route || ''
    const key = routeToTab[route]
    if (key) {
      activeTab.value = key
      visible.value = true
    } else {
      visible.value = false
    }
  }

  function showTabBar() {
    visible.value = true
  }

  function hideTabBar() {
    visible.value = false
  }

  return {
    activeTab: currentActive,
    tabVisible: computed(() => visible.value),
    tabRoutes,
    setActiveTab,
    syncFromRoute,
    showTabBar,
    hideTabBar
  }
}

// 单例供 App.vue 与 composable 共用
const store = usePlayerTabStore()
export default store
