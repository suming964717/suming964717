<template>
  <div class="ruler-page page">
    <!-- 顶部导航 -->
    <div class="ruler-topbar">
      <button class="back-btn" @click="store.goBack()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
          <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
      </button>
      <h1 class="page-title">屏幕尺子</h1>
      <div class="topbar-actions">
        <button class="action-btn" @click="toggleUnit" :class="{ active: unit === 'inch' }">
          {{ unit === 'cm' ? 'cm' : 'in' }}
        </button>
        <button class="action-btn" @click="showCalibrate = true">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- 尺子主体区域 -->
    <div class="ruler-container" ref="rulerContainer">
      <!-- 水平尺 -->
      <div
        v-if="orientation === 'horizontal'"
        class="ruler-board horizontal"
        :style="{ width: '100%', height: rulerHeight + 'px' }"
        @touchstart="onTouchStart"
        @touchmove="onTouchMove"
        @touchend="onTouchEnd"
        @mousedown="onMouseDown"
        @mousemove="onMouseMove"
        @mouseup="onMouseUp"
      >
        <!-- 刻度尺 -->
        <canvas ref="rulerCanvas" class="ruler-canvas"></canvas>

        <!-- 测量指针 A -->
        <div
          class="ruler-pointer pointer-a"
          :style="{ left: pointerA + 'px' }"
          @touchstart.stop="dragPointer('a', $event)"
          @mousedown.stop="dragPointer('a', $event)"
        >
          <div class="pointer-line"></div>
          <div class="pointer-handle">A</div>
          <div class="pointer-value">{{ getPointerValue(pointerA) }}</div>
        </div>

        <!-- 测量指针 B -->
        <div
          class="ruler-pointer pointer-b"
          :style="{ left: pointerB + 'px' }"
          @touchstart.stop="dragPointer('b', $event)"
          @mousedown.stop="dragPointer('b', $event)"
        >
          <div class="pointer-line"></div>
          <div class="pointer-handle">B</div>
          <div class="pointer-value">{{ getPointerValue(pointerB) }}</div>
        </div>

        <!-- 测量距离显示 -->
        <div
          class="measure-result"
          :style="{
            left: Math.min(pointerA, pointerB) + 'px',
            width: Math.abs(pointerB - pointerA) + 'px'
          }"
        >
          <div class="measure-line"></div>
          <div class="measure-value-bubble">
            <span class="measure-num">{{ distance }}</span>
            <span class="measure-unit">{{ unit }}</span>
          </div>
        </div>
      </div>

      <!-- 大值显示 -->
      <div class="ruler-display">
        <div class="display-main">
          <span class="display-number">{{ distance }}</span>
          <span class="display-unit">{{ unit }}</span>
        </div>
        <div class="display-sub" v-if="unit === 'cm'">
          ≈ {{ cmToInch }} 英寸
        </div>
        <div class="display-sub" v-else>
          ≈ {{ inchToCm }} 厘米
        </div>
      </div>

      <!-- 操作按钮 -->
      <div class="ruler-actions">
        <button class="action-pill" @click="resetPointers">
          <span>↺</span> 重置
        </button>
        <button class="action-pill" @click="toggleOrientation">
          <span>⟲</span> {{ orientation === 'horizontal' ? '垂直' : '水平' }}
        </button>
        <button class="action-pill" :class="{ vip: !store.isMember.value }" @click="saveRecord">
          <span>💾</span> 保存
        </button>
        <button class="action-pill" :class="{ vip: !store.isMember.value }" @click="screenshot">
          <span>📷</span> 截图
        </button>
      </div>

      <!-- 校准提示 -->
      <div class="calibrate-tip">
        <span class="tip-icon">💡</span>
        <span>当前PPI: {{ ppi.toFixed(0) }} | 拖动A/B标记点测量</span>
      </div>

      <!-- 参考对象 -->
      <div class="reference-objects">
        <div class="ref-title">参考对象 (点击校准)</div>
        <div class="ref-grid">
          <div class="ref-item" v-for="ref in references" :key="ref.name" @click="calibrateByRef(ref)">
            <span class="ref-emoji">{{ ref.emoji }}</span>
            <span class="ref-name">{{ ref.name }}</span>
            <span class="ref-size">{{ ref.size }}cm</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 校准弹窗 -->
    <div class="modal-overlay" v-if="showCalibrate" @click.self="showCalibrate = false">
      <div class="modal-panel">
        <div class="modal-handle"></div>
        <h3 class="modal-title">手动校准</h3>
        <p class="modal-desc">将一个已知长度的物体放在尺子上，输入实际长度来校准</p>

        <div class="calibrate-input-section">
          <label class="input-label">当前测量距离</label>
          <div class="input-group">
            <input
              type="number"
              v-model="knownLength"
              placeholder="输入已知物体长度（cm）"
              class="calibrate-input"
              step="0.1"
            >
            <span class="input-suffix">cm</span>
          </div>
        </div>

        <div class="quick-refs">
          <div class="ref-btn" v-for="ref in references" :key="ref.name" @click="knownLength = ref.size">
            {{ ref.emoji }} {{ ref.name }} {{ ref.size }}cm
          </div>
        </div>

        <button class="btn btn-primary" style="width:100%" @click="applyCalibration">
          应用校准
        </button>
      </div>
    </div>

    <!-- VIP弹窗 -->
    <VipModal v-if="store.state.showVipModal" @close="store.state.showVipModal = false" />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'
