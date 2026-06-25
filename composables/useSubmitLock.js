import { ref } from 'vue'

/**
 * 防止按钮重复点击导致重复提交
 */
export function useSubmitLock() {
  const submitting = ref(false)

  async function runSubmit(fn) {
    if (submitting.value) return false
    submitting.value = true
    try {
      await fn()
      return true
    } finally {
      submitting.value = false
    }
  }

  return { submitting, runSubmit }
}
