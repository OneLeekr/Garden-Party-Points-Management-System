import { config } from '@/config/index.js'
import { getToken, getCsrfToken } from '@/utils/auth.js'
import { handleAuthFailure, isAuthLocked } from '@/utils/authGuard.js'
import { hostAccessMessage, isHostProtectionResponse } from '@/utils/hostAccess.js'

function parseErrorMessage(res) {
  const data = res.data
  if (typeof data === 'string') {
    if (isHostProtectionResponse(data)) {
      return hostAccessMessage('host_protection')
    }
    if (data.includes('<html')) {
      return hostAccessMessage('host_protection')
    }
  }
  if (data && typeof data === 'object' && data.message) {
    return data.message
  }
  if (res.statusCode === 404) {
    return '接口不存在(404)'
  }
  if (res.statusCode >= 500) {
    return '服务器错误(' + res.statusCode + ')'
  }
  return '请求失败'
}

function handleHttpAuth(res, data) {
  const msg = data?.message || ''
  if (res.statusCode === 401) {
    handleAuthFailure(msg || '未登录或Token已过期', { force: true })
    return true
  }
  if (res.statusCode === 403) {
    if (handleAuthFailure(msg)) return true
  }
  return false
}

function appendAccessToken(options) {
  const token = getToken()
  if (!token) return options

  const method = (options.method || 'GET').toUpperCase()
  if (method === 'GET') {
    const sep = options.url.includes('?') ? '&' : '?'
    if (!options.url.includes('access_token=')) {
      options.url += sep + 'access_token=' + encodeURIComponent(token)
    }
  } else if (options.data && typeof options.data === 'object' && !Array.isArray(options.data)) {
    if (!options.data.access_token) {
      options.data = { ...options.data, access_token: token }
    }
  } else {
    options.data = { access_token: token, ...(options.data || {}) }
  }
  return options
}

function request(options) {
  if (isAuthLocked()) {
    return Promise.reject(new Error('AUTH_LOCKED'))
  }

  options = appendAccessToken(options)

  return new Promise((resolve, reject) => {
    if (isAuthLocked()) {
      reject(new Error('AUTH_LOCKED'))
      return
    }

    const header = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.header || {})
    }
    const token = getToken()
    if (token) {
      header.Authorization = `Bearer ${token}`
    }
    const csrf = getCsrfToken()
    if (csrf) {
      header['X-CSRF-Token'] = csrf
    }

    const url = (config.baseURL || '') + options.url

    uni.request({
      url,
      method: options.method || 'GET',
      data: options.data,
      header,
      timeout: config.timeout,
      withCredentials: true,
      success(res) {
        if (isAuthLocked()) {
          reject(new Error('AUTH_LOCKED'))
          return
        }

        let data = res.data
        if (typeof data === 'string') {
          try {
            data = JSON.parse(data)
          } catch {
            reject(new Error(parseErrorMessage(res)))
            return
          }
        }

        if (handleHttpAuth(res, data)) {
          reject(new Error(data?.message || '登录已失效'))
          return
        }

        if (data && (data.code === 200 || data.code === 201)) {
          resolve(data)
        } else {
          const msg = parseErrorMessage({ ...res, data })
          if (data?.code === 401) {
            handleAuthFailure(msg, { force: true })
          } else if (data?.code === 403) {
            handleAuthFailure(msg)
          }
          reject(new Error(msg))
        }
      },
      fail(err) {
        const msg = err?.errMsg || ''
        if (msg.includes('timeout') || msg.includes('超时')) {
          reject(new Error(hostAccessMessage('timeout')))
          return
        }
        reject(new Error(msg || hostAccessMessage('network')))
      }
    })
  })
}

export function get(url, params) {
  let query = ''
  if (params) {
    const parts = Object.entries(params)
      .filter(([, v]) => v !== undefined && v !== null && v !== '')
      .map(([k, v]) => `${encodeURIComponent(k)}=${encodeURIComponent(v)}`)
    if (parts.length) query = '?' + parts.join('&')
  }
  return request({ url: url + query, method: 'GET' })
}

export function post(url, data) {
  return request({ url, method: 'POST', data })
}

export function put(url, data) {
  return request({ url, method: 'PUT', data })
}

export function del(url, data) {
  return request({ url, method: 'DELETE', data })
}

export function upload(url, filePath, name = 'file') {
  if (isAuthLocked()) {
    return Promise.reject(new Error('AUTH_LOCKED'))
  }
  return new Promise((resolve, reject) => {
    const token = getToken()
    uni.uploadFile({
      url: (config.baseURL || '') + url + (token ? '?access_token=' + encodeURIComponent(token) : ''),
      filePath,
      name,
      header: {
        Authorization: `Bearer ${token}`,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      withCredentials: true,
      success(res) {
        if (isAuthLocked()) {
          reject(new Error('AUTH_LOCKED'))
          return
        }
        try {
          const data = JSON.parse(res.data)
          if (data.code === 200) resolve(data)
          else {
            if (data.code === 401) handleAuthFailure(data.message, { force: true })
            else if (data.code === 403) handleAuthFailure(data.message)
            reject(new Error(data.message))
          }
        } catch {
          reject(new Error('上传失败'))
        }
      },
      fail: reject
    })
  })
}

export default { get, post, put, del, upload, request }
