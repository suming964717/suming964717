<template>
  <div class="home-page page">
    <!-- 背景装饰 -->
    <div class="bg-decoration">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
    </div>

    <!-- 顶部栏 -->
    <header class="home-header">
      <div class="header-left">
        <div class="logo-icon">📐</div>
        <div class="logo-text">
          <span class="logo-name">MeasureMaster</span>
          <span class="logo-sub">测量大师</span>
        </div>
      </div>
      <div class="header-right">
        <div class="usage-badge" @click="store.navigate('user')">
          <span class="usage-icon">⚡</span>
          <span v-if="store.isMember.value">无限次</span>
          <span v-else>{{ remaining }}/{{ store.FREE_LIMIT }}</span>
        </div>
        <div class="avatar-btn" @click="store.navigate('user')">
          <template v-if="store.state.user?.avatar">
            <img :src="store.state.user.avatar" alt="avatar">
          </template>
          <template v-else>
            <span class="avatar-emoji">{{ store.state.user ? '👤' : '🔑' }}</span>
          </template>
          <span v-if="store.isMember.value" class="vip-dot"></span>
        </div>
      </div>
    </header>

    <!-- 会员横幅 -->
    <div v-if="!store.isMember.value" class="vip-banner" @click="store.state.showMemberModal = true">
      <div class="vip-banner-left">
        <span class="vip-crown">👑</span>
        <div>
          <div class="vip-title">解锁高级功能</div>
          <div class="vip-desc">无限测量 · 历史记录 · 数据导出</div>
        </div>
      </div>
      <div class="vip-arrow">
        <span>立即开通</span>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
          <path d="M9 18l6-6-6-6"/>
        </svg>
      </div>
    </div>

    <!-- 主工具卡片 -->
    <div class="tools-section">
      <div class="section-title">测量工具</div>

      <!-- 三大工具 -->
      <div class="tools-grid">
        <!-- 尺子 -->
        <div class="tool-card tool-ruler" @click="openTool('ruler')">
          <div class="tool-card-bg"></div>
          <div class="tool-icon-wrap">
            <div class="tool-icon">📏</div>
          </div>
          <div class="tool-info">
            <div class="tool-name">屏幕尺子</div>
            <div class="tool-desc">精准测量物体长度</div>
            <div class="tool-tags">
              <span class="tag">厘米</span>
              <span class="tag">英寸</span>
              <span class="tag free-tag">免费</span>
            </div>
          </div>
          <div class="tool-arrow">›</div>
        </div>

        <!-- 水平仪 -->
        <div class="tool-card tool-level" @click="openTool('level')">
          <div class="tool-card-bg"></div>
          <div class="tool-icon-wrap">
            <div class="tool-icon">🫧</div>
          </div>
          <div class="tool-info">
            <div class="tool-name">水平仪</div>
            <div class="tool-desc">检测设备倾斜角度</div>
            <div class="tool-tags">
              <span class="tag">水平</span>
              <span class="tag">垂直</span>
              <span class="tag free-tag">免费</span>
            </div>
          </div>
          <div class="tool-arrow">›</div>
        </div>

        <!-- 分贝仪 -->
        <div class="tool-card tool-decibel" @click="openTool('decibel')">
          <div class="tool-card-bg"></div>
          <div class="tool-icon-wrap">
            <div class="tool-icon">🎙️</div>
          </div>
          <div class="tool-info">
            <div class="tool-name">分贝仪</div>
            <div class="tool-desc">实时环境噪音检测</div>
            <div class="tool-tags">
              <span class="tag">实时</span>
              <span class="tag">波形</span>
              <span class="tag free-tag">免费</span>
            </div>
          </div>
          <div class="tool-arrow">›</div>
        </div>
      </div>
    </div>

    <!-- 今日统计 -->
    <div class="stats-section">
      <div class="section-title">今日统计</div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-value">{{ todayCount }}</div>
          <div class="stat-label">测量次数</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">⏱️</div>
          <div class="stat-value">{{ historyCount }}</div>
          <div class="stat-label">历史记录</div>
        </div>
        <div class="stat-card" @click="store.navigate('user')">
          <div class="stat-icon">{{ store.isMember.value ? '👑' : '🔓' }}</div>
          <div class="stat-value gradient-text-gold">{{ store.isMember.value ? 'VIP' : '免费' }}</div>
          <div class="stat-label">当前版本</div>
        </div>
      </div>
    </div>

    <!-- 最近记录 -->
    <div class="recent-section" v-if="recentHistory.length > 0">
      <div class="section-header">
        <div class="section-title">最近记录</div>
        <span class="see-all" @click="store.navigate('history')">查看全部</span>
      </div>
      <div class="history-list">
        <div
          v-for="item in recentHistory"
          :key="item.id"
          class="history-item"
        >
          <div class="history-icon">{{ typeIcon(item.type) }}</div>
          <div class="history-info">
            <div class="history-value">{{ item.value }} <span class="history-unit">{{ item.unit }}</span></div>
            <div class="history-time">{{ formatTime(item.time) }}</div>
          </div>
          <div class="history-type-badge">{{ typeName(item.type) }}</div>
        </div>
      </div>
    </div>

    <!-- 无记录提示 -->
    <div class="empty-hint" v-else-if="!store.isMember.value">
      <div class="empty-icon">📋</div>
      <div class="empty-text">开通会员后可保存测量历史</div>
      <button class="btn btn-primary btn-sm" @click="store.state.showMemberModal = true">了解会员</button>
    </div>

    <!-- 底部安全区 -->
    <div style="height: 100px;"></div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import store from '../store/index.js'

