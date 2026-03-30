<template>
  <div class="decibel-page page">
    <!-- 顶部导航 -->
    <div class="db-topbar">
      <button class="back-btn" @click="store.goBack()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
      </button>
      <h1 class="page-title">分贝仪</h1>
      <div class="topbar-actions">
        <button class="action-btn" :class="{ danger: isWarningEnabled }" @click="toggleWarning">
          {{ isWarningEnabled ? '🔔' : '🔕' }}
        </button>
        <span class="session-time" v-if="isMonitoring">{{ sessionTime }}</span>
      </div>
    </div>

    <!-- 主体 -->
    <div class="db-main">
      <!-- 环境标识 -->
      <div class="env-badge" :class="envClass">
        <span class="env-icon">{{ envConfig.icon }}</span>
        <div class="env-info">
          <span class="env-label">{{ envConfig.label }}</span>
          <span class="env-range">{{ envConfig.range }}</span>
        </div>
      </div>

      <!-- 分贝仪表盘 -->
      <div class="gauge-container">
        <canvas ref="gaugeCanvas" class="gauge-canvas"></canvas>
        <div class="gauge-center">
          <div class="db-value" :style="{ color: dbColor }">
            <span class="db-num">{{ currentDb.toFixed(1) }}</span>
            <span class="db-unit">dB</span>
          </div>
          <div class="db-label">当前音量</div>
        </div>
      </div>

      <!-- 峰值和均值 -->
      <div class="stats-row">
        <div class="stat-pill min">
          <span class="stat-pill-label">最小</span>
          <span class="stat-pill-val">{{ minDb.toFixed(0) }}</span>
          <span class="stat-pill-unit">dB</span>
        </div>
        <div class="stat-pill avg">
          <span class="stat-pill-label">平均</span>
          <span class="stat-pill-val">{{ avgDb.toFixed(0) }}</span>
          <span class="stat-pill-unit">dB</span>
        </div>
        <div class="stat-pill max">
          <span class="stat-pill-label">峰值</span>
          <span class="stat-pill-val">{{ maxDb.toFixed(0) }}</span>
          <span class="stat-pill-unit">dB</span>
        </div>
      </div>

      <!-- 波形图 -->
      <div class="waveform-section">
        <div class="waveform-header">
          <span class="wf-title">实时波形</span>
          <span class="wf-range">30 - 130 dB</span>
        </div>
        <canvas ref="waveCanvas" class="waveform-canvas"></canvas>
      </div>

      <!-- 控制按钮 -->
      <div class="control-section">
        <button
          class="main-control-btn"
          :class="{ active: isMonitoring, loading: isLoading }"
          @click="toggleMonitor"
        >
          <div class="control-btn-ring"></div>
          <div class="control-btn-inner">
            <span v-if="isLoading" class="loading-spinner" style="width:24px;height:24px;border-width:2px;"></span>
            <span v-else-if="!isMonitoring">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8 5v14l11-7z"/>
              </svg>
            </span>
            <span v-else>
              <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                <rect x="6" y="4" width="4" height="16" rx="1"/>
                <rect x="14" y="4" width="4" height="16" rx="1"/>
              </svg>
            </span>
            <span class="control-btn-text">{{ isLoading ? '初始化...' : isMonitoring ? '停止监测' : '开始监测' }}</span>
          </div>
        </button>

        <div class="side-controls">
          <button class="side-btn" @click="resetStats">
            <span>↺</span>
            <span>重置</span>
          </button>
          <button class="side-btn vip" @click="saveRecord">
            <span>💾</span>
            <span>保存</span>
          </button>
          <button class="side-btn vip" @click="exportReport">
            <span>📊</span>
            <span>导出</span>
          </button>
        </div>
      </div>

      <!-- 预警设置 -->
      <div class="warning-section" v-if="isWarningEnabled">
        <div class="warning-header">
          <span class="warning-title">⚠️ 噪音预警</span>
          <span class="warning-value">{{ warningThreshold }} dB</span>
        </div>
        <input
          type="range"
          v-model.number="warningThreshold"
          min="30"
          max="120"
          step="5"
          class="threshold-slider"
        >
        <div class="slider-labels">
          <span>30dB</span>
          <span>安静</span>
          <span>正常</span>
          <span>嘈杂</span>
          <span>危险</span>
          <span>130dB</span>
        </div>
      </div>

      <!-- 麦克风权限 -->
      <div class="perm-card" v-if="!hasPermission">
        <div class="perm-card-icon">🎙️</div>
        <div class="perm-card-text">
          <div class="perm-card-title">需要麦克风权限</div>
          <div class="perm-card-desc">分贝仪需要访问麦克风来检测环境声音</div>
        </div>
        <button class="btn btn-primary btn-sm" @click="toggleMonitor">授权</button>
      </div>

      <!-- 环境参考 -->
      <div class="env-reference">
        <div class="env-ref-title">环境参考值</div>
        <div class="env-ref-grid">
          <div
            class="env-ref-item"
            v-for="env in envReferences"
            :key="env.label"
            :class="{ current: currentDb >= env.min && currentDb < env.max }"
          >
            <div class="env-ref-bar">
              <div
                class="env-ref-fill"
                :style="{
                  width: ((env.min - 30) / 100 * 100) + '%',
                  background: env.color
                }"
              ></div>
            </div>
            <span class="env-ref-icon">{{ env.icon }}</span>
            <span class="env-ref-name">{{ env.label }}</span>
            <span class="env-ref-db">{{ env.min }}-{{ env.max }}dB</span>
          </div>
        </div>
      </div>
    </div>

    <VipModal v-if="store.state.showVipModal" @close="store.state.showVipModal = false" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import store from '../store/index.js'
