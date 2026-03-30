<template>
  <div class="level-page page">
    <!-- 顶部导航 -->
    <div class="level-topbar">
      <button class="back-btn" @click="store.goBack()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
      </button>
      <h1 class="page-title">水平仪</h1>
      <div class="topbar-actions">
        <button class="action-btn" :class="{ active: soundEnabled }" @click="soundEnabled = !soundEnabled">
          {{ soundEnabled ? '🔔' : '🔕' }}
        </button>
        <button class="action-btn" @click="toggleMode">
          {{ mode === 'horizontal' ? '水平' : '垂直' }}
        </button>
      </div>
    </div>

    <!-- 主体区域 -->
    <div class="level-main">
      <!-- 角度状态指示 -->
      <div class="status-ring" :class="statusClass">
        <div class="status-text">{{ statusText }}</div>
        <div class="status-sub">{{ statusDesc }}</div>
      </div>

      <!-- 圆形水平仪 -->
      <div class="bubble-container">
        <canvas ref="levelCanvas" class="level-canvas"></canvas>
        <!-- 气泡覆盖层 -->
        <div class="bubble-wrap">
          <!-- 中心十字 -->
          <div class="center-cross">
            <div class="cross-h"></div>
            <div class="cross-v"></div>
            <div class="center-dot" :class="{ leveled: isLeveled }"></div>
          </div>

          <!-- 气泡 -->
          <div
            class="bubble"
            :style="bubbleStyle"
            :class="{ leveled: isLeveled }"
          >
            <div class="bubble-inner"></div>
            <div class="bubble-glare"></div>
          </div>
        </div>

        <!-- 角度刻度文字 -->
        <div class="degree-marks">
          <span class="degree-mark top">0°</span>
          <span class="degree-mark right">90°</span>
          <span class="degree-mark bottom">180°</span>
          <span class="degree-mark left">90°</span>
        </div>
      </div>

      <!-- 角度数值显示 -->
      <div class="angle-display">
        <div class="angle-card">
          <div class="angle-label">X轴倾斜</div>
          <div class="angle-value" :style="{ color: getAngleColor(beta) }">
            {{ beta.toFixed(1) }}<span class="angle-unit">°</span>
          </div>
        </div>
        <div class="angle-divider"></div>
        <div class="angle-card main-angle">
          <div class="angle-label">当前角度</div>
          <div class="angle-value large" :class="{ leveled: isLeveled }">
            {{ totalAngle.toFixed(2) }}<span class="angle-unit">°</span>
          </div>
        </div>
        <div class="angle-divider"></div>
        <div class="angle-card">
          <div class="angle-label">Y轴倾斜</div>
          <div class="angle-value" :style="{ color: getAngleColor(gamma) }">
            {{ gamma.toFixed(1) }}<span class="angle-unit">°</span>
          </div>
        </div>
      </div>

      <!-- 坡度显示 -->
      <div class="slope-display">
        <div class="slope-item">
          <span class="slope-label">坡度</span>
          <span class="slope-value">{{ slope }}%</span>
        </div>
        <div class="slope-item">
          <span class="slope-label">精度</span>
          <span class="slope-value">±0.1°</span>
        </div>
        <div class="slope-item" v-if="isLocked">
          <span class="slope-label">锁定角度</span>
          <span class="slope-value">{{ lockedAngle.toFixed(2) }}°</span>
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="level-actions">
        <button class="level-btn" @click="calibrate">
          <span class="btn-icon">⚙️</span>
          <span>校准</span>
        </button>
        <button class="level-btn" :class="{ active: isLocked }" @click="toggleLock">
          <span class="btn-icon">{{ isLocked ? '🔒' : '🔓' }}</span>
          <span>{{ isLocked ? '已锁定' : '锁定角度' }}</span>
        </button>
        <button class="level-btn vip-btn" @click="saveRecord">
          <span class="btn-icon">💾</span>
          <span>保存</span>
        </button>
      </div>

      <!-- 权限提示 -->
      <div class="permission-tip" v-if="!hasPermission">
        <div class="perm-icon">🌀</div>
        <div class="perm-text">需要传感器权限才能使用水平仪</div>
        <button class="btn btn-primary btn-sm" @click="requestPermission">授权传感器</button>
      </div>

      <!-- 环境参考 -->
      <div class="reference-table">
        <div class="ref-table-title">角度参考</div>
        <div class="ref-table-grid">
          <div class="ref-row" v-for="ref in angleRefs" :key="ref.label"
            :class="{ highlight: Math.abs(totalAngle - ref.angle) < 2 }">
            <span class="ref-angle">{{ ref.angle }}°</span>
            <span class="ref-label">{{ ref.label }}</span>
            <span class="ref-icon">{{ ref.icon }}</span>
          </div>
        </div>
      </div>
    </div>

    <VipModal v-if="store.state.showVipModal" @close="store.state.showVipModal = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import store from '../store/index.js'
