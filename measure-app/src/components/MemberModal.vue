<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-panel member-modal">
      <div class="modal-handle"></div>

      <!-- 头部 -->
      <div class="member-header">
        <div class="member-banner">
          <div class="banner-glow"></div>
          <div class="banner-content">
            <span class="banner-icon">👑</span>
            <div>
              <div class="banner-title">MeasureMaster 会员</div>
              <div class="banner-sub">专业测量工具，无限可能</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 套餐选择 -->
      <div class="plan-section">
        <div class="plan-title">选择套餐</div>
        <div class="plans-grid">
          <div
            v-for="plan in plans"
            :key="plan.type"
            class="plan-card"
            :class="{ selected: selectedPlan === plan.type, recommended: plan.recommended }"
            @click="selectedPlan = plan.type"
          >
            <div class="plan-badge" v-if="plan.recommended">推荐</div>
            <div class="plan-name">{{ plan.name }}</div>
            <div class="plan-duration">{{ plan.duration }}</div>
            <div class="plan-price">
              <span class="price-symbol">¥</span>
              <span class="price-num">{{ plan.price }}</span>
            </div>
            <div class="plan-daily" v-if="plan.daily">约¥{{ plan.daily }}/天</div>
            <div class="plan-save" v-if="plan.save">节省 {{ plan.save }}</div>
          </div>
        </div>
      </div>

      <!-- 权益展示 -->
      <div class="benefits-section">
        <div class="benefits-title">会员专属权益</div>
        <div class="benefits-grid">
          <div class="benefit-chip" v-for="b in benefits" :key="b">
            <span>✓</span> {{ b }}
          </div>
        </div>
      </div>

      <!-- 支付方式 -->
      <div class="pay-section">
        <div class="pay-title">选择支付方式</div>
        <div class="pay-options">
          <div
            class="pay-option"
            :class="{ selected: payType === 'wechat' }"
            @click="payType = 'wechat'"
          >
            <span>💚</span>
            <span>微信支付</span>
          </div>
          <div
            class="pay-option"
            :class="{ selected: payType === 'alipay' }"
            @click="payType = 'alipay'"
          >
            <span>🔵</span>
            <span>支付宝</span>
          </div>
        </div>
      </div>

      <!-- 支付按钮 -->
      <button class="pay-btn" @click="purchase" :disabled="isPaying">
        <span v-if="isPaying" class="loading-spinner" style="width:20px;height:20px;border-width:2px;"></span>
        <span v-else>
          立即支付 ¥{{ selectedPlanInfo?.price }}
          <span class="pay-btn-sub">{{ selectedPlanInfo?.name }}·{{ selectedPlanInfo?.duration }}</span>
        </span>
      </button>

      <div class="pay-note">
        支付即代表同意《会员服务协议》，自动续费可随时取消
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import store from '../store/index.js'

const emit = defineEmits(['close'])

const selectedPlan = ref(2)
const payType = ref('wechat')
const isPaying = ref(false)

const plans = [
  { type: 1, name: '月度会员', duration: '30天', price: 12, daily: '0.4', recommended: false },
  { type: 2, name: '季度会员', duration: '90天', price: 30, daily: '0.33', save: '¥6', recommended: true },
  { type: 3, name: '年度会员', duration: '365天', price: 88, daily: '0.24', save: '¥56', recommended: false },
  { type: 4, name: '永久会员', duration: '永久', price: 198, daily: null, save: '最划算', recommended: false },
]

const benefits = [
  '无限次测量', '历史记录保存', '云端数据同步', '去除全部广告',
  '专业测量模式', '数据报告导出', '角度锁定功能', '噪音预警设置'
]

const selectedPlanInfo = computed(() => plans.find(p => p.type === selectedPlan.value))

async function purchase() {
  isPaying.value = true
  // 模拟支付过程
  await new Promise(resolve => setTimeout(resolve, 1500))
  isPaying.value = false

  if (!store.state.user) {
    store.login({ nickname: '新用户' })
  }
  store.activateMember(selectedPlan.value)
  emit('close')
}
</script>