import VipModal from '../components/VipModal.vue'

// ===== 状态 =====
const currentDb = ref(30)
const maxDb = ref(30)
const minDb = ref(130)
const dbHistory = ref([])
const isMonitoring = ref(false)
const isLoading = ref(false)
const hasPermission = ref(false)
const isWarningEnabled = ref(false)
const warningThreshold = ref(80)
const sessionStart = ref(null)
const sessionTime = ref('00:00')
const gaugeCanvas = ref(null)
const waveCanvas = ref(null)

// 音频相关
let audioCtx = null
let analyser = null
let microphone = null
let animFrame = null
let timerInterval = null
let waveData = new Array(80).fill(30)

const envReferences = [
  { min: 0, max: 40, label: '无声/图书馆', icon: '📚', color: '#43E97B' },
  { min: 40, max: 60, label: '安静办公室', icon: '🏢', color: '#4FC3F7' },
  { min: 60, max: 75, label: '正常交谈', icon: '💬', color: '#FFB347' },
  { min: 75, max: 90, label: '繁忙街道', icon: '🚗', color: '#FF8C42' },
  { min: 90, max: 110, label: '地铁/工厂', icon: '🚇', color: '#FF6584' },
  { min: 110, max: 130, label: '噪音危险', icon: '⚠️', color: '#FF4757' },
]

// ===== 计算 =====
const avgDb = computed(() => {
  if (dbHistory.value.length === 0) return 0
  return dbHistory.value.reduce((a, b) => a + b, 0) / dbHistory.value.length
})

const envConfig = computed(() => {
  const db = currentDb.value
  if (db < 40) return { label: '无声', range: '< 40dB', icon: '🤫', class: 'env-silent' }
  if (db < 60) return { label: '安静', range: '40-60dB', icon: '😌', class: 'env-quiet' }
  if (db < 75) return { label: '正常', range: '60-75dB', icon: '😊', class: 'env-normal' }
  if (db < 90) return { label: '嘈杂', range: '75-90dB', icon: '😰', class: 'env-noisy' }
  if (db < 110) return { label: '危险', range: '90-110dB', icon: '😱', class: 'env-danger' }
  return { label: '极危险', range: '> 110dB', icon: '🚨', class: 'env-critical' }
})

const envClass = computed(() => envConfig.value.class)

const dbColor = computed(() => {
  const db = currentDb.value
  if (db < 60) return '#43E97B'
  if (db < 75) return '#4FC3F7'
  if (db < 85) return '#FFB347'
  if (db < 95) return '#FF8C42'
  return '#FF4757'
})

// ===== 监控逻辑 =====
async function toggleMonitor() {
  if (isMonitoring.value) {
    stopMonitor()
  } else {
    await startMonitor()
  }
}

