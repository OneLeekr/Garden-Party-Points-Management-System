/** 从 User-Agent 解析简短设备描述 */
export function parseDeviceLabel(ua) {
  if (!ua) return '未知设备'
  const text = String(ua)
  if (/iPhone/i.test(text)) return 'iPhone'
  if (/iPad/i.test(text)) return 'iPad'
  if (/Android/i.test(text) && /Mobile/i.test(text)) return 'Android 手机'
  if (/Android/i.test(text)) return 'Android 平板'
  if (/Windows/i.test(text)) return 'Windows'
  if (/Macintosh|Mac OS X/i.test(text)) return 'Mac'
  if (/MicroMessenger/i.test(text)) return '微信内置浏览器'
  return text.length > 48 ? `${text.slice(0, 48)}…` : text
}
