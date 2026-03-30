import { reactive, computed } from 'vue'
import { authApi, userApi, memberApi, historyApi } from '../api/index.js'

// ===== 全局状态 =====
const state = reactive({
  user: JSON.parse(localStorage.getItem('mm_user') || 'null'),
  currentPage: 'home',
  previousPage: null,
  history: [],
  plans: [],
  toast: null,
  toastTimer: null,
  showVipModal: false,
  showLoginModal: false,
  showMemberModal: false,
  todayUsage: 0,
  freeLimit: 10,
  remaining: 10,
})

const FREE_LIMIT  = 10
const isLoggedIn  = computed(() => !!state.user)
const isMember    = computed(() => {
  if (!state.user) return false
  if (state.user.member_type === 4) return true
  if (!state.user.member_expire) return false
  return new Date(state.user.member_expire) > new Date()
})

// ===== 初始化：从后端同步用户状态 =====
async function initUser() {
  const token = localStorage.getItem('mm_token')
  if (!token) return
  const res = await userApi.info()
  if (res && res.code === 200) {
    state.user       = res.data
    state.todayUsage = res.data.today_usage || 0
    state.freeLimit  = res.data.free_limit  || 10
    state.remaining  = res.data.remaining   || 10
    localStorage.setItem('mm_user', JSON.stringify(res.data))
  } else {
    logout()
  }
}

// ===== 使用次数 =====
function getTodayUsage()     { return state.todayUsage }
function getRemainingUsage() { return state.remaining }

async function incrementUsage(type = '') {
  if (isMember.value) return true
  const res = await userApi.use(type)
  if (res && res.code === 200) {
    state.todayUsage = res.data.today_usage
    state.remaining  = res.data.remaining
    return true
  }
  if (res && res.code === 403) {
    state.showVipModal = true
    showToast('今日免费次数已用完，请升级会员')
    return false
  }
  return false
}

function canUse() {
  if (isMember.value) return true
  return state.remaining > 0
}

// ===== 发送短信 =====
async function sendSms(mobile) {
  const res = await authApi.sendSms(mobile)
  if (res && res.code === 200) {
    showToast(res.msg || '验证码已发送')
    return { success: true, debugCode: res.data?.debug_code }
  }
  showToast(res?.msg || '发送失败')
  return { success: false }
}

// ===== 登录 =====
async function login(mobile, code) {
  const res = await authApi.login(mobile, code)
  if (res && res.code === 200) {
    const { token, user } = res.data
    localStorage.setItem('mm_token', token)
    localStorage.setItem('mm_user', JSON.stringify(user))
    state.user = user
    await initUser()
    showToast('🎉 登录成功！')
    return true
  }
  showToast(res?.msg || '登录失败')
  return false
}

// ===== 登出 =====
function logout() {
  state.user       = null
  state.todayUsage = 0
  state.remaining  = 10
  state.history    = []
  localStorage.removeItem('mm_token')
  localStorage.removeItem('mm_user')
}

// ===== 会员套餐 =====
async function loadPlans() {
  const res = await memberApi.plans()
  if (res && res.code === 200) state.plans = res.data
}

function activateMember() {
  initUser() // 支付后刷新用户状态
}

// ===== 历史记录 =====
async function loadHistory() {
  if (!isMember.value) return
  const res = await historyApi.list({ page: 1, size: 100 })
  if (res && res.code === 200) state.history = res.data.list || []
}

async function saveHistory(record) {
  if (!isMember.value) return
  await historyApi.save(record)
  await loadHistory()
}

async function deleteHistory(id) {
  await historyApi.delete(id)
  state.history = state.history.filter(h => h.id !== id)
}

async function clearHistory() {
  await historyApi.clear()
  state.history = []
}

// ===== 导航 =====
function navigate(page) {
  state.previousPage = state.currentPage
  state.currentPage  = page
}

function goBack() {
  state.currentPage  = state.previousPage || 'home'
  state.previousPage = null
}

// ===== Toast =====
function showToast(message, duration = 2000) {
  if (state.toastTimer) clearTimeout(state.toastTimer)
  state.toast = message
  state.toastTimer = setTimeout(() => { state.toast = null }, duration)
}

// ===== 会员拦截 =====
function requireMember(feature) {
  if (isMember.value) return true
  state.showVipModal = true
  showToast(`💎 ${feature} 是会员专属功能`)
  return false
}

// 启动时初始化
initUser()
loadPlans()

export default {
  state, isLoggedIn, isMember, FREE_LIMIT,
  getTodayUsage, getRemainingUsage, incrementUsage, canUse,
  sendSms, login, logout, activateMember,
  loadPlans, saveHistory, deleteHistory, clearHistory, loadHistory,
  navigate, goBack, showToast, requireMember, initUser,
}