async function startMonitor() {
  isLoading.value = true
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false })
    hasPermission.value = true
    audioCtx = new (window.AudioContext || window.webkitAudioContext)()
    analyser = audioCtx.createAnalyser()
    analyser.fftSize = 2048
    analyser.smoothingTimeConstant = 0.8
    microphone = audioCtx.createMediaStreamSource(stream)
    microphone.connect(analyser)

    isMonitoring.value = true
    sessionStart.value = Date.now()
    startTimer()
    processAudio()
  } catch (e) {
    hasPermission.value = false
    store.showToast('麦克风权限被拒绝')
  }
  isLoading.value = false
}

function stopMonitor() {
  isMonitoring.value = false
  if (animFrame) cancelAnimationFrame(animFrame)
  if (timerInterval) clearInterval(timerInterval)
  if (microphone) microphone.disconnect()
  if (audioCtx) audioCtx.close().catch(() => {})
  audioCtx = null
  analyser = null
  microphone = null
}

function processAudio() {
  if (!isMonitoring.value || !analyser) return

  const bufferLength = analyser.fftSize
  const dataArray = new Float32Array(bufferLength)
  analyser.getFloatTimeDomainData(dataArray)

  // 计算RMS
  let sumSq = 0
  for (let i = 0; i < bufferLength; i++) {
    sumSq += dataArray[i] * dataArray[i]
  }
  const rms = Math.sqrt(sumSq / bufferLength)

  // 转换dB (加噪底 0.0001)
  let db = rms > 0.0001 ? 20 * Math.log10(rms / 0.0001) : 30
  db = Math.max(30, Math.min(130, db))

  // 平滑处理
  currentDb.value = currentDb.value * 0.7 + db * 0.3

  if (currentDb.value > maxDb.value) maxDb.value = currentDb.value
  if (currentDb.value < minDb.value) minDb.value = currentDb.value

  dbHistory.value.push(currentDb.value)
  if (dbHistory.value.length > 300) dbHistory.value.shift()

  // 波形数据
  waveData.push(currentDb.value)
  if (waveData.length > 80) waveData.shift()

  // 预警
  if (isWarningEnabled.value && currentDb.value > warningThreshold.value) {
    triggerWarning()
  }

  drawGauge()
  drawWaveform()
  animFrame = requestAnimationFrame(processAudio)
}

function startTimer() {
  timerInterval = setInterval(() => {
    const elapsed = Math.floor((Date.now() - sessionStart.value) / 1000)
    const m = Math.floor(elapsed / 60).toString().padStart(2, '0')
    const s = (elapsed % 60).toString().padStart(2, '0')
    sessionTime.value = `${m}:${s}`
  }, 1000)
}

let lastWarn = 0
function triggerWarning() {
  const now = Date.now()
  if (now - lastWarn < 3000) return
  lastWarn = now
  store.showToast(`⚠️ 噪音超过 ${warningThreshold.value} dB！`, 3000)
}

