import config from '@/config/index.js'

const TOKEN_KEY = 'yyfss_token'
const USER_KEY = 'yyfss_user'
const CSRF_KEY = 'yyfss_csrf'
const REMEMBER_KEY = 'yyfss_remember'

export function getToken() {
  return uni.getStorageSync(TOKEN_KEY) || ''
}

export function setToken(token) {
  uni.setStorageSync(TOKEN_KEY, token)
}

export function removeToken() {
  uni.removeStorageSync(TOKEN_KEY)
}

export function getUser() {
  try {
    return JSON.parse(uni.getStorageSync(USER_KEY) || 'null')
  } catch {
    return null
  }
}

export function setUser(user) {
  uni.setStorageSync(USER_KEY, JSON.stringify(user))
}

export function removeUser() {
  uni.removeStorageSync(USER_KEY)
}

export function getCsrfToken() {
  return uni.getStorageSync(CSRF_KEY) || ''
}

export function setCsrfToken(token) {
  uni.setStorageSync(CSRF_KEY, token)
}

export function getRemember() {
  try {
    return JSON.parse(uni.getStorageSync(REMEMBER_KEY) || 'null')
  } catch {
    return null
  }
}

export function setRemember(data) {
  uni.setStorageSync(REMEMBER_KEY, JSON.stringify(data))
}

export function clearRemember() {
  uni.removeStorageSync(REMEMBER_KEY)
}

export function clearAuth() {
  removeToken()
  removeUser()
  uni.removeStorageSync(CSRF_KEY)
}

export function getRoleRoute(roleSlug) {
  const map = {
    super_admin: '/pages/super/dashboard',
    admin: '/pages/admin/index',
    player: '/pages/player/home'
  }
  return map[roleSlug] || '/pages/login/login'
}

export { config, TOKEN_KEY, USER_KEY }
