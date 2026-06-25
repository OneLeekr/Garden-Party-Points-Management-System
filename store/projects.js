import { defineStore } from 'pinia'
import { get } from '@/utils/request.js'

export const useProjectsStore = defineStore('projects', {
  state: () => ({
    list: [],
    updatedAt: 0,
    loading: false
  }),
  actions: {
    applyList(list) {
      this.list = Array.isArray(list) ? list : []
      this.updatedAt = Date.now()
    },
    async fetchProjects() {
      if (this.loading) return this.list
      this.loading = true
      try {
        const res = await get('/api/projects')
        this.applyList(res.data)
        return this.list
      } finally {
        this.loading = false
      }
    }
  }
})
