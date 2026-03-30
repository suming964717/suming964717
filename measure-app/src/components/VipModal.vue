<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-panel vip-modal">
      <div class="modal-handle"></div>

      <div class="vip-modal-header">
        <div class="crown-anim">👑</div>
        <h2>解锁会员特权</h2>
        <p>升级会员，享受更多专业功能</p>
      </div>

      <div class="perks-list">
        <div class="perk-item" v-for="perk in perks" :key="perk.title">
          <div class="perk-icon">{{ perk.icon }}</div>
          <div class="perk-info">
            <div class="perk-title">{{ perk.title }}</div>
            <div class="perk-desc">{{ perk.desc }}</div>
          </div>
          <span class="perk-check">✓</span>
        </div>
      </div>

      <button class="btn btn-primary btn-lg" style="width:100%" @click="openMember">
        立即开通会员
      </button>
      <button class="skip-btn" @click="$emit('close')">暂不开通</button>
    </div>
  </div>
</template>

<script setup>
import store from '../store/index.js'
const emit = defineEmits(['close'])

const perks = [
  { icon: '⚡', title: '无限测量次数', desc: '不受每日10次限制' },
  { icon: '📋', title: '历史记录云保存', desc: '随时查看历史测量数据' },
  { icon: '🚫', title: '去除广告', desc: '纯净使用体验' },
  { icon: '📊', title: '数据报告导出', desc: '导出PDF/图片格式报告' },
  { icon: '🔒', title: '角度锁定', desc: '水平仪锁定参考角度' },
  { icon: '⚠️', title: '噪音预警', desc: '自定义分贝阈值提醒' },
]

function openMember() {
  emit('close')
  store.state.showMemberModal = true
}
</script>

<style scoped>
.vip-modal {
  background: linear-gradient(180deg, #1a1030 0%, var(--bg-card) 100%);
}

.vip-modal-header {
  text-align: center;
  margin-bottom: 20px;
}

.crown-anim {
  font-size: 48px;
  margin-bottom: 10px;
  animation: bounceIn 0.5s ease;
  filter: drop-shadow(0 0 20px rgba(255,215,0,0.5));
}

.vip-modal-header h2 {
  font-size: 22px;
  font-weight: 900;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 6px;
}

.vip-modal-header p {
  font-size: 13px;
  color: var(--text-secondary);
}

.perks-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 20px;
}

.perk-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  background: rgba(255,215,0,0.05);
  border: 1px solid rgba(255,215,0,0.12);
  border-radius: var(--radius-md);
}

.perk-icon { font-size: 20px; width: 28px; text-align: center; }

.perk-info { flex: 1; }

.perk-title {
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.perk-desc {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 1px;
}

.perk-check {
  color: var(--accent);
  font-weight: 800;
  font-size: 16px;
}

.skip-btn {
  width: 100%;
  padding: 12px;
  background: transparent;
  border: none;
  color: var(--text-muted);
  font-size: 13px;
  cursor: pointer;
  margin-top: 8px;
}
</style>