<style scoped>
.member-modal {
  background: linear-gradient(180deg, #120d28 0%, var(--bg-card) 100%);
}

/* 头部横幅 */
.member-header {
  margin-bottom: 20px;
}

.member-banner {
  position: relative;
  padding: 16px;
  background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(108,99,255,0.15));
  border: 1px solid rgba(255,215,0,0.2);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.banner-glow {
  position: absolute;
  top: -30px;
  right: -30px;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(255,215,0,0.2), transparent);
  filter: blur(20px);
}

.banner-content {
  display: flex;
  align-items: center;
  gap: 14px;
  position: relative;
}

.banner-icon {
  font-size: 36px;
  filter: drop-shadow(0 0 12px rgba(255,215,0,0.5));
}

.banner-title {
  font-size: 16px;
  font-weight: 800;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.banner-sub {
  font-size: 12px;
  color: rgba(255,215,0,0.6);
  margin-top: 2px;
}

/* 套餐 */
.plan-section {
  margin-bottom: 18px;
}

.plan-title, .benefits-title, .pay-title {
  font-size: 12px;
  font-weight: 700;
  color: var(--text-muted);
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 10px;
}

.plans-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.plan-card {
  position: relative;
  padding: 12px 8px;
  background: rgba(255,255,255,0.04);
  border: 2px solid rgba(255,255,255,0.08);
  border-radius: var(--radius-lg);
  text-align: center;
  cursor: pointer;
  transition: var(--transition);
  overflow: hidden;
}

.plan-card:active {
  transform: scale(0.97);
}

.plan-card.selected {
  border-color: var(--primary);
  background: rgba(108,99,255,0.12);
  box-shadow: 0 0 0 1px rgba(108,99,255,0.4);
}

.plan-card.recommended.selected {
  border-color: #FFD700;
  background: rgba(255,215,0,0.08);
}

.plan-badge {
  position: absolute;
  top: -1px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #FFD700, #FFA500);
  color: #1a0f00;
  font-size: 9px;
  font-weight: 800;
  padding: 2px 8px;
  border-radius: 0 0 8px 8px;
}

.plan-name {
  font-size: 11px;
  color: var(--text-secondary);
  margin-bottom: 2px;
  margin-top: 8px;
}

.plan-duration {
  font-size: 10px;
  color: var(--text-muted);
  margin-bottom: 6px;
}

.plan-price {
  display: flex;
  align-items: baseline;
  justify-content: center;
  gap: 2px;
}

.price-symbol {
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-light);
}

.price-num {
  font-size: 22px;
  font-weight: 900;
  color: white;
}

.plan-daily {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 3px;
}

.plan-save {
  font-size: 10px;
  color: var(--accent);
  font-weight: 700;
}

/* 权益 */
.benefits-section {
  margin-bottom: 18px;
}

.benefits-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.benefit-chip {
  padding: 5px 10px;
  background: rgba(67,233,123,0.08);
  border: 1px solid rgba(67,233,123,0.15);
  border-radius: var(--radius-full);
  font-size: 11px;
  color: var(--accent);
  display: flex;
  align-items: center;
  gap: 4px;
}

/* 支付方式 */
.pay-section {
  margin-bottom: 16px;
}

.pay-options {
  display: flex;
  gap: 10px;
}

.pay-option {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px;
  background: rgba(255,255,255,0.05);
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.pay-option.selected {
  border-color: var(--primary);
  background: rgba(108,99,255,0.12);
}

/* 支付按钮 */
.pay-btn {
  width: 100%;
  padding: 16px;
  background: linear-gradient(135deg, #FFD700, #FFA500);
  border: none;
  border-radius: var(--radius-lg);
  font-size: 16px;
  font-weight: 800;
  color: #1a0f00;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  box-shadow: 0 6px 24px rgba(255,215,0,0.3);
  margin-bottom: 10px;
}

.pay-btn:active {
  transform: scale(0.99);
  box-shadow: 0 3px 12px rgba(255,215,0,0.2);
}

.pay-btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.pay-btn-sub {
  font-size: 11px;
  font-weight: 600;
  opacity: 0.7;
}

.pay-note {
  text-align: center;
  font-size: 11px;
  color: var(--text-muted);
}
</style>