import VipModal from '../components/VipModal.vue'

// ===== 状态 =====
const beta = ref(0)    // X轴倾斜
const gamma = ref(0)   // Y轴倾斜
const alpha = ref(0)   // 方位角
const calibBeta = ref(0)
const calibGamma = ref(0)
const hasPermission = ref(true)
const soundEnabled = ref(false)
const mode = ref('horizontal')
const isLocked = ref(false)
const lockedAngle = ref(0)
const levelCanvas = ref(null)

// 音频上下文
let audioCtx = null

const angleRefs = [
  { angle: 0, label: '完全水平', icon: '✅' },
  { angle: 15, label: '轻微倾斜', icon: '📐' },
  { angle: 30, label: '30°斜面', icon: '🏔️' },
  { angle: 45, label: '45°角', icon: '↗️' },
  { angle: 90, label: '垂直竖立', icon: '⬆️' },
]

// ===== 计算 =====
const calibBetaV = computed(() => beta.value - calibBeta.value)
const calibGammaV = computed(() => gamma.value - calibGamma.value)

const totalAngle = computed(() => {
  const b = calibBetaV.value
  const g = calibGammaV.value
  return Math.sqrt(b * b + g * g)
})

const isLeveled = computed(() => totalAngle.value < 1.0)

const slope = computed(() => {
  return (Math.tan(totalAngle.value * Math.PI / 180) * 100).toFixed(1)
})

const statusClass = computed(() => {
  const a = totalAngle.value
  if (a < 1) return 'status-leveled'
  if (a < 5) return 'status-near'
  if (a < 20) return 'status-tilted'
  return 'status-steep'
})

const statusText = computed(() => {
  const a = totalAngle.value
  if (a < 1) return '水平 ✓'
  if (a < 5) return '接近水平'
  if (a < 20) return '轻微倾斜'
  return '明显倾斜'
})

const statusDesc = computed(() => {
  const a = totalAngle.value
  if (a < 1) return '设备已完全水平'
  if (a < 5) return '偏差较小，接近水平'
  if (a < 20) return `偏差 ${a.toFixed(1)}°`
  return `大幅倾斜 ${a.toFixed(1)}°`
})

const bubbleStyle = computed(() => {
  const containerSize = 200
  const maxOffset = containerSize / 2 - 22
  const bRad = calibBetaV.value * Math.PI / 180
  const gRad = calibGammaV.value * Math.PI / 180
  const sensitivity = 3

  let dx = Math.sin(gRad) * containerSize * sensitivity
  let dy = Math.sin(bRad) * containerSize * sensitivity

  const dist = Math.sqrt(dx * dx + dy * dy)
  if (dist > maxOffset) {
    const scale = maxOffset / dist
    dx *= scale
    dy *= scale
  }

  const hue = isLeveled.value ? '142, 90%, 55%' : '340, 90%, 60%'

  return {
    transform: `translate(calc(-50% + ${dx}px), calc(-50% + ${dy}px))`,
    background: `radial-gradient(circle at 30% 30%, hsla(${hue}, 0.9), hsla(${hue}, 0.5))`,
    boxShadow: isLeveled.value
      ? '0 0 20px rgba(67,233,123,0.6), inset 0 0 10px rgba(67,233,123,0.3)'
      : '0 0 12px rgba(255,71,87,0.4), inset 0 0 8px rgba(255,71,87,0.2)'
  }
})

function getAngleColor(angle) {
  const a = Math.abs(angle)
  if (a < 2) return '#43E97B'
  if (a < 10) return '#FFB347'
  return '#FF4757'
}