import store from '../store/index.js'
import VipModal from '../components/VipModal.vue'

// ===== 状态 =====
const unit = ref('cm')
const orientation = ref('horizontal')
const ppi = ref(96)
const pointerA = ref(60)
const pointerB = ref(200)
const showCalibrate = ref(false)
const knownLength = ref('')
const rulerCanvas = ref(null)
const rulerContainer = ref(null)
const rulerHeight = ref(280)

// 参考对象
const references = [
  { name: '银行卡宽', size: 8.5, emoji: '💳' },
  { name: 'A4纸宽', size: 21, emoji: '📄' },
  { name: '身份证宽', size: 8.56, emoji: '🪪' },
  { name: '硬币（1元）', size: 2.5, emoji: '🪙' },
]

// ===== 计算 =====
const pxPerCm = computed(() => ppi.value / 2.54)

const distance = computed(() => {
  const px = Math.abs(pointerB.value - pointerA.value)
  if (unit.value === 'cm') return (px / pxPerCm.value).toFixed(2)
  return (px / pxPerCm.value / 2.54).toFixed(3)
})

const cmToInch = computed(() => (parseFloat(distance.value) / 2.54).toFixed(3))
const inchToCm = computed(() => (parseFloat(distance.value) * 2.54).toFixed(2))

function getPointerValue(px) {
  if (unit.value === 'cm') return (px / pxPerCm.value).toFixed(1) + 'cm'
  return (px / pxPerCm.value / 2.54).toFixed(2) + '"'
}

// ===== PPI检测 =====
function detectPPI() {
  // 尝试通过 devicePixelRatio 和已知屏幕尺寸推算
  const dpr = window.devicePixelRatio || 1
  const sw = screen.width * dpr
  // 使用常见 PPI 预设
  const width = window.screen.width
  if (width >= 375 && width <= 414) ppi.value = 326 // iPhone
  else if (width >= 360 && width <= 412) ppi.value = 420 // Android 高清
  else ppi.value = Math.round(96 * dpr)
}

