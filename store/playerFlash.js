import { ref } from 'vue'

const flashState = ref({
  visible: false,
  score: 0,
  type: 'gain'
})

let hideTimer = null
let flashStyleReady = false

function ensureFlashStyles() {
  if (typeof document === 'undefined' || flashStyleReady) return
  if (document.getElementById('yyfss-flash-style')) {
    flashStyleReady = true
    return
  }
  const style = document.createElement('style')
  style.id = 'yyfss-flash-style'
  style.textContent = `
    .yyfss-score-flash-overlay {
      position: fixed;
      inset: 0;
      z-index: 999999;
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
      animation: yyfssFlashFade 2.8s ease forwards;
    }
    .yyfss-score-flash-overlay.gain { background: #059669; }
    .yyfss-score-flash-overlay.consume { background: #dc2626; }
    .yyfss-score-flash-text {
      font-size: 96px;
      font-weight: 900;
      color: #fff;
      line-height: 1;
      animation: yyfssScorePop 2.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
      text-shadow: 0 8px 32px rgba(0,0,0,0.15);
    }
    @keyframes yyfssScorePop {
      0% { transform: scale(0.2); opacity: 0; }
      12% { transform: scale(1); opacity: 1; }
      75% { transform: scale(1); opacity: 1; }
      100% { transform: scale(1.15); opacity: 0; }
    }
    @keyframes yyfssFlashFade {
      0%, 80% { opacity: 1; }
      100% { opacity: 0; }
    }
  `
  document.head.appendChild(style)
  flashStyleReady = true
}

function removeFlashDom() {
  if (typeof document === 'undefined') return
  document.getElementById('yyfss-score-flash-overlay')?.remove()
}

function showFlashDom(score, type) {
  if (typeof document === 'undefined') return
  ensureFlashStyles()
  removeFlashDom()
  const sign = type === 'consume' ? '-' : '+'
  const el = document.createElement('div')
  el.id = 'yyfss-score-flash-overlay'
  el.className = `yyfss-score-flash-overlay ${type === 'consume' ? 'consume' : 'gain'}`
  el.innerHTML = `<span class="yyfss-score-flash-text">${sign}${Math.abs(score)}</span>`
  document.body.appendChild(el)
}

export function showPlayerScoreFlash(score, type = 'gain') {
  if (hideTimer) clearTimeout(hideTimer)
  const normalized = type === 'consume' ? 'consume' : 'gain'
  flashState.value = {
    visible: true,
    score: Math.abs(score),
    type: normalized
  }
  showFlashDom(score, normalized)
  hideTimer = setTimeout(() => {
    flashState.value.visible = false
    removeFlashDom()
  }, 2800)
}

export function usePlayerScoreFlash() {
  return flashState
}

export function getLastFlashRecordId() {
  return uni.getStorageSync('yyfss_last_flash_id') || 0
}

export function setLastFlashRecordId(id) {
  uni.setStorageSync('yyfss_last_flash_id', id)
}

export function resetFlashBaseline() {
  uni.removeStorageSync('yyfss_last_flash_id')
}
