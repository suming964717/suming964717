<template>
  <div class="user-page page">
    <!-- 顶部背景 -->
    <div class="user-header-bg">
      <div class="header-orb"></div>
    </div>

    <!-- 用户信息卡 -->
    <div class="user-profile-card">
      <div class="avatar-area">
        <div class="avatar-ring" :class="{ vip: store.isMember.value }">
          <div class="avatar-inner">
            <span class="avatar-emoji">{{ store.state.user ? '😊' : '👤' }}</span>
          </div>
        </div>
        <div class="avatar-badge" v-if="store.isMember.value">
          <span>👑</span>
          <span>VIP</span>
        </div>
      </div>

      <div class="profile-info">
        <div class="profile-name">
          {{ store.state.user?.nickname || '游客用户' }}
          <span v-if="store.isMember.value" class="vip-tag gradient-text-gold">VIP</span>
        </div>
        <div class="profile-sub" v-if="store.state.user?.mobile">
          📱 {{ maskMobile(store.state.user.mobile) }}
        </div>
        <div class="profile-sub" v-else>
          点击登录享受完整功能
        </div>
      </div>

      <button
        v-if="!store.state.user"
        class="btn btn-primary btn-sm"
        @click="store.state.showLoginModal = true"
      >
        登录
      </button>
      <button
        v-else
        class="edit-profile-btn"
        @click="showEditProfile = true"
      >
        ✏️
      </button>
    </div>

    <!-- 会员状态 -->
    <div v-if="store.isMember.value" class="member-status-card">
      <div class="member-status-left">
        <span class="member-icon">👑</span>
        <div>
          <div class="member-type">{{ memberTypeName }}</div>
          <div class="member-expire">到期：{{ formatExpire }}</div>
        </div>
      </div>
      <button class="renew-btn" @click="store.state.showMemberModal = true">
        续费
      </button>
    </div>

    <div v-else class="vip-promo-card" @click="store.state.showMemberModal = true">
      <div class="promo-left">
        <div class="promo-title">🚀 升级会员</div>
        <div class="promo-perks">
          <span v-for="perk in promoPerks" :key="perk">{{ perk }}</span>
        </div>
      </div>
      <div class="promo-price">
        <div class="price-from">仅需</div>
        <div class="price-main">¥12</div>
        <div class="price-unit">/月起</div>
      </div>
    </div>

    <!-- 今日使用 -->
    <div class="usage-card">
      <div class="usage-header">
        <span>今日使用</span>
        <span class="usage-count">{{ store.getTodayUsage() }}/{{ store.isMember.value ? '∞' : store.FREE_LIMIT }}</span>
      </div>
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: Math.min(100, store.getTodayUsage() / store.FREE_LIMIT * 100) + '%' }"
        ></div>
      </div>
      <div class="usage-tip" v-if="!store.isMember.value">
        剩余 {{ store.getRemainingUsage() }} 次免费使用机会
      </div>
    </div>

    <!-- 功能菜单 -->
    <div class="menu-section">
      <div class="menu-group">
        <div class="menu-item" @click="handleHistoryMenu">
          <div class="menu-item-left">
            <span class="menu-icon">📋</span>
            <span class="menu-label">测量历史</span>
          </div>
          <div class="menu-item-right">
            <span class="menu-count" v-if="store.state.history.length">{{ store.state.history.length }}</span>
            <span v-if="!store.isMember.value" class="menu-vip-badge">👑</span>
            <span class="menu-arrow">›</span>
          </div>
        </div>

        <div class="menu-item" @click="store.navigate('history')">
          <div class="menu-item-left">
            <span class="menu-icon">📊</span>
            <span class="menu-label">统计报告</span>
          </div>
          <div class="menu-item-right">
            <span v-if="!store.isMember.value" class="menu-vip-badge">👑</span>
            <span class="menu-arrow">›</span>
          </div>
        </div>

        <div class="menu-item" @click="store.state.showMemberModal = true">
          <div class="menu-item-left">
            <span class="menu-icon">💎</span>
            <span class="menu-label">会员中心</span>
          </div>
          <div class="menu-item-right">
            <span class="menu-tag">推荐</span>
            <span class="menu-arrow">›</span>
          </div>
        </div>
      </div>

      <div class="menu-group">
        <div class="menu-item" @click="showAbout = true">
          <div class="menu-item-left">
            <span class="menu-icon">ℹ️</span>
            <span class="menu-label">关于应用</span>
          </div>
          <div class="menu-item-right">
            <span class="menu-version">v1.0.0</span>
            <span class="menu-arrow">›</span>
          </div>
        </div>

        <div class="menu-item" @click="showFeedback = true">
          <div class="menu-item-left">
            <span class="menu-icon">💬</span>
            <span class="menu-label">意见反馈</span>
          </div>
          <div class="menu-item-right">
            <span class="menu-arrow">›</span>
          </div>
        </div>

        <div class="menu-item danger" v-if="store.state.user" @click="confirmLogout">
          <div class="menu-item-left">
            <span class="menu-icon">🚪</span>
            <span class="menu-label" style="color: var(--danger)">退出登录</span>
          </div>
          <div class="menu-item-right">
            <span class="menu-arrow">›</span>
          </div>
        </div>
      </div>
    </div>

    <div style="height: 100px;"></div>

    <!-- 登录弹窗 -->
    <div class="modal-overlay" v-if="store.state.showLoginModal" @click.self="store.state.showLoginModal = false">
      <div class="modal-panel">
        <div class="modal-handle"></div>
        <div class="login-header">
          <div class="login-logo">📐</div>
          <h2>欢迎使用 MeasureMaster</h2>
          <p>登录后享受完整测量体验</p>
        </div>

        <div class="login-form" v-if="!showVerify">
          <div class="input-group">
            <span class="input-prefix">🇨🇳 +86</span>
            <input
              v-model="loginMobile"
              type="tel"
              placeholder="输入手机号"
              class="login-input"
              maxlength="11"
            >
          </div>
          <button class="btn btn-primary" style="width:100%" @click="sendCode" :disabled="codeSent">
            {{ codeSent ? `重新发送 (${countdown}s)` : '获取验证码' }}
          </button>
        </div>

        <div class="login-form" v-else>
          <div class="verify-hint">验证码已发送至 {{ loginMobile }}</div>
          <div class="code-input-row">
            <input
              v-for="(_, i) in [0,1,2,3,4,5]"
              :key="i"
              type="number"
              maxlength="1"
              class="code-digit"
              :ref="el => codeInputs[i] = el"
              v-model="codeDigits[i]"
              @input="onCodeInput(i)"
              @keydown.backspace="onCodeBack(i)"
            >
          </div>
          <button class="btn btn-primary" style="width:100%" @click="verifyCode">
            确认登录
          </button>
          <div class="resend-row">
            <span @click="sendCode" :class="{ disabled: codeSent }">
              {{ codeSent ? `${countdown}s 后重新发送` : '重新发送' }}
            </span>
          </div>
        </div>

        <div class="login-divider">
          <span>— 或 —</span>
        </div>

        <button class="wechat-btn" @click="wechatLogin">
          <span>💚</span> 微信一键登录
        </button>

        <button class="guest-btn" @click="guestLogin">
          以游客身份继续
        </button>
      </div>
    </div>

    <!-- 历史记录内嵌展示 -->
    <div class="modal-overlay" v-if="showHistory" @click.self="showHistory = false">
      <div class="modal-panel" style="max-height: 80vh;">
        <div class="modal-handle"></div>
        <div class="history-modal-header">
          <h3>测量历史</h3>
          <button class="clear-btn" @click="clearHistory" v-if="store.state.history.length">清空</button>
        </div>
        <div v-if="store.state.history.length === 0" class="empty-history">
          <span>📋</span>
          <p>暂无测量记录</p>
        </div>
        <div v-else class="history-scroll">
          <div
            v-for="item in store.state.history"
            :key="item.id"
            class="h-item"
          >
            <div class="h-icon">{{ typeIcon(item.type) }}</div>
            <div class="h-info">
              <span class="h-value">{{ item.value }} <span class="h-unit">{{ item.unit }}</span></span>
              <span class="h-time">{{ formatTime(item.time) }}</span>
            </div>
            <button class="h-del" @click="store.deleteHistory(item.id)">✕</button>
          </div>
        </div>
      </div>
    </div>

    <!-- VIP弹窗 -->
    <VipModal v-if="store.state.showVipModal" @close="store.state.showVipModal = false" />
    <MemberModal v-if="store.state.showMemberModal" @close="store.state.showMemberModal = false" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import store from '../store/index.js'
