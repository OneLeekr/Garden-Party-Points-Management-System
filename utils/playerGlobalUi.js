import { createApp } from 'vue'
import PlayerTabBar from '@/components/PlayerTabBar/PlayerTabBar.vue'

let mounted = false

/** H5：将底部导航挂到 body，避免随页面滚动被遮挡 */
export function mountPlayerGlobalUI() {
  // #ifdef H5
  if (typeof document === 'undefined') return
  if (document.getElementById('yyfss-tabbar-root')) {
    mounted = true
    showPlayerGlobalUI()
    return
  }
  const root = document.createElement('div')
  root.id = 'yyfss-tabbar-root'
  document.body.appendChild(root)
  createApp(PlayerTabBar).mount(root)
  mounted = true
  // #endif
}

/** H5：隐藏全局底部导航（登录页等） */
export function hidePlayerGlobalUI() {
  // #ifdef H5
  if (typeof document === 'undefined') return
  const root = document.getElementById('yyfss-tabbar-root')
  if (root) root.style.display = 'none'
  // #endif
}

/** H5：显示全局底部导航（玩家页） */
export function showPlayerGlobalUI() {
  // #ifdef H5
  if (typeof document === 'undefined') return
  const root = document.getElementById('yyfss-tabbar-root')
  if (root) root.style.display = 'block'
  // #endif
}