// ===== 绘制刻度尺 =====
function drawRuler() {
  const canvas = rulerCanvas.value
  if (!canvas) return

  const container = rulerContainer.value
  const W = container.clientWidth
  const H = rulerHeight.value

  canvas.width = W
  canvas.height = H

  const ctx = canvas.getContext('2d')
  ctx.clearRect(0, 0, W, H)

  // 背景
  const bgGrad = ctx.createLinearGradient(0, 0, 0, H)
  bgGrad.addColorStop(0, '#1a1a2e')
  bgGrad.addColorStop(1, '#16213e')
  ctx.fillStyle = bgGrad
  ctx.fillRect(0, 0, W, H)

  // 网格线
  ctx.strokeStyle = 'rgba(108,99,255,0.05)'
  ctx.lineWidth = 1
  for (let y = 0; y < H; y += 20) {
    ctx.beginPath()
    ctx.moveTo(0, y)
    ctx.lineTo(W, y)
    ctx.stroke()
  }

  const cmW = pxPerCm.value
  const totalCm = W / cmW
  const unitLabel = unit.value

  // 刻度
  for (let i = 0; i <= totalCm * 10; i++) {
    const x = i * cmW / 10
    const isCm = i % 10 === 0
    const isHalf = i % 5 === 0
    const tickH = isCm ? 50 : isHalf ? 35 : 20

    // 刻度线
    const grad = ctx.createLinearGradient(0, 0, 0, tickH)
    if (isCm) {
      grad.addColorStop(0, '#6C63FF')
      grad.addColorStop(1, 'rgba(108,99,255,0.3)')
    } else {
      grad.addColorStop(0, 'rgba(255,255,255,0.5)')
      grad.addColorStop(1, 'rgba(255,255,255,0.1)')
    }

    ctx.strokeStyle = grad
    ctx.lineWidth = isCm ? 2 : 1
    ctx.beginPath()
    ctx.moveTo(x, 0)
    ctx.lineTo(x, tickH)
    ctx.stroke()

    // 数字标签
    if (isCm && i > 0) {
      const label = unitLabel === 'cm' ? (i / 10).toFixed(0) : (i / 10 / 2.54).toFixed(1)
      ctx.fillStyle = 'rgba(255,255,255,0.7)'
      ctx.font = '600 11px -apple-system, sans-serif'
      ctx.textAlign = 'center'
      ctx.fillText(label, x, tickH + 14)
    }
  }

  // 顶部边线
  const lineGrad = ctx.createLinearGradient(0, 0, W, 0)
  lineGrad.addColorStop(0, 'transparent')
  lineGrad.addColorStop(0.2, '#6C63FF')
  lineGrad.addColorStop(0.8, '#6C63FF')
  lineGrad.addColorStop(1, 'transparent')
  ctx.strokeStyle = lineGrad
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(0, 0)
  ctx.lineTo(W, 0)
  ctx.stroke()

  // 测量区域高亮
  const aX = Math.min(pointerA.value, pointerB.value)
  const bX = Math.max(pointerA.value, pointerB.value)
  const hlGrad = ctx.createLinearGradient(aX, 0, bX, 0)
  hlGrad.addColorStop(0, 'rgba(108,99,255,0)')
  hlGrad.addColorStop(0.3, 'rgba(108,99,255,0.15)')
  hlGrad.addColorStop(0.7, 'rgba(108,99,255,0.15)')
  hlGrad.addColorStop(1, 'rgba(108,99,255,0)')
  ctx.fillStyle = hlGrad
  ctx.fillRect(aX, 0, bX - aX, H)
}

// ===== 拖拽逻辑 =====
let dragging = null
let dragStartX = 0
let pointerStartX = 0

function dragPointer(which, event) {
  dragging = which
  const touch = event.touches?.[0] || event
  dragStartX = touch.clientX
  pointerStartX = which === 'a' ? pointerA.value : pointerB.value

  window.addEventListener('touchmove', onDragMove, { passive: false })
  window.addEventListener('touchend', onDragEnd)
  window.addEventListener('mousemove', onDragMove)
  window.addEventListener('mouseup', onDragEnd)
}

function onDragMove(event) {
  event.preventDefault?.()
  if (!dragging) return
  const touch = event.touches?.[0] || event
  const dx = touch.clientX - dragStartX
  const container = rulerContainer.value
  const maxX = container ? container.clientWidth - 10 : 400
  const newX = Math.max(10, Math.min(maxX, pointerStartX + dx))

  if (dragging === 'a') pointerA.value = newX
  else pointerB.value = newX

  drawRuler()
}

function onDragEnd() {
  dragging = null
  window.removeEventListener('touchmove', onDragMove)
  window.removeEventListener('touchend', onDragEnd)
  window.removeEventListener('mousemove', onDragMove)
  window.removeEventListener('mouseup', onDragEnd)
}

// ===== 其他功能 =====
function toggleUnit() {
  unit.value = unit.value === 'cm' ? 'inch' : 'cm'
  drawRuler()
}