import VipModal from '../components/VipModal.vue'
import MemberModal from '../components/MemberModal.vue'

const showEditProfile = ref(false)
const showHistory = ref(false)
const showAbout = ref(false)
const showFeedback = ref(false)
const loginMobile = ref('')
const showVerify = ref(false)
const codeSent = ref(false)
const countdown = ref(60)
const codeDigits = ref(['', '', '', '', '', ''])
const codeInputs = ref([])

let countdownTimer = null

const promoPerks = ['无限测量', '历史记录', '去广告', '数据导出']

const memberTypeName = computed(() => {
  const typeMap = { 1: '月度会员', 2: '季度会员', 3: '年度会员', 4: '永久会员' }
  return typeMap[store.state.user?.memberType] || '会员'
})

const formatExpire = computed(() => {
  const exp = store.state.user?.memberExpire
  if (!exp) return '永久'
  if (store.state.user?.memberType === 4) return '永久有效'
  return new Date(exp).toLocaleDateString('zh-CN')
})

function maskMobile(mobile) {
  return mobile.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

function handleHistoryMenu() {
  if (!store.requireMember('测量历史')) return
  showHistory.value = true
}

function clearHistory() {
  store.clearHistory()
  showHistory.value = false
  store.showToast('已清空历史记录')
}

function confirmLogout() {
  if (confirm('确认退出登录？')) {
    store.logout()
    store.showToast('已退出登录')
  }
}

function sendCode() {
  if (!loginMobile.value || loginMobile.value.length !== 11) {
    store.showToast('请输入正确的手机号')
    return
  }
  showVerify.value = true
  codeSent.value = true
  countdown.value = 60
  countdownTimer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(countdownTimer)
      codeSent.value = false
    }
  }, 1000)
  store.showToast('验证码已发送（演示：123456）')
}

