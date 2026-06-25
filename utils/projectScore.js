/**
 * 根据活动配置自动填入登记/核销积分
 */
export function isFlagOn(v) {
  return v === 1 || v === '1' || v === true
}

export function applyProjectScore(project, mode = 'gain') {
  if (!project) return ''
  if (mode === 'gain') {
    if (isFlagOn(project.auto_fill_gain) && Number(project.gain_score) > 0) {
      return String(project.gain_score)
    }
    return ''
  }
  if (isFlagOn(project.auto_fill_consume) && Number(project.consume_score) > 0) {
    return String(project.consume_score)
  }
  return ''
}

export function isScoreLocked(project, mode = 'gain') {
  if (!project) return false
  if (mode === 'gain') {
    return isFlagOn(project.auto_fill_gain) && isFlagOn(project.lock_auto_fill_gain)
  }
  return isFlagOn(project.auto_fill_consume) && isFlagOn(project.lock_auto_fill_consume)
}

export function scoreHint(project, mode = 'gain') {
  if (!project) return ''
  if (mode === 'gain') {
    if (isFlagOn(project.auto_fill_gain) && project.gain_score > 0) {
      return isScoreLocked(project, 'gain')
        ? `已自动填入 ${project.gain_score} 分，不可修改`
        : `已自动填入 ${project.gain_score} 分，可修改`
    }
    return '请手动输入积分'
  }
  if (isFlagOn(project.auto_fill_consume) && project.consume_score > 0) {
    return isScoreLocked(project, 'consume')
      ? `已自动填入 ${project.consume_score} 分，不可修改`
      : `已自动填入 ${project.consume_score} 分，可修改`
  }
  return '请手动输入扣除积分'
}

export function getConsumeReasonOptions(project) {
  if (!project) return []
  let list = project.consume_reasons
  if (typeof list === 'string') {
    const trimmed = list.trim()
    if (trimmed.startsWith('[')) {
      try {
        list = JSON.parse(trimmed)
      } catch {
        list = trimmed
      }
    }
    if (typeof list === 'string' && list) {
      return list.split(/[\n,，;；]+/).map((s) => s.trim()).filter(Boolean)
    }
  }
  if (Array.isArray(list)) return list.map((s) => String(s).trim()).filter(Boolean)
  return []
}

export function showPresetConsumeReason(project) {
  if (!project || !isFlagOn(project.use_preset_consume_reason)) return false
  return getConsumeReasonOptions(project).length > 0
}

export function showCustomConsumeReason(project) {
  if (!project) return true
  if (showPresetConsumeReason(project)) {
    return isFlagOn(project.allow_custom_consume_reason)
  }
  return true
}

export function applyConsumeReason(project) {
  if (!project || !showPresetConsumeReason(project)) return ''
  if (!isFlagOn(project.auto_fill_consume_reason)) return ''
  return project.default_consume_reason || getConsumeReasonOptions(project)[0] || ''
}

export function consumeReasonHint(project) {
  if (!project) return '请填写核销原因'
  const preset = showPresetConsumeReason(project)
  const custom = showCustomConsumeReason(project)
  if (preset && custom) return '可选择预设原因，或在下方自定义输入'
  if (preset) return '请从预设原因中选择'
  return '请手动输入核销原因'
}

export function resolveConsumeReason(project, presetReason, customReason) {
  const custom = String(customReason || '').trim()
  if (custom && showCustomConsumeReason(project)) return custom
  const preset = String(presetReason || '').trim()
  if (preset) return preset
  return ''
}

/** 未配置时默认允许重复游玩（兼容旧版后端） */
export function allowsRepeatPlay(project) {
  if (!project) return true
  return project.allow_repeat_play !== 0 && project.allow_repeat_play !== false
}