// ===== 绘制仪表盘 =====
function drawGauge() {
  const canvas = gaugeCanvas.value
  if (!canvas) return

  const SIZE = 220
  canvas.width = SIZE
  canvas.height = SIZE
  const ctx = canvas.getContext('2d')
  const cx = SIZE / 2
  const cy = SIZE / 2
  const R = SIZE / 2 - 12

  ctx.clearRect(0, 0, SIZE, SIZE)

  // 背景弧
  ctx.beginPath()
  ctx.arc(cx, cy, R, Math.PI * 0.75, Math.PI * 2.25)
  ctx.strokeStyle = 'rgba(255,255,255,0.06)'
  ctx.lineWidth = 14
  ctx.lineCap = 'round'
  ctx.stroke()

  // 彩色进度弧
  const progress = (currentDb.value - 30) / 100
  const startAngle = Math.PI * 0.75
  const endAngle = startAngle + progress * Math.PI * 1.5

  const arcGrad = ctx.createLinearGradient(0, 0, SIZE, SIZE)
  arcGrad.addColorStop(0, '#43E97B')
  arcGrad.addColorStop(0.4, '#FFB347')
  arcGrad.addColorStop(0.7, '#FF6584')
  arcGrad.addColorStop(1, '#FF4757')

  ctx.beginPath()
  ctx.arc(cx, cy, R, startAngle, endAngle)
  ctx.strokeStyle = arcGrad
  ctx.lineWidth = 14
  ctx.lineCap = 'round'
  ctx.stroke()

  // 发光效果
  ctx.beginPath()
  ctx.arc(cx, cy, R, startAngle, endAngle)
  ctx.strokeStyle = dbColor.value + '40'
  ctx.lineWidth = 22
  ctx.lineCap = 'round'
  ctx.stroke()

  // 刻度线
  for (let db = 30; db <= 130; db += 10) {
    const p = (db - 30) / 100
    const angle = Math.PI * 0.75 + p * Math.PI * 1.5
    const inner = R - 20
    const outer = R - 8
    ctx.beginPath()
    ctx.moveTo(cx + Math.cos(angle) * inner, cy + Math.sin(angle) * inner)
    ctx.lineTo(cx + Math.cos(angle) * outer, cy + Math.sin(angle) * outer)
    ctx.strokeStyle = 'rgba(255,255,255,0.3)'
    ctx.lineWidth = db % 30 === 0 ? 2 : 1
    ctx.stroke()

    if (db % 30 === 0) {
      const textR = R - 30
      ctx.fillStyle = 'rgba(255,255,255,0.5)'
      ctx.font = '600 9px sans-serif'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'middle'
      ctx.fillText(db.toString(), cx + Math.cos(angle) * textR, cy + Math.sin(angle) * textR)
    }
  }

  // 指针
  const angle = Math.PI * 0.75 + progress * Math.PI * 1.5
  const needleLength = R - 25

  ctx.save()
  ctx.translate(cx, cy)
  ctx.rotate(angle)

  const needleGrad = ctx.createLinearGradient(0, 0, needleLength, 0)
  needleGrad.addColorStop(0, dbColor.value)
  needleGrad.addColorStop(1, 'transparent')

  ctx.beginPath()
  ctx.moveTo(-10, 0)
  ctx.lineTo(needleLength, 0)
  ctx.strokeStyle = needleGrad
  ctx.lineWidth = 3
  ctx.lineCap = 'round'
  ctx.stroke()
  ctx.restore()

  // 中心圆
  ctx.beginPath()
  ctx.arc(cx, cy, 8, 0, Math.PI * 2)
  ctx.fillStyle = dbColor.value
  ctx.fill()
  ctx.beginPath()
  ctx.arc(cx, cy, 4, 0, Math.PI * 2)
  ctx.fillStyle = 'white'
  ctx.fill()
}

// ===== 绘制波形 =====
function drawWaveform() {
  const canvas = waveCanvas.value
  if (!canvas) return

  const W = canvas.offsetWidth || 340
  const H = 80
  canvas.width = W
  canvas.height = H
  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, W, H)

  // 背景
  ctx.fillStyle = 'rgba(255,255,255,0.02)'
  ctx.fillRect(0, 0, W, H)

  if (waveData.length < 2) return

  const barW = W / waveData.length
  const grad = ctx.createLinearGradient(0, 0, W, 0)
  grad.addColorStop(0, '#43E97B40')
  grad.addColorStop(0.5, '#6C63FF80')
  grad.addColorStop(1, dbColor.value + 'CC')

  for (let i = 0; i < waveData.length; i++) {
    const db = waveData[i]
    const barH = Math.max(2, ((db - 30) / 100) * H * 0.85)
    const x = i * barW
    const y = H / 2 - barH / 2

    ctx.fillStyle = grad
    ctx.beginPath()
    ctx.roundRect(x + 1, y, barW - 2, barH, 2)
    ctx.fill()
  }

  // 参考线
  ctx.strokeStyle = 'rgba(255,179,71,0.3)'
  ctx.lineWidth = 1
  ctx.setLineDash([4, 4])
  const refY = H / 2 - ((warningThreshold.value - 30) / 100) * H * 0.85 / 2
  ctx.beginPath()
  ctx.moveTo(0, refY)
  ctx.lineTo(W, refY)
  ctx.stroke()
  ctx.setLineDash([])
}

// ===== 其他功能 =====
function resetStats() {
  maxDb.value = 30
  minDb.value = 130
  dbHistory.value = []
  waveData = new Array(80).fill(30)
  store.showToast('已重置统计数据')
}