function toggleOrientation() {
  orientation.value = orientation.value === 'horizontal' ? 'vertical' : 'horizontal'
}

function resetPointers() {
  const container = rulerContainer.value
  const W = container ? container.clientWidth : 400
  pointerA.value = 60
  pointerB.value = Math.min(W - 60, 250)
  drawRuler()
  store.showToast('已重置测量点')
}

function applyCalibration() {
  const len = parseFloat(knownLength.value)
  if (!len || len <= 0) {
    store.showToast('请输入有效长度')
    return
  }
  const px = Math.abs(pointerB.value - pointerA.value)
  const newPxPerCm = px / len
  ppi.value = newPxPerCm * 2.54
  showCalibrate.value = false
  knownLength.value = ''
  drawRuler()
  store.showToast('✅ 校准成功！')
}

function calibrateByRef(ref) {
  knownLength.value = ref.size
  showCalibrate.value = true
}

function saveRecord() {
  if (!store.requireMember('测量历史')) return
  store.incrementUsage()
  store.saveHistory({
    type: 'ruler',
    value: distance.value,
    unit: unit.value
  })
  store.showToast('✅ 已保存测量记录')
}

function screenshot() {
  if (!store.requireMember('截图功能')) return
  store.showToast('📷 截图功能开发中...')
}

// ===== 生命周期 =====
onMounted(async () => {
  detectPPI()
  await nextTick()
  const container = rulerContainer.value
  if (container) {
    pointerB.value = Math.min(container.clientWidth - 60, 250)
  }
  drawRuler()
  window.addEventListener('resize', drawRuler)
})

onUnmounted(() => {
  window.removeEventListener('resize', drawRuler)
})
</script>

<style scoped>
.ruler-page {
  background: #0f0f1a;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.ruler-topbar {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: rgba(15,15,26,0.95);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  gap: 12px;
  flex-shrink: 0;
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

.back-btn:active {
  background: rgba(255,255,255,0.15);
}

.page-title {
  flex: 1;
  font-size: 18px;
  font-weight: 700;
  color: white;
}

.topbar-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 7px 14px;
  border-radius: var(--radius-md);
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.3);
  color: var(--primary-light);
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn.active {
  background: var(--primary);
  color: white;
  box-shadow: 0 4px 12px rgba(108,99,255,0.4);
}

/* 尺子主体 */
.ruler-container {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 30px;
}

.ruler-board {
  position: relative;
  background: #1a1a2e;
  overflow: hidden;
  cursor: crosshair;
  touch-action: none;
}

.ruler-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

/* 测量指针 */
.ruler-pointer {
  position: absolute;
  top: 0;
  bottom: 0;
  transform: translateX(-50%);
  z-index: 10;
  cursor: ew-resize;
  touch-action: none;
}

.pointer-line {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 50%;
  width: 2px;
  transform: translateX(-50%);
  background: var(--danger);
  box-shadow: 0 0 8px rgba(255,71,87,0.6);
}

.pointer-handle {
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 32px;
  height: 32px;
  background: var(--danger);
  border-radius: 50% 50% 50% 0;
  rotate: -45deg;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 800;
  color: white;
  box-shadow: 0 4px 12px rgba(255,71,87,0.5);
  cursor: grab;
}

.pointer-handle:active {
  cursor: grabbing;
}

.pointer-b .pointer-line {
  background: var(--accent);
  box-shadow: 0 0 8px rgba(43,233,123,0.6);
}

.pointer-b .pointer-handle {
  background: var(--accent);
  box-shadow: 0 4px 12px rgba(43,233,123,0.5);
  border-radius: 50% 50% 0 50%;
  rotate: 45deg;
}

.pointer-value {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(20,20,40,0.9);
  border: 1px solid rgba(255,255,255,0.1);
  color: white;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
  backdrop-filter: blur(8px);
}

/* 测量结果 */
.measure-result {
  position: absolute;
  top: 0;
  bottom: 0;
  z-index: 5;
  pointer-events: none;
}