const remaining = computed(() => store.getRemainingUsage())
const todayCount = computed(() => store.getTodayUsage())
const historyCount = computed(() => store.state.history.length)
const recentHistory = computed(() => store.state.history.slice(0, 5))

function openTool(tool) {
  if (!store.canUse()) {
    store.state.showVipModal = true
    return
  }
  store.navigate(tool)
}

function typeIcon(type) {
  return { ruler: '📏', level: '🫧', decibel: '🎙️' }[type] || '📊'
}

function typeName(type) {
  return { ruler: '尺子', level: '水平仪', decibel: '分贝仪' }[type] || type
}

function formatTime(iso) {
  const d = new Date(iso)
  const now = new Date()
  const diff = now - d
  if (diff < 60000) return '刚刚'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}分钟前`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}小时前`
  return d.toLocaleDateString('zh-CN', { month: '2-digit', day: '2-digit' })
}
</script>

<style scoped>
.home-page {
  background: var(--bg-dark);
  padding-bottom: 80px;
}

/* 背景装饰 */
.bg-decoration {
  position: fixed;
  inset: 0;
  pointer-events: none;
  z-index: 0;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.15;
}

.orb-1 {
  width: 300px;
  height: 300px;
  background: radial-gradient(circle, #6C63FF, transparent);
  top: -100px;
  right: -50px;
  animation: pulse 8s ease-in-out infinite;
}

.orb-2 {
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, #FF6584, transparent);
  top: 40%;
  left: -80px;
  animation: pulse 10s ease-in-out infinite 2s;
}

.orb-3 {
  width: 250px;
  height: 250px;
  background: radial-gradient(circle, #43E97B, transparent);
  bottom: 10%;
  right: -60px;
  animation: pulse 12s ease-in-out infinite 4s;
}

/* 顶部导航 */
.home-header {
  position: relative;
  z-index: 10;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.logo-icon {
  font-size: 28px;
  filter: drop-shadow(0 0 10px rgba(108,99,255,0.5));
}

.logo-text {
  display: flex;
  flex-direction: column;
}

.logo-name {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 0.5px;
  background: linear-gradient(135deg, #fff, #a0a0ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.logo-sub {
  font-size: 11px;
  color: var(--text-muted);
  letter-spacing: 2px;
  margin-top: 1px;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.usage-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.3);
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-light);
  cursor: pointer;
  transition: var(--transition);
}

.usage-badge:active {
  transform: scale(0.95);
  background: rgba(108,99,255,0.3);
}

.usage-icon {
  font-size: 14px;
}

.avatar-btn {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  position: relative;
  transition: var(--transition);
  box-shadow: 0 4px 12px rgba(108,99,255,0.4);
}

.avatar-btn:active {
  transform: scale(0.95);
}

.avatar-emoji {
  font-size: 20px;
}

.avatar-btn img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.vip-dot {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  border-radius: 50%;
  border: 2px solid var(--bg-dark);
  animation: pulse 2s ease-in-out infinite;
}

/* 会员横幅 */
.vip-banner {
  position: relative;
  z-index: 10;
  margin: 0 20px 16px;
  padding: 14px 16px;
  background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,165,0,0.15));
  border: 1px solid rgba(255,215,0,0.3);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: var(--transition);
}

.vip-banner:active {
  transform: scale(0.99);
}