function toggleWarning() {
  if (!isWarningEnabled.value && !store.requireMember('噪音预警')) return
  isWarningEnabled.value = !isWarningEnabled.value
}

function saveRecord() {
  if (!store.requireMember('测量历史')) return
  store.saveHistory({
    type: 'decibel',
    value: currentDb.value.toFixed(1),
    unit: 'dB',
    extra: { max: maxDb.value.toFixed(1), avg: avgDb.value.toFixed(1), min: minDb.value.toFixed(1) }
  })
  store.showToast('✅ 已保存分贝数据')
}

function exportReport() {
  if (!store.requireMember('报告导出')) return
  store.showToast('📊 报告生成功能开发中...')
}

// 初始化时绘制空仪表
onMounted(async () => {
  await nextTick()
  drawGauge()
  drawWaveform()
})

onUnmounted(() => {
  stopMonitor()
})

// 监听数据变化实时绘制
watch(currentDb, () => {
  if (!isMonitoring.value) {
    drawGauge()
  }
})
</script>

<style scoped>
.decibel-page {
  background: var(--bg-dark);
  overflow-y: auto;
}

.db-topbar {
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
  align-items: center;
  gap: 8px;
}

.action-btn {
  padding: 7px 14px;
  border-radius: var(--radius-md);
  background: rgba(255,101,132,0.1);
  border: 1px solid rgba(255,101,132,0.25);
  color: var(--secondary);
  font-size: 16px;
  cursor: pointer;
  transition: var(--transition);
}

.action-btn.danger {
  background: var(--secondary);
  color: white;
}

.session-time {
  font-size: 13px;
  font-weight: 700;
  color: var(--accent);
  font-variant-numeric: tabular-nums;
  padding: 4px 10px;
  background: rgba(67,233,123,0.1);
  border-radius: var(--radius-full);
}

/* 主体 */
.db-main {
  padding: 16px 20px 100px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

/* 环境标识 */
.env-badge {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-lg);
  border: 1px solid;
  transition: all 0.5s ease;
}

.env-silent { background: rgba(67,233,123,0.08); border-color: rgba(67,233,123,0.25); }
.env-quiet { background: rgba(79,195,247,0.08); border-color: rgba(79,195,247,0.25); }
.env-normal { background: rgba(255,179,71,0.08); border-color: rgba(255,179,71,0.25); }
.env-noisy { background: rgba(255,140,66,0.1); border-color: rgba(255,140,66,0.3); }
.env-danger { background: rgba(255,101,132,0.1); border-color: rgba(255,101,132,0.3); }
.env-critical { background: rgba(255,71,87,0.15); border-color: rgba(255,71,87,0.4); animation: pulse 1s ease-in-out infinite; }

.env-icon { font-size: 28px; }

.env-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.env-label {
  font-size: 16px;
  font-weight: 800;
  color: white;
}

.env-range {
  font-size: 12px;
  color: var(--text-muted);
}

/* 仪表盘 */
.gauge-container {
  position: relative;
  width: 220px;
  height: 220px;
}

.gauge-canvas {
  position: absolute;
  inset: 0;
}

.gauge-center {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-top: 20px;
}

.db-value {
  display: flex;
  align-items: baseline;
  gap: 4px;
  transition: color 0.3s ease;
}

.db-num {
  font-size: 52px;
  font-weight: 900;
  line-height: 1;
  font-variant-numeric: tabular-nums;
  text-shadow: 0 0 20px currentColor;
}

.db-unit {
  font-size: 18px;
  font-weight: 500;
  opacity: 0.8;
}

.db-label {
  font-size: 12px;
  color: var(--text-muted);
  margin-top: 4px;
}

/* 统计数据 */
.stats-row {
  display: flex;
  gap: 10px;
  width: 100%;
}

.stat-pill {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 10px;
  border-radius: var(--radius-md);
  border: 1px solid rgba(255,255,255,0.08);
}

.stat-pill.min { background: rgba(67,233,123,0.08); }
.stat-pill.avg { background: rgba(108,99,255,0.08); }
.stat-pill.max { background: rgba(255,71,87,0.08); }

