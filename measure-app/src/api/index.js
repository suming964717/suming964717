// ===== API 配置 =====
// 开发时改为你的后端地址，生产时改为真实域名
const BASE_URL = import.meta.env.VITE_API_BASE || 'http://localhost:8000'

// ===== 请求封装 =====
async function request(method, path, data = null) {
  const token = localStorage.getItem('mm_token')
  const headers = { 'Content-Type': 'application/json' }
  if (token) headers['Authorization'] = `Bearer ${token}`

  const opts = { method, headers }

  if (data) {
    if (method === 'GET') {
      const qs = new URLSearchParams(data).toString()
      path = `${path}?${qs}`
    } else {
      opts.body = JSON.stringify(data)
    }
  }

  try {
    const res = await fetch(`${BASE_URL}/${path}`, opts)
    const json = await res.json()

    if (json.code === 401) {
      // Token过期，清除登录状态
      localStorage.removeItem('mm_token')
      localStorage.removeItem('mm_user')
      window.location.reload()
      return null
    }

    return json
  } catch (e) {
    console.error('API请求失败:', e)
    return { code: 0, msg: '网络异常，请检查网络连接', data: null }
  }
}

const get    = (path, data) => request('GET', path, data)
const post   = (path, data) => request('POST', path, data)
const del    = (path, data) => request('DELETE', path, data)

// ===== Auth API =====
export const authApi = {
  sendSms: (mobile)       => post('api/auth/sendSms', { mobile }),
  login:   (mobile, code) => post('api/auth/login', { mobile, code }),
}

// ===== User API =====
export const userApi = {
  info:   ()     => get('api/user/info'),
  update: (data) => post('api/user/update', data),
  use:    (type) => post('api/user/use', { type }),
}

// ===== Member API =====
export const memberApi = {
  plans:  () => get('api/member/plans'),
  status: () => get('api/member/status'),
}

// ===== Payment API =====
export const paymentApi = {
  create: (planId, payType) => post('api/payment/create', { plan_id: planId, pay_type: payType }),
  status: (orderNo)        => get('api/payment/status', { order_no: orderNo }),
}

// ===== History API =====
export const historyApi = {
  list:   (params) => get('api/history/list', params),
  save:   (data)   => post('api/history/save', data),
  delete: (id)     => del('api/history/delete', { id }),
  clear:  ()       => del('api/history/clear'),
}
