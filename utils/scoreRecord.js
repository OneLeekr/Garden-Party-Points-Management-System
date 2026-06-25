/**
 * 积分流水显示符号
 */
export function getScoreSign(record) {
  if (!record) return '+'
  if (record.type === 'consume') return '-'
  if (record.type === 'gain') return '+'
  if (record.type === 'adjust') {
    if (record.reason && record.reason.includes('[扣除]')) return '-'
    return '+'
  }
  return '+'
}

export function isScoreMinus(record) {
  return getScoreSign(record) === '-'
}

export function formatScoreChange(record) {
  const sign = getScoreSign(record)
  return `${sign}${record?.score ?? 0}`
}

export function getRecordTypeLabel(type, reason) {
  const map = { gain: '发放', consume: '核销', adjust: '调整' }
  if (type === 'adjust' && reason && reason.includes('[扣除]')) return '扣除'
  if (type === 'adjust' && reason && reason.includes('[增加]')) return '增加'
  return map[type] || type
}
