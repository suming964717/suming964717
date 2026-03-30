import { reactive, ref, computed } from 'vue'

// ===== 全局状态管理（轻量版Pinia替代）=====

const state = reactive({
  // 用户信息
  user: JSON.parse(localStorage.getItem('mm_user') || 'null'),

  // 当前活跃页面
  currentPage: 'home',
  previousPage: null,

  // 今日使用次数
  todayUsage: JSON.parse(localStorage.getItem('mm_usage') || '{"date":"","count":0}'),

  // 测量历史记录
  history: JSON.parse(localStorage.getItem('mm_history') || '[]'),

  // Toast 提示
  toast: null,
  toastTimer: null,

  // 弹窗状态
  showVipModal: false,
  showLoginModal: false,
  showMemberModal: false,
})

// ===== 用户相关 =====
const FREE_LIMIT = 10

const isLoggedIn = computed(() => !!state.user)
const isMember = computed(() => {
  if (!state.user) return false
  if (state.user.memberType === 4) return true // 永久会员
  if (!state.user.memberExpire) return false
  return new Date(state.user.memberExpire) > new Date()
})

function getTodayUsage() {
  const today = new Date().toDateString()
  if (state.todayUsage.date !== today) {
    state.todayUsage = { date: today, count: 0 }
    saveTodayUsage()
  }
  return state.todayUsage.count
}

function getRemainingUsage() {
  if (isMember.value) return Infinity
  return Math.max(0, FREE_LIMIT - getTodayUsage())
}

function incrementUsage() {
  const today = new Date().toDateString()
  if (state.todayUsage.date !== today) {
    state.todayUsage = { date: today, count: 0 }
  }
  state.todayUsage.count++
  saveTodayUsage()
}

function canUse() {
  if (isMember.value) return true
  return getRemainingUsage() > 0
}

function saveTodayUsage() {
  localStorage.setItem('mm_usage', JSON.stringify(state.todayUsage))
}

// ===== 登录/注册 =====
function login(data) {
  state.user = {
    id: Date.now(),
    nickname: data.nickname || '测量大师',
    mobile: data.mobile || '',
    avatar: data.avatar || null,
    isMember: data.isMember || false,
    memberType: data.memberType || null,
    memberExpire: data.memberExpire || null,
    createdAt: new Date().toISOString()
  }
  localStorage.setItem('mm_user', JSON.stringify(state.user))
}

function logout() {
  state.user = null
  localStorage.removeItem('mm_user')
}

function updateUser(updates) {
  if (state.user) {
    Object.assign(state.user, updates)
    localStorage.setItem('mm_user', JSON.stringify(state.user))
  }
}

function activateMember(type) {
  const daysMap = { 1: 30, 2: 90, 3: 365, 4: 36500 }
  const days = daysMap[type]
  const expire = new Date()
  expire.setDate(expire.getDate() + days)

  updateUser({
    isMember: true,
    memberType: type,
    memberExpire: expire.toISOString()
  })
  showToast('🎉 会员开通成功！')
}

// ===== 历史记录 =====
function saveHistory(record) {
  if (!isMember.value) return
  state.history.unshift({
    id: Date.now(),
    ...record,
    time: new Date().toISOString()
  })
  // 只保留最近100条
  if (state.history.length > 100) {
    state.history = state.history.slice(0, 100)
  }
  localStorage.setItem('mm_history', JSON.stringify(state.history))
}

function deleteHistory(id) {
  state.history = state.history.filter(h => h.id !== id)
  localStorage.setItem('mm_history', JSON.stringify(state.history))
}

function clearHistory() {
  state.history = []
  localStorage.removeItem('mm_history')
}

// ===== 导航 =====
function navigate(page) {
  state.previousPage = state.currentPage
  state.currentPage = page
}

function goBack() {
  if (state.previousPage) {
    state.currentPage = state.previousPage
    state.previousPage = null
  } else {
    state.currentPage = 'home'
  }
}

// ===== Toast =====
function showToast(message, duration = 2000) {
  if (state.toastTimer) clearTimeout(state.toastTimer)
  state.toast = message
  state.toastTimer = setTimeout(() => {
    state.toast = null
  }, duration)
}

// ===== 会员弹窗 =====
function requireMember(feature) {
  if (isMember.value) return true
  state.showVipModal = true
  showToast(`💎 ${feature} 是会员专属功能`)
  return false
}

export default {
  state,
  isLoggedIn,
  isMember,
  FREE_LIMIT,
  getTodayUsage,
  getRemainingUsage,
  incrementUsage,
  canUse,
  login,
  logout,
  updateUser,
  activateMember,
  saveHistory,
  deleteHistory,
  clearHistory,
  navigate,
  goBack,
  showToast,
  requireMember,
}
