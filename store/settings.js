import { defineStore } from 'pinia'
import { get } from '@/utils/request.js'

const DEFAULTS = {
  site_name: '游园会积分登记与核销系统',
  site_name_en: 'YuYuan Fair Score System'
}

export const useSettingsStore = defineStore('settings', {
  state: () => ({
    site_name: DEFAULTS.site_name,
    site_name_en: DEFAULTS.site_name_en,
    loaded: false
  }),
  actions: {
    apply(data = {}) {
      if (data.site_name) this.site_name = data.site_name
      if (data.site_name_en) this.site_name_en = data.site_name_en
      this.loaded = true
      this.syncDocumentTitle()
    },
    syncDocumentTitle() {
      // #ifdef H5
      if (typeof document !== 'undefined' && this.site_name) {
        document.title = this.site_name
      }
      // #endif
    },
    async loadPublic() {
      try {
        const res = await get('/api/settings/public')
        this.apply(res.data)
      } catch (_) {
        this.loaded = true
      }
    }
  }
})