function onCodeInput(index) {
  const val = codeDigits.value[index]
  if (val && val.length > 1) {
    codeDigits.value[index] = val[0]
  }
  if (val && index < 5) {
    codeInputs.value[index + 1]?.focus()
  }
}

function onCodeBack(index) {
  if (!codeDigits.value[index] && index > 0) {
    codeInputs.value[index - 1]?.focus()
  }
}

function verifyCode() {
  const code = codeDigits.value.join('')
  if (code === '123456' || code.length === 6) {
    store.login({ mobile: loginMobile.value, nickname: `用户${loginMobile.value.slice(-4)}` })
    store.state.showLoginModal = false
    showVerify.value = false
    codeDigits.value = ['', '', '', '', '', '']
    store.showToast('🎉 登录成功！')
  } else {
    store.showToast('验证码错误，请重试')
  }
}

function wechatLogin() {
  store.login({ nickname: '微信用户', avatar: null })
  store.state.showLoginModal = false
  store.showToast('🎉 微信登录成功！')
}

function guestLogin() {
  store.state.showLoginModal = false
  store.showToast('已以游客模式使用')
}

function typeIcon(type) {
  return { ruler: '📏', level: '🫧', decibel: '🎙️' }[type] || '📊'
}

function formatTime(iso) {
  const d = new Date(iso)
  return d.toLocaleString('zh-CN', { month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.user-page {
  background: var(--bg-dark);
  overflow-y: auto;
  padding-bottom: 100px;
}

/* 顶部背景 */
.user-header-bg {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 200px;
  overflow: hidden;
  pointer-events: none;
}

.header-orb {
  position: absolute;
  top: -80px;
  left: 50%;
  transform: translateX(-50%);
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(108,99,255,0.2), transparent 70%);
  filter: blur(40px);
}

/* 用户卡片 */
.user-profile-card {
  position: relative;
  z-index: 10;
  margin: 20px 20px 16px;
  padding: 20px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-xl);
  display: flex;
  align-items: center;
  gap: 16px;
  backdrop-filter: blur(20px);
}

.avatar-area {
  position: relative;
  flex-shrink: 0;
}

.avatar-ring {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  padding: 2px;
  background: rgba(255,255,255,0.15);
}

.avatar-ring.vip {
  background: linear-gradient(135deg, #FFD700, #FFA500, #FF6584);
  animation: spin 4s linear infinite;
}

.avatar-inner {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: var(--bg-card2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
}

.avatar-badge {
  position: absolute;
  bottom: -4px;
  right: -4px;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  border-radius: var(--radius-full);
  padding: 2px 6px;
  font-size: 9px;
  font-weight: 800;
  color: #1a0f00;
  display: flex;
  align-items: center;
  gap: 2px;
  border: 2px solid var(--bg-dark);
}

.profile-info {
  flex: 1;
}

.profile-name {
  font-size: 18px;
  font-weight: 800;
  color: white;
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.vip-tag {
  font-size: 12px;
  font-weight: 700;
}

.profile-sub {
  font-size: 13px;
  color: var(--text-muted);
}

.edit-profile-btn {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.07);
  border: none;
  font-size: 16px;
  cursor: pointer;
  transition: var(--transition);
}

/* 会员状态 */
.member-status-card {
  margin: 0 20px 16px;
  padding: 14px 16px;
  background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,165,0,0.08));
  border: 1px solid rgba(255,215,0,0.25);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.member-status-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.member-icon { font-size: 24px; }

.member-type {
  font-size: 14px;
  font-weight: 700;
  color: #FFD700;
}

.member-expire {
  font-size: 11px;
  color: rgba(255,215,0,0.6);
  margin-top: 2px;
}

.renew-btn {
  padding: 7px 16px;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  border: none;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
  color: #1a0f00;
  cursor: pointer;
}

/* VIP推广 */
.vip-promo-card {
  margin: 0 20px 16px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(108,99,255,0.15), rgba(67,233,123,0.08));
  border: 1px solid rgba(108,99,255,0.25);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: var(--transition);
}

.vip-promo-card:active {
  transform: scale(0.99);
}

.promo-title {
  font-size: 15px;
  font-weight: 800;
  margin-bottom: 6px;
}

.promo-perks {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.promo-perks span {
  font-size: 11px;
  color: var(--text-secondary);
  background: rgba(255,255,255,0.07);
  padding: 2px 8px;
  border-radius: var(--radius-full);
}

.promo-price {
  text-align: center;
  flex-shrink: 0;
}

.price-from { font-size: 11px; color: var(--text-muted); }

.price-main {
  font-size: 28px;
  font-weight: 900;
  background: linear-gradient(135deg, var(--primary-light), var(--accent2));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.price-unit { font-size: 11px; color: var(--text-muted); }

/* 使用量 */
.usage-card {
  margin: 0 20px 16px;
  padding: 16px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-lg);
}

.usage-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  font-size: 14px;
  font-weight: 600;
}

.usage-count {
  color: var(--primary-light);
  font-weight: 800;
}

.usage-tip {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 8px;
}

/* 菜单 */
.menu-section {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.menu-group {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 16px;
  cursor: pointer;
  transition: var(--transition);
  border-bottom: 1px solid rgba(255,255,255,0.05);
}

.menu-item:last-child {
  border-bottom: none;
}

.menu-item:active {
  background: rgba(255,255,255,0.05);
}

.menu-item-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.menu-icon { font-size: 20px; }
.menu-label { font-size: 15px; font-weight: 600; color: white; }

.menu-item-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.menu-count {
  font-size: 12px;
  padding: 2px 8px;
  background: var(--primary);
  border-radius: var(--radius-full);
  font-weight: 700;
}

.menu-vip-badge {
  font-size: 14px;
}

.menu-arrow {
  font-size: 20px;
  color: var(--text-muted);
}

.menu-version {
  font-size: 12px;
  color: var(--text-muted);
}

.menu-tag {
  font-size: 10px;
  padding: 2px 7px;
  background: linear-gradient(135deg, var(--primary), var(--accent2));
  border-radius: var(--radius-full);
  font-weight: 700;
}

/* 登录弹窗 */
.login-header {
  text-align: center;
  margin-bottom: 24px;
}

.login-logo {
  font-size: 40px;
  margin-bottom: 10px;
}

.login-header h2 {
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 6px;
}

.login-header p {
  font-size: 13px;
  color: var(--text-secondary);
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.input-group {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.input-prefix {
  padding: 14px 14px;
  font-size: 14px;
  color: var(--text-secondary);
  border-right: 1px solid rgba(255,255,255,0.08);
  flex-shrink: 0;
}

.login-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 14px;
  color: white;
  font-size: 16px;
  outline: none;
}

.login-input::placeholder {
  color: var(--text-muted);
}

.verify-hint {
  font-size: 13px;
  color: var(--text-secondary);
  text-align: center;
}

.code-input-row {
  display: flex;
  gap: 8px;
  justify-content: center;
}

.code-digit {
  width: 46px;
  height: 52px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: var(--radius-md);
  color: white;
  font-size: 22px;
  font-weight: 800;
  text-align: center;
  outline: none;
  -moz-appearance: textfield;
}

.code-digit::-webkit-inner-spin-button,
.code-digit::-webkit-outer-spin-button {
  -webkit-appearance: none;
}

.code-digit:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 2px rgba(108,99,255,0.2);
}

.resend-row {
  text-align: center;
  font-size: 13px;
  color: var(--primary-light);
  cursor: pointer;
}

.resend-row .disabled {
  color: var(--text-muted);
  cursor: default;
}

.login-divider {
  text-align: center;
  font-size: 12px;
  color: var(--text-muted);
  margin: 12px 0;
}

.wechat-btn {
  width: 100%;
  padding: 14px;
  background: rgba(7,193,96,0.15);
  border: 1px solid rgba(7,193,96,0.3);
  border-radius: var(--radius-md);
  color: #07c160;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  margin-bottom: 10px;
  transition: var(--transition);
}

.wechat-btn:active {
  background: rgba(7,193,96,0.25);
}

.guest-btn {
  width: 100%;
  padding: 12px;
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 13px;
  cursor: pointer;
  text-decoration: underline;
}

/* 历史记录弹窗 */
.history-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.history-modal-header h3 {
  font-size: 18px;
  font-weight: 800;
}

.clear-btn {
  font-size: 13px;
  color: var(--danger);
  background: none;
  border: none;
  cursor: pointer;
}

.empty-history {
  text-align: center;
  padding: 30px;
  color: var(--text-muted);
}

.empty-history span { font-size: 36px; display: block; margin-bottom: 10px; }

.history-scroll {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 50vh;
  overflow-y: auto;
}

.h-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: rgba(255,255,255,0.04);
  border-radius: var(--radius-md);
}

.h-icon { font-size: 20px; }

.h-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.h-value { font-size: 15px; font-weight: 700; }
.h-unit { font-size: 12px; color: var(--text-secondary); font-weight: 400; }
.h-time { font-size: 11px; color: var(--text-muted); }

.h-del {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(255,71,87,0.15);
  border: none;
  color: var(--danger);
  font-size: 12px;
  cursor: pointer;
  transition: var(--transition);
}

.h-del:active { background: rgba(255,71,87,0.3); }
</style>
