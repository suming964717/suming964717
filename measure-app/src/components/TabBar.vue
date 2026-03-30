<template>
  <div class="tab-bar">
    <div
      v-for="tab in tabs"
      :key="tab.key"
      class="tab-item"
      :class="{ active: currentPage === tab.key }"
      @click="navigate(tab.key)"
    >
      <span class="tab-icon">{{ tab.icon }}</span>
      <span class="tab-label">{{ tab.label }}</span>
      <span v-if="tab.key === 'user' && store.isMember.value" class="tab-vip-dot"></span>
    </div>
  </div>
</template>

<script setup>
import store from '../store/index.js'
import { computed } from 'vue'

const currentPage = computed(() => store.state.currentPage)

const tabs = [
  { key: 'home', icon: '🏠', label: '首页' },
  { key: 'ruler', icon: '📏', label: '尺子' },
  { key: 'level', icon: '🫧', label: '水平仪' },
  { key: 'decibel', icon: '🎙️', label: '分贝仪' },
  { key: 'user', icon: '👤', label: '我的' },
]

function navigate(key) {
  if (['ruler', 'level', 'decibel'].includes(key)) {
    if (!store.canUse()) {
      store.state.showVipModal = true
      return
    }
  }
  store.navigate(key)
}
</script>

<style scoped>
.tab-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 70px;
  background: rgba(12, 12, 22, 0.97);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-top: 1px solid rgba(255,255,255,0.07);
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding-bottom: env(safe-area-inset-bottom, 0px);
  z-index: 100;
}

.tab-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  padding: 6px 16px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  border-radius: 12px;
  position: relative;
  min-width: 56px;
}

.tab-icon {
  font-size: 22px;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  display: block;
}

.tab-label {
  font-size: 10px;
  color: var(--text-muted);
  transition: all 0.25s ease;
  font-weight: 600;
}

.tab-item.active .tab-icon {
  transform: translateY(-3px) scale(1.1);
  filter: drop-shadow(0 4px 8px rgba(108,99,255,0.6));
}

.tab-item.active .tab-label {
  color: #8B85FF;
  font-weight: 800;
}

.tab-item.active {
  background: rgba(108,99,255,0.08);
}

.tab-vip-dot {
  position: absolute;
  top: 4px;
  right: 10px;
  width: 8px;
  height: 8px;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  border-radius: 50%;
  border: 1.5px solid rgba(12,12,22,0.97);
}
</style>