.measure-line {
  position: absolute;
  height: 2px;
  top: 50%;
  left: 0;
  right: 0;
  background: rgba(108,99,255,0.4);
  transform: translateY(-50%);
}

.measure-value-bubble {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: linear-gradient(135deg, rgba(108,99,255,0.9), rgba(108,99,255,0.7));
  backdrop-filter: blur(12px);
  border: 1px solid rgba(108,99,255,0.5);
  border-radius: var(--radius-full);
  padding: 6px 14px;
  white-space: nowrap;
  box-shadow: 0 4px 20px rgba(108,99,255,0.4);
}

.measure-num {
  font-size: 18px;
  font-weight: 800;
  color: white;
  font-variant-numeric: tabular-nums;
}

.measure-unit {
  font-size: 12px;
  color: rgba(255,255,255,0.8);
  margin-left: 3px;
}

/* 大值显示 */
.ruler-display {
  padding: 20px;
  text-align: center;
  background: linear-gradient(180deg, rgba(108,99,255,0.08) 0%, transparent 100%);
}

.display-main {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 6px;
}

.display-number {
  font-size: 56px;
  font-weight: 900;
  background: linear-gradient(135deg, #fff, #a0a0ff);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-variant-numeric: tabular-nums;
  line-height: 1;
}

.display-unit {
  font-size: 22px;
  color: var(--primary-light);
  font-weight: 600;
}

.display-sub {
  font-size: 13px;
  color: var(--text-muted);
  margin-top: 4px;
}

/* 操作按钮 */
.ruler-actions {
  display: flex;
  gap: 10px;
  padding: 0 20px 16px;
  overflow-x: auto;
}

.action-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-full);
  color: white;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  cursor: pointer;
  transition: var(--transition);
  flex-shrink: 0;
}

.action-pill:active {
  transform: scale(0.97);
  background: rgba(255,255,255,0.12);
}

.action-pill.vip {
  border-color: rgba(255,215,0,0.3);
  color: #FFD700;
}

/* 校准提示 */
.calibrate-tip {
  margin: 0 20px 16px;
  padding: 10px 14px;
  background: rgba(108,99,255,0.08);
  border: 1px solid rgba(108,99,255,0.2);
  border-radius: var(--radius-md);
  font-size: 12px;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 8px;
}

/* 参考对象 */
.reference-objects {
  padding: 0 20px;
}

.ref-title {
  font-size: 12px;
  color: var(--text-muted);
  margin-bottom: 10px;
  letter-spacing: 1px;
  text-transform: uppercase;
}

.ref-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.ref-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: var(--transition);
}

.ref-item:active {
  background: rgba(108,99,255,0.15);
  border-color: rgba(108,99,255,0.3);
}

.ref-emoji { font-size: 18px; }
.ref-name { font-size: 12px; color: var(--text-secondary); flex: 1; }
.ref-size { font-size: 12px; color: var(--primary-light); font-weight: 700; }

/* 校准弹窗 */
.modal-title {
  font-size: 20px;
  font-weight: 800;
  margin-bottom: 8px;
}

.modal-desc {
  font-size: 13px;
  color: var(--text-secondary);
  margin-bottom: 20px;
  line-height: 1.6;
}

.calibrate-input-section {
  margin-bottom: 16px;
}

.input-label {
  font-size: 12px;
  color: var(--text-muted);
  display: block;
  margin-bottom: 8px;
  letter-spacing: 0.5px;
}

.input-group {
  display: flex;
  align-items: center;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: var(--radius-md);
  overflow: hidden;
}

.calibrate-input {
  flex: 1;
  background: transparent;
  border: none;
  padding: 14px 16px;
  color: white;
  font-size: 16px;
  outline: none;
}

.calibrate-input::placeholder {
  color: var(--text-muted);
}

.input-suffix {
  padding: 0 16px;
  color: var(--text-secondary);
  font-weight: 600;
}

.quick-refs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}

.ref-btn {
  padding: 7px 12px;
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-full);
  font-size: 12px;
  color: var(--text-secondary);
  cursor: pointer;
  transition: var(--transition);
}

.ref-btn:active {
  background: rgba(108,99,255,0.2);
  color: var(--primary-light);
}
</style>