// ===== 绘制圆形水平仪底盘 =====
function drawLevelBase() {
  const canvas = levelCanvas.value
  if (!canvas) return

  const SIZE = 240
  canvas.width = SIZE
  canvas.height = SIZE
  const ctx = canvas.getContext('2d')
  const cx = SIZE / 2
  const cy = SIZE / 2
  const R = SIZE / 2 - 8

  ctx.clearRect(0, 0, SIZE, SIZE)

  // 外圆背景
  const outerGrad = ctx.createRadialGradient(cx, cy, 0, cx, cy, R)
  outerGrad.addColorStop(0, '#1e1e35')
  outerGrad.addColorStop(1, '#12122a')
  ctx.beginPath()
  ctx.arc(cx, cy, R, 0, Math.PI * 2)
  ctx.fillStyle = outerGrad
  ctx.fill()

  // 外圆边框渐变
  const strokeGrad = ctx.createLinearGradient(0, 0, SIZE, SIZE)
  strokeGrad.addColorStop(0, 'rgba(108,99,255,0.6)')
  strokeGrad.addColorStop(0.5, 'rgba(67,233,123,0.4)')
  strokeGrad.addColorStop(1, 'rgba(108,99,255,0.6)')
  ctx.strokeStyle = strokeGrad
  ctx.lineWidth = 3
  ctx.stroke()

  // 角度刻度
  for (let i = 0; i < 360; i += 5) {
    const rad = (i - 90) * Math.PI / 180
    const isMain = i % 45 === 0
    const isMid = i % 15 === 0
    const inner = R - (isMain ? 18 : isMid ? 12 : 7)
    const outer = R - 2
    const cos = Math.cos(rad)
    const sin = Math.sin(rad)

    ctx.beginPath()
    ctx.moveTo(cx + cos * inner, cy + sin * inner)
    ctx.lineTo(cx + cos * outer, cy + sin * outer)
    ctx.strokeStyle = isMain ? 'rgba(108,99,255,0.8)' : isMid ? 'rgba(255,255,255,0.4)' : 'rgba(255,255,255,0.15)'
    ctx.lineWidth = isMain ? 2 : 1
    ctx.stroke()

    if (isMain && i % 90 === 0) {
      const textR = R - 28
      ctx.fillStyle = 'rgba(255,255,255,0.6)'
      ctx.font = '600 10px sans-serif'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'middle'
      ctx.fillText(`${i}°`, cx + cos * textR, cy + sin * textR)
    }
  }

  // 内圆
  ctx.beginPath()
  ctx.arc(cx, cy, R - 24, 0, Math.PI * 2)
  ctx.strokeStyle = 'rgba(255,255,255,0.08)'
  ctx.lineWidth = 1
  ctx.stroke()

  // 中心圆
  ctx.beginPath()
  ctx.arc(cx, cy, 20, 0, Math.PI * 2)
  ctx.strokeStyle = 'rgba(108,99,255,0.4)'
  ctx.lineWidth = 1
  ctx.stroke()
}

// ===== 传感器 =====
function handleOrientation(event) {
  if (event.beta !== null) beta.value = event.beta
  if (event.gamma !== null) gamma.value = event.gamma
  if (event.alpha !== null) alpha.value = event.alpha

  // 水平音效
  if (soundEnabled.value && isLeveled.value) {
    playBeep()
  }
}

let lastBeep = 0
function playBeep() {
  const now = Date.now()
  if (now - lastBeep < 1000) return
  lastBeep = now
  try {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    const osc = audioCtx.createOscillator()
    const gain = audioCtx.createGain()
    osc.connect(gain)
    gain.connect(audioCtx.destination)
    osc.frequency.value = 880
    gain.gain.setValueAtTime(0.3, audioCtx.currentTime)
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3)
    osc.start(audioCtx.currentTime)
    osc.stop(audioCtx.currentTime + 0.3)
  } catch (e) {}
}

async function requestPermission() {
  if (typeof DeviceOrientationEvent !== 'undefined' &&
    typeof DeviceOrientationEvent.requestPermission === 'function') {
    try {
      const resp = await DeviceOrientationEvent.requestPermission()
      if (resp === 'granted') {
        hasPermission.value = true
        startSensor()
      }
    } catch (e) {
      store.showToast('获取传感器权限失败')
    }
  } else {
    hasPermission.value = true
    startSensor()
  }
}

function startSensor() {
  window.addEventListener('deviceorientation', handleOrientation, true)
}

function calibrate() {
  calibBeta.value = beta.value
  calibGamma.value = gamma.value
  store.showToast('✅ 已校准当前位置为水平')
}

