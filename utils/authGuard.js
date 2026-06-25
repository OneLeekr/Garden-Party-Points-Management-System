import { clearAuth, getToken } from '@/utils/auth.js'
import playerTabStore from '@/store/playerTab.js'
import { hidePlayerGlobalUI } from '@/utils/playerGlobalUi.js'

let authLocked = false
let kickoffShown = false
let stopPollCallback = null
let clearStoreCallback = null

export function registerAuthPollStopper(fn) {
  stopPollCallback = fn
}

export function registerSessionClearer(fn) {
  clearStoreCallback = fn
}

export function isAuthLocked() {
  return authLocked
}

export function unlockAuth() {
  authLocked = false
  kickoffShown = false
}

function shouldForceLogout(message) {
  const msg = message || ''
  if (!msg) return false
  return (
    msg.includes('封禁') ||
    msg.includes('不存在') ||
    msg.includes('未登录') ||
    msg.includes('Token') ||
    msg.includes('token') ||
    msg.includes('无效') ||
    msg.includes('过期') ||
    msg.includes('其他设备') ||
    msg.includes('重新登录') ||
    msg.includes('登录已失效')
  )
}

function formatKickMessage(message) {
  const msg = message || '请重新登录'
  if (msg.includes('封禁')) return '您的账号已被封禁，请联系管理员'
  if (msg.includes('其他设备')) return '您的账号已在其他设备登录'
  if (msg.includes('不存在')) return '账号不存在或已被删除'
  return msg
}

function clearSessionState() {
  clearAuth()
  playerTabStore.hideTabBar()
  hidePlayerGlobalUI()
  if (stopPollCallback) stopPollCallback()
  if (clearStoreCallback) clearStoreCallback()
}

function goLoginPage() {
  uni.reLaunch({ url: '/pages/login/login' })
}

/** 账号失效：弹窗一次并跳转登录页 */
export function handleAuthFailure(message, options = {}) {
  const { force = false } = options
  if (authLocked) return true
  if (!force && !shouldForceLogout(message)) return false

  authLocked = true
  clearSessionState()

  const tip = formatKickMessage(message)

  if (kickoffShown) {
    goLoginPage()
    return true
  }
  kickoffShown = true

  uni.showModal({
    title: '提示',
    content: tip,
    showCancel: false,
    confirmText: '重新登录',
    success: () => goLoginPage(),
    fail: () => goLoginPage()
  })

  return true
}

export function hasActiveSession() {
  return !authLocked && !!getToken()
}