.vip-banner-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.vip-crown {
  font-size: 28px;
  filter: drop-shadow(0 0 8px rgba(255,215,0,0.5));
}

.vip-title {
  font-size: 14px;
  font-weight: 700;
  color: #FFD700;
}

.vip-desc {
  font-size: 11px;
  color: rgba(255,215,0,0.7);
  margin-top: 2px;
}

.vip-arrow {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #FFD700;
  font-weight: 600;
}

/* 工具区域 */
.tools-section, .stats-section, .recent-section {
  position: relative;
  z-index: 10;
  padding: 0 20px 20px;
}

.section-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 14px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.see-all {
  font-size: 12px;
  color: var(--primary-light);
  cursor: pointer;
}

.tools-grid {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.tool-card {
  position: relative;
  border-radius: var(--radius-lg);
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 16px;
  cursor: pointer;
  overflow: hidden;
  transition: var(--transition);
  border: 1px solid rgba(255,255,255,0.08);
}

.tool-card:active {
  transform: scale(0.98);
}

.tool-card-bg {
  position: absolute;
  inset: 0;
  opacity: 0.08;
  transition: opacity var(--transition);
}

.tool-card:active .tool-card-bg {
  opacity: 0.15;
}

.tool-ruler {
  background: linear-gradient(135deg, rgba(108,99,255,0.15), rgba(108,99,255,0.05));
}

.tool-ruler .tool-card-bg {
  background: linear-gradient(135deg, #6C63FF, transparent);
}

.tool-level {
  background: linear-gradient(135deg, rgba(67,233,123,0.12), rgba(67,233,123,0.03));
}

.tool-level .tool-card-bg {
  background: linear-gradient(135deg, #43E97B, transparent);
}

.tool-decibel {
  background: linear-gradient(135deg, rgba(255,101,132,0.12), rgba(255,101,132,0.03));
}

.tool-decibel .tool-card-bg {
  background: linear-gradient(135deg, #FF6584, transparent);
}

.tool-icon-wrap {
  width: 56px;
  height: 56px;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  backdrop-filter: blur(10px);
}

.tool-icon {
  font-size: 28px;
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.3));
}

.tool-info {
  flex: 1;
  min-width: 0;
}

.tool-name {
  font-size: 17px;
  font-weight: 700;
  color: white;
  margin-bottom: 3px;
}

.tool-desc {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 8px;
}

.tool-tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.tag {
  padding: 2px 8px;
  background: rgba(255,255,255,0.08);
  border-radius: var(--radius-full);
  font-size: 10px;
  color: var(--text-secondary);
  letter-spacing: 0.5px;
}

.free-tag {
  background: rgba(67,233,123,0.15);
  color: var(--accent);
}

.tool-arrow {
  font-size: 24px;
  color: rgba(255,255,255,0.3);
  flex-shrink: 0;
}

/* 统计卡片 */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-card {
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-lg);
  padding: 16px 12px;
  text-align: center;
  cursor: pointer;
  transition: var(--transition);
}

.stat-card:active {
  transform: scale(0.97);
}

.stat-icon {
  font-size: 24px;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 22px;
  font-weight: 800;
  color: white;
  font-variant-numeric: tabular-nums;
  margin-bottom: 3px;
}

.stat-label {
  font-size: 11px;
  color: var(--text-muted);
}

/* 历史记录 */
.history-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: var(--radius-md);
  transition: var(--transition);
}

.history-item:active {
  background: rgba(255,255,255,0.07);
}

.history-icon {
  font-size: 22px;
  width: 36px;
  text-align: center;
}

.history-info {
  flex: 1;
}

.history-value {
  font-size: 15px;
  font-weight: 700;
  color: white;
  font-variant-numeric: tabular-nums;
}

.history-unit {
  font-size: 12px;
  color: var(--text-secondary);
  font-weight: 400;
}

.history-time {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 2px;
}

.history-type-badge {
  font-size: 11px;
  color: var(--text-muted);
  background: rgba(255,255,255,0.07);
  padding: 3px 8px;
  border-radius: var(--radius-full);
}

/* 空提示 */
.empty-hint {
  position: relative;
  z-index: 10;
  padding: 30px 20px;
  text-align: center;
}

.empty-icon {
  font-size: 40px;
  margin-bottom: 12px;
  opacity: 0.5;
}

.empty-text {
  color: var(--text-muted);
  font-size: 13px;
  margin-bottom: 16px;
}
</style>
