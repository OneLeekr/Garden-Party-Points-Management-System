import { defineStore } from 'pinia'
import { get, post } from '@/utils/request.js'
import {
  getToken, setToken, getUser, setUser, clearAuth,
  setCsrfToken, getRemember, setRemember, clearRemember, getRoleRoute
} from '@/utils/auth.js'
import playerTabStore from '@/store/playerTab.js'
import { hidePlayerGlobalUI } from '@/utils/playerGlobalUi.js'
import { unlockAuth, registerSessionClearer } from '@/utils/authGuard.js'

export const useUserStore = defineStore('user', {
  state: () => ({
    token: getToken(),
    userInfo: getUser(),
    csrfToken: ''
  }),
  getters: {
    isLoggedIn: (state) => !!state.token,
    roleSlug: (state) => state.userInfo?.role_slug || '',
    mustChangePassword: (state) => state.userInfo?.must_change_password === 1
  },
  actions: {
    async login(username, password, remember = false) {
      unlockAuth()
      const res = await post('/api/auth/login', { username, password, remember })
      this.token = res.data.token
      this.userInfo = res.data.user
      this.csrfToken = res.data.csrf_token
      setToken(res.data.token)
      setUser(res.data.user)
      setCsrfToken(res.data.csrf_token)
      if (remember) {
        setRemember({ username, password })
      } else {
        clearRemember()
      }
      return res.data
    },
    async fetchProfile() {
      const res = await get('/api/auth/me')
      this.userInfo = { ...this.userInfo, ...res.data }
      setUser(this.userInfo)
      return res.data
    },
    async refreshCsrf() {
      const res = await get('/api/auth/csrf-token')
      this.csrfToken = res.data.csrf_token
      setCsrfToken(res.data.csrf_token)
    },
    async logout() {
      try {
        await post('/api/auth/logout', {})
      } catch (_) {}
      this.token = ''
      this.userInfo = null
      this.csrfToken = ''
      clearAuth()
      unlockAuth()
      playerTabStore.hideTabBar()
      hidePlayerGlobalUI()
    },
    navigateByRole() {
      const route = getRoleRoute(this.userInfo?.role_slug)
      uni.reLaunch({ url: route })
    },
    loadRemember() {
      return getRemember()
    }
  }
})

registerSessionClearer(() => {
  const store = useUserStore()
  store.token = ''
  store.userInfo = null
  store.csrfToken = ''
})
