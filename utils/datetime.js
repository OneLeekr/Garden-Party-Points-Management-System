const BEIJING_TZ = 'Asia/Shanghai'

/**
 * 格式化为北京时间显示（后端已按 Asia/Shanghai 存储时直接展示）
 */
export function formatBeijingDateTime(value, style = 'full') {
  if (value === null || value === undefined || value === '') return ''

  const text = String(value).trim()
  if (/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(text)) {
    const normalized = text.replace('T', ' ').slice(0, 19)
    if (style === 'short') return normalized.slice(5, 16)
    if (style === 'date') return normalized.slice(0, 10)
    return normalized
  }

  const date = new Date(text)
  if (Number.isNaN(date.getTime())) return text

  const opts = {
    timeZone: BEIJING_TZ,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }
  if (style === 'full') opts.second = '2-digit'
  if (style === 'date') {
    delete opts.hour
    delete opts.minute
  }

  const parts = new Intl.DateTimeFormat('zh-CN', opts).formatToParts(date)
  const pick = (type) => parts.find((p) => p.type === type)?.value || ''
  const y = pick('year')
  const m = pick('month')
  const d = pick('day')
  if (style === 'date') return `${y}-${m}-${d}`
  const hh = pick('hour')
  const mm = pick('minute')
  if (style === 'short') return `${m}-${d} ${hh}:${mm}`
  const ss = pick('second')
  return `${y}-${m}-${d} ${hh}:${mm}:${ss}`
}