function toggleLock() {
  if (!store.requireMember('角度锁定')) return
  if (!isLocked.value) {
    lockedAngle.value = totalAngle.value
    isLocked.value = true
    store.showToast('🔒 已锁定当前角度')
  } else {
    isLocked.value = false
    store.showToast('已解除角度锁定')
  }
}

function toggleMode() {
  mode.value = mode.value === 'horizontal' ? 'vertical' : 'horizontal'
  store.showToast(`已切换到${mode.value === 'horizontal' ? '水平' : '垂直'}模式`)
}

function saveRecord() {
  if (!store.requireMember('测量历史')) return
  store.saveHistory({
    type: 'level',
    value: totalAngle.value.toFixed(2),
    unit: '°'
  })
  store.showToast('✅ 已保存水平仪数据')
}

// 模拟传感器（调试用）
let simTimer = null
function startSimulation() {
  let t = 0
  simTimer = setInterval(() => {
    t += 0.05
    beta.value = Math.sin(t) * 8
    gamma.value = Math.cos(t * 0.7) * 6
  }, 50)
}

onMounted(async () => {
  await nextTick()
  drawLevelBase()

  if (typeof DeviceOrientationEvent !== 'undefined') {
    if (typeof DeviceOrientationEvent.requestPermission === 'function') {
      hasPermission.value = false
    } else {
      startSensor()
    }
  } else {
    // 没有真实传感器，使用模拟
    startSimulation()
  }
})

onUnmounted(() => {
  window.removeEventListener('deviceorientation', handleOrientation, true)
  if (simTimer) clearInterval(simTimer)
  if (audioCtx) {
    try { audioCtx.close() } catch (e) {}
  }
})
</script>

<style scoped>
.level-page {
  background: var(--bg-dark);
  overflow-y: auto;
}

.level-topbar {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: rgba(15,15,26,0.95);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  gap: 12px;
  position: sticky;
  top: 0;
  z-index: 50;
}

.back-btn {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.07);
  border: none;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: var(--transition);
}

.back-btn:active { background: rgba(255,255,255,0.15); }

.page-title {
  flex: 1;
  font-size: 18px;
  font-weight: 700;
}

.topbar-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 7px 14px;
  border-radius: var(--radius-md);
  background: rgba(67,233,123,0.1);
  border: 1px solid rgba(67,233,123,0.25);
  color: var(--accent);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.action-btn.active {
  background: var(--accent);
  color: var(--bg-dark);
}

/* 主体 */
.level-main {
  padding: 20px;
  padding-bottom: 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

/* 状态环 */
.status-ring {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 3px solid;
  transition: all 0.5s ease;
  text-align: center;
}

.status-leveled {
  border-color: var(--accent);
  background: rgba(67,233,123,0.1);
  box-shadow: 0 0 30px rgba(67,233,123,0.3);
}

.status-near {
  border-color: var(--warning);
  background: rgba(255,179,71,0.1);
  box-shadow: 0 0 20px rgba(255,179,71,0.2);
}

.status-tilted {
  border-color: var(--secondary);
  background: rgba(255,101,132,0.1);
}

.status-steep {
  border-color: var(--danger);
  background: rgba(255,71,87,0.1);
}

.status-text {
  font-size: 15px;
  font-weight: 800;
  color: white;
}

.status-sub {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 3px;
  padding: 0 8px;
  text-align: center;
}

/* 气泡水平仪 */
.bubble-container {
  position: relative;
  width: 240px;
  height: 240px;
}

.level-canvas {
  position: absolute;
  inset: 0;
  border-radius: 50%;
}

.bubble-wrap {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  overflow: hidden;
}

.center-cross {
  position: absolute;
  inset: 0;
  z-index: 5;
}

.cross-h {
  position: absolute;
  top: 50%;
  left: 20%;
  right: 20%;
  height: 1px;
  background: rgba(255,255,255,0.15);
  transform: translateY(-50%);
}

.cross-v {
  position: absolute;
  left: 50%;
  top: 20%;
  bottom: 20%;
  width: 1px;
  background: rgba(255,255,255,0.15);
  transform: translateX(-50%);
}

.center-dot {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(255,255,255,0.3);
  border: 2px solid rgba(255,255,255,0.5);
  transform: translate(-50%, -50%);
  z-index: 10;
  transition: all 0.3s ease;
}

.center-dot.leveled {
  background: rgba(67,233,123,0.6);
  border-color: var(--accent);
  box-shadow: 0 0 12px rgba(67,233,123,0.6);
  animation: pulse 1s ease-in-out infinite;
}

.bubble {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  transition: transform 0.1s ease-out, background 0.3s ease, box-shadow 0.3s ease;
  z-index: 8;
  overflow: hidden;
}

.bubble-inner {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: radial-gradient(circle at 60% 60%, rgba(255,255,255,0.2), transparent);
}

.bubble-glare {
  position: absolute;
  top: 15%;
  left: 20%;
  width: 35%;
  height: 25%;
  background: rgba(255,255,255,0.5);
  border-radius: 50%;
  filter: blur(3px);
}

.degree-marks {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 2;
}

.degree-mark {
  position: absolute;
  font-size: 10px;
  color: rgba(255,255,255,0.4);
  font-weight: 600;
}

.degree-mark.top { top: 6px; left: 50%; transform: translateX(-50%); }
.degree-mark.bottom { bottom: 6px; left: 50%; transform: translateX(-50%); }
.degree-mark.left { left: 4px; top: 50%; transform: translateY(-50%); }
.degree-mark.right { right: 4px; top: 50%; transform: translateY(-50%); }

/* 角度显示 */
.angle-display {
  display: flex;
  align-items: center;
  width: 100%;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-xl);
  padding: 16px;
}