.stat-pill-label {
  font-size: 10px;
  color: var(--text-muted);
  margin-bottom: 4px;
}

.stat-pill-val {
  font-size: 22px;
  font-weight: 800;
  color: white;
  font-variant-numeric: tabular-nums;
}

.stat-pill-unit {
  font-size: 10px;
  color: var(--text-muted);
}

/* 波形图 */
.waveform-section {
  width: 100%;
}

.waveform-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.wf-title {
  font-size: 12px;
  color: var(--text-muted);
  font-weight: 600;
  letter-spacing: 0.5px;
}

.wf-range {
  font-size: 11px;
  color: var(--text-muted);
}

.waveform-canvas {
  width: 100%;
  height: 80px;
  border-radius: var(--radius-md);
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.05);
  display: block;
}

/* 控制按钮 */
.control-section {
  display: flex;
  gap: 12px;
  align-items: center;
  width: 100%;
}

.main-control-btn {
  flex: 0 0 100px;
  height: 100px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  position: relative;
  background: transparent;
  transition: var(--transition);
}

.main-control-btn:active {
  transform: scale(0.95);
}

.control-btn-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: conic-gradient(from 0deg, #FF4757, #FF6584, #6C63FF, #43E97B, #FF4757);
  opacity: 0.3;
  animation: spin 3s linear infinite;
}

.main-control-btn.active .control-btn-ring {
  opacity: 0.8;
}

.control-btn-inner {
  position: relative;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  background: linear-gradient(135deg, #1a1a35, #0f0f1a);
  border: 2px solid rgba(255,255,255,0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  color: white;
  z-index: 1;
}

.main-control-btn.active .control-btn-inner {
  background: linear-gradient(135deg, rgba(255,71,87,0.2), rgba(255,101,132,0.1));
  border-color: rgba(255,71,87,0.4);
  color: #FF6584;
}

.control-btn-text {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.5px;
}

.side-controls {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.side-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-md);
  color: white;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.side-btn:active {
  background: rgba(255,255,255,0.1);
  transform: scale(0.98);
}

.side-btn.vip {
  border-color: rgba(255,215,0,0.25);
  color: rgba(255,215,0,0.8);
}

/* 预警设置 */
.warning-section {
  width: 100%;
  padding: 16px;
  background: rgba(255,179,71,0.08);
  border: 1px solid rgba(255,179,71,0.2);
  border-radius: var(--radius-lg);
}

.warning-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.warning-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--warning);
}

.warning-value {
  font-size: 18px;
  font-weight: 800;
  color: white;
}

.threshold-slider {
  width: 100%;
  -webkit-appearance: none;
  height: 6px;
  border-radius: 3px;
  background: linear-gradient(to right, #43E97B, #FFB347, #FF4757);
  outline: none;
  margin-bottom: 8px;
}

.threshold-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.4);
  cursor: pointer;
}

.slider-labels {
  display: flex;
  justify-content: space-between;
  font-size: 10px;
  color: var(--text-muted);
}

/* 权限卡片 */
.perm-card {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: rgba(108,99,255,0.1);
  border: 1px solid rgba(108,99,255,0.25);
  border-radius: var(--radius-lg);
}

.perm-card-icon { font-size: 32px; }

.perm-card-text { flex: 1; }

.perm-card-title {
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 2px;
}

.perm-card-desc {
  font-size: 12px;
  color: var(--text-secondary);
}

/* 环境参考 */
.env-reference {
  width: 100%;
}

.env-ref-title {
  font-size: 12px;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.env-ref-grid {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.env-ref-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: var(--radius-md);
  transition: var(--transition);
}

.env-ref-item.current {
  background: rgba(108,99,255,0.12);
  border-color: rgba(108,99,255,0.3);
  animation: pulse 2s ease-in-out infinite;
}

.env-ref-bar {
  width: 30px;
  height: 4px;
  background: rgba(255,255,255,0.1);
  border-radius: 2px;
  overflow: hidden;
}

.env-ref-fill {
  height: 100%;
  border-radius: 2px;
}

.env-ref-icon { font-size: 18px; }
.env-ref-name { flex: 1; font-size: 12px; color: var(--text-secondary); }
.env-ref-db { font-size: 11px; color: var(--text-muted); }
</style>
