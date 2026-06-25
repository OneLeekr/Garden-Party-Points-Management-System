/**
 * API 地址配置
 * ByetHost 免费主机有防机器人验证，前端必须与 API 部署在同一域名下才能正常请求
 */
function resolveBaseURL() {
  // H5 部署在虚拟主机同域名时，使用当前站点 origin（推荐）
  if (typeof window !== 'undefined' && window.location.protocol.startsWith('http')) {
    const host = window.location.hostname
    // 已部署到线上域名（含 byethost 或自定义域名）
    if (!host.includes('localhost') && !host.includes('127.0.0.1')) {
      return window.location.origin
    }
    // 本地开发：走 devServer 代理（manifest.json h5.devServer.proxy）
    if (import.meta.env && import.meta.env.DEV) {
      return ''
    }
  }
  return 'http://your-domain.example.com'
}

const config = {
  baseURL: resolveBaseURL(),
  timeout: 60000
}

export default config
export { config, resolveBaseURL }