.angle-card {
  flex: 1;
  text-align: center;
}

.angle-label {
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 6px;
  letter-spacing: 0.5px;
}

.angle-value {
  font-size: 24px;
  font-weight: 800;
  color: white;
  font-variant-numeric: tabular-nums;
  transition: color 0.3s ease;
}

.angle-value.large {
  font-size: 36px;
}

.angle-value.leveled {
  color: var(--accent);
  text-shadow: 0 0 15px rgba(67,233,123,0.5);
}

.angle-unit {
  font-size: 14px;
  font-weight: 400;
  opacity: 0.7;
}

.angle-divider {
  width: 1px;
  height: 50px;
  background: rgba(255,255,255,0.08);
  margin: 0 8px;
}

.main-angle {
  flex: 1.5;
}

/* 坡度 */
.slope-display {
  display: flex;
  gap: 12px;
  width: 100%;
}

.slope-item {
  flex: 1;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-md);
  padding: 12px;
  text-align: center;
}

.slope-label {
  display: block;
  font-size: 11px;
  color: var(--text-muted);
  margin-bottom: 4px;
}

.slope-value {
  font-size: 18px;
  font-weight: 800;
  color: white;
  font-variant-numeric: tabular-nums;
}

/* 操作按钮 */
.level-actions {
  display: flex;
  gap: 12px;
  width: 100%;
}

.level-btn {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 14px 10px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-lg);
  color: white;
  cursor: pointer;
  transition: var(--transition);
}

.level-btn:active {
  transform: scale(0.97);
  background: rgba(255,255,255,0.1);
}

.level-btn.active {
  background: rgba(67,233,123,0.15);
  border-color: rgba(67,233,123,0.4);
}

.level-btn.vip-btn {
  border-color: rgba(255,215,0,0.3);
  color: #FFD700;
}

.btn-icon { font-size: 22px; }

.level-btn span:last-child {
  font-size: 11px;
  font-weight: 600;
}

/* 权限提示 */
.permission-tip {
  width: 100%;
  padding: 24px;
  background: rgba(255,179,71,0.08);
  border: 1px solid rgba(255,179,71,0.2);
  border-radius: var(--radius-lg);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.perm-icon { font-size: 32px; }
.perm-text { font-size: 13px; color: var(--text-secondary); }

/* 参考表 */
.reference-table {
  width: 100%;
}

.ref-table-title {
  font-size: 12px;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.ref-table-grid {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.ref-row {
  display: flex;
  align-items: center;
  padding: 10px 14px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: var(--radius-md);
  transition: var(--transition);
}

.ref-row.highlight {
  background: rgba(67,233,123,0.1);
  border-color: rgba(67,233,123,0.3);
  animation: pulse 2s ease-in-out infinite;
}

.ref-angle {
  font-size: 15px;
  font-weight: 800;
  color: var(--primary-light);
  width: 50px;
}

.ref-label {
  flex: 1;
  font-size: 13px;
  color: var(--text-secondary);
}

.ref-icon { font-size: 18px; }
</style>
