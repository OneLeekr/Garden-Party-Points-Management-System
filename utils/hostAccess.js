export function isHostProtectionResponse(text) {
  if (typeof text !== 'string' || !text) return false
  return text.includes('aes.js') || (text.includes('<html') && text.includes('<script'))
}

export function hostAccessMessage(reason) {
  if (reason === 'host_protection') {
    return '主机防护拦截，请用浏览器打开网站域名访问并完成验证后再试'
  }
  if (reason === 'timeout') {
    return '连接服务器超时，请检查网络后刷新重试'
  }
  return '无法连接服务器，请确认前端与 API 已部署在同一域名'
}

export async function probeApiAccess(baseURL = '', timeoutMs = 15000) {
  const controller = typeof AbortController !== 'undefined' ? new AbortController() : null
  const timer = controller
    ? setTimeout(() => controller.abort(), timeoutMs)
    : null

  try {
    const res = await fetch(`${baseURL}/api/health?_=${Date.now()}`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      signal: controller?.signal
    })
    const text = await res.text()
    if (isHostProtectionResponse(text)) {
      return { ok: false, reason: 'host_protection' }
    }
    JSON.parse(text)
    return { ok: true }
  } catch (e) {
    const reason = e?.name === 'AbortError' ? 'timeout' : 'network'
    return { ok: false, reason }
  } finally {
    if (timer) clearTimeout(timer)
  }
}
