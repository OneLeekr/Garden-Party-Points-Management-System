/**
 * H5 扫码工具（普通管理员在浏览器中扫码登记/核销）
 */

let ScannerClass = null

async function loadScanner() {
  if (ScannerClass) return ScannerClass
  const mod = await import('html5-qrcode')
  ScannerClass = mod.Html5QrcodeScanner
  return ScannerClass
}

/**
 * 打开扫码界面
 * @param {object} options
 * @param {boolean} options.continuous 连续扫码，成功后不关闭摄像头
 * @param {function} options.onScan 连续模式下每次扫到回调
 */
export function scanQRCode(options = {}) {
  const { continuous = false, onScan } = options

  // #ifdef H5
  return openScannerModal({ continuous, onScan })
  // #endif
  // #ifndef H5
  return new Promise((resolve, reject) => {
    uni.scanCode({
      success: (res) => {
        if (continuous && onScan) {
          onScan(res.result)
        } else {
          resolve(res.result)
        }
      },
      fail: (err) => reject(new Error(err.errMsg || '扫码取消'))
    })
  })
  // #endif
}

// #ifdef H5
function openScannerModal({ continuous, onScan }) {
  return new Promise((resolve, reject) => {
    const overlay = document.createElement('div')
    overlay.className = 'yyfss-scan-overlay'
    overlay.innerHTML = `
      <div class="yyfss-scan-box">
        <div class="yyfss-scan-header">
          <span class="yyfss-scan-title">扫描二维码</span>
          <button type="button" class="yyfss-scan-close" aria-label="关闭">✕</button>
        </div>
        <div id="yyfss-qr-reader" class="yyfss-qr-reader"></div>
        <p class="yyfss-scan-tip">将玩家二维码放入框内，或下方手动输入</p>
        <div class="yyfss-scan-manual">
          <input type="text" class="yyfss-scan-input" placeholder="粘贴二维码内容" />
          <button type="button" class="yyfss-scan-confirm">确认</button>
        </div>
        <p class="yyfss-scan-status"></p>
      </div>
    `

    const style = document.createElement('style')
    style.textContent = `
      .yyfss-scan-overlay {
        position: fixed; inset: 0; z-index: 20000;
        background: rgba(15,23,42,0.65);
        display: flex; align-items: center; justify-content: center;
        padding: 16px; box-sizing: border-box;
      }
      .yyfss-scan-box {
        width: 100%; max-width: 420px; background: #fff;
        border-radius: 16px; padding: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        position: relative;
      }
      .yyfss-scan-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 12px; position: relative; z-index: 30;
      }
      .yyfss-scan-title { font-size: 18px; font-weight: 700; color: #0f172a; }
      .yyfss-scan-close {
        width: 40px; height: 40px; border: none; background: #f1f5f9;
        border-radius: 50%; font-size: 20px; cursor: pointer; color: #64748b;
        flex-shrink: 0; position: relative; z-index: 31;
        -webkit-tap-highlight-color: transparent;
      }
      .yyfss-scan-close:hover, .yyfss-scan-close:active { background: #e2e8f0; color: #0f172a; }
      .yyfss-qr-reader { width: 100%; border-radius: 12px; overflow: hidden; min-height: 280px; }
      .yyfss-qr-reader video { border-radius: 12px; }
      .yyfss-scan-tip { font-size: 13px; color: #64748b; margin: 12px 0 8px; text-align: center; }
      .yyfss-scan-manual { display: flex; gap: 8px; }
      .yyfss-scan-input {
        flex: 1; height: 44px; padding: 0 12px; border: 1px solid #e8edf5;
        border-radius: 10px; font-size: 14px; box-sizing: border-box;
      }
      .yyfss-scan-confirm {
        height: 44px; padding: 0 20px; background: #2563eb; color: #fff;
        border: none; border-radius: 10px; font-weight: 600; cursor: pointer;
      }
      .yyfss-scan-status {
        margin: 10px 0 0; text-align: center; font-size: 13px; color: #059669; min-height: 18px;
      }
    `

    document.head.appendChild(style)
    document.body.appendChild(overlay)
    document.body.style.overflow = 'hidden'

    let scanner = null
    let done = false
    let lastScanAt = 0
    const statusEl = overlay.querySelector('.yyfss-scan-status')

    function cleanup() {
      if (done) return
      done = true
      try {
        if (scanner) scanner.clear().catch(() => {})
      } catch (_) {}
      overlay.remove()
      style.remove()
      document.body.style.overflow = ''
    }

    async function handleScan(text) {
      const value = String(text || '').trim()
      if (!value) return

      const now = Date.now()
      if (now - lastScanAt < 1500) return
      lastScanAt = now

      if (continuous && onScan) {
        try {
          await onScan(value)
          statusEl.textContent = '识别成功，继续扫描下一个'
          input.value = ''
        } catch (e) {
          statusEl.textContent = e.message || '识别失败'
        }
        return
      }

      cleanup()
      resolve(value)
    }

    function cancel() {
      cleanup()
      reject(new Error('扫码取消'))
    }

    const box = overlay.querySelector('.yyfss-scan-box')
    const closeBtn = overlay.querySelector('.yyfss-scan-close')
    const input = overlay.querySelector('.yyfss-scan-input')
    const confirmBtn = overlay.querySelector('.yyfss-scan-confirm')

    box.addEventListener('click', (e) => e.stopPropagation())
    closeBtn.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      cancel()
    })
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) cancel()
    })
    confirmBtn.addEventListener('click', () => handleScan(input.value))
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') handleScan(input.value)
    })

    loadScanner().then((Cls) => {
      if (done) return
      scanner = new Cls(
        'yyfss-qr-reader',
        {
          fps: 10,
          qrbox: { width: 250, height: 250 },
          rememberLastUsedCamera: true,
          aspectRatio: 1
        },
        false
      )
      scanner.render(
        (decodedText) => handleScan(decodedText),
        () => {}
      )
    }).catch(() => {})
  })
}
// #endif
