<template>
  <div class="marketplace-page page">
    <!-- 背景装饰 -->
    <div class="bg-decoration">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
    </div>

    <!-- 顶部栏 -->
    <header class="mp-header">
      <div class="mp-header-title">
        <span class="mp-header-icon">🧩</span>
        <div>
          <div class="mp-title">插件市场</div>
          <div class="mp-subtitle">扩展你的测量工具箱</div>
        </div>
      </div>
      <div class="mp-installed-badge" @click="activeTab = 'installed'">
        <span>已安装 {{ installedCount }}</span>
      </div>
    </header>

    <!-- 搜索栏 -->
    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input
        v-model="searchQuery"
        class="search-input"
        type="text"
        placeholder="搜索插件..."
      />
      <button v-if="searchQuery" class="search-clear" @click="searchQuery = ''">✕</button>
    </div>

    <!-- 标签页 -->
    <div class="tab-switcher">
      <button
        class="switcher-btn"
        :class="{ active: activeTab === 'all' }"
        @click="activeTab = 'all'"
      >全部</button>
      <button
        class="switcher-btn"
        :class="{ active: activeTab === 'installed' }"
        @click="activeTab = 'installed'"
      >已安装</button>
      <button
        v-for="cat in categories"
        :key="cat.key"
        class="switcher-btn"
        :class="{ active: activeTab === cat.key }"
        @click="activeTab = cat.key"
      >{{ cat.label }}</button>
    </div>

    <!-- 精选横幅（仅全部模式且无搜索时显示） -->
    <div v-if="activeTab === 'all' && !searchQuery" class="featured-banner">
      <div class="featured-icon">⭐</div>
      <div class="featured-text">
        <div class="featured-title">本周推荐：量角器</div>
        <div class="featured-desc">精准测量任意角度，支持实时相机叠加</div>
      </div>
      <button class="featured-btn" @click="toggleInstall(featuredPlugin)">
        {{ isInstalled(featuredPlugin.id) ? '已安装' : '安装' }}
      </button>
    </div>

    <!-- 插件列表 -->
    <div class="plugins-section">
      <div class="section-title">
        {{ sectionTitle }}
        <span class="section-count">{{ filteredPlugins.length }}</span>
      </div>

      <div v-if="filteredPlugins.length === 0" class="empty-state">
        <div class="empty-icon">📦</div>
        <div class="empty-text">{{ emptyText }}</div>
      </div>

      <div class="plugins-list">
        <div
          v-for="plugin in filteredPlugins"
          :key="plugin.id"
          class="plugin-card"
          :class="{ 'plugin-installed': isInstalled(plugin.id) }"
        >
          <div class="plugin-icon-wrap" :style="{ background: plugin.color }">
            <span class="plugin-icon">{{ plugin.icon }}</span>
          </div>
          <div class="plugin-body">
            <div class="plugin-top">
              <div class="plugin-name">{{ plugin.name }}</div>
              <div class="plugin-badges">
                <span v-if="plugin.isNew" class="badge badge-new">NEW</span>
                <span v-if="plugin.isPro" class="badge badge-pro">PRO</span>
                <span v-if="isInstalled(plugin.id)" class="badge badge-installed">已安装</span>
              </div>
            </div>
            <div class="plugin-desc">{{ plugin.desc }}</div>
            <div class="plugin-meta">
              <span class="plugin-category">{{ plugin.categoryLabel }}</span>
              <span class="plugin-rating">⭐ {{ plugin.rating }}</span>
              <span class="plugin-downloads">↓ {{ plugin.downloads }}</span>
            </div>
          </div>
          <button
            class="install-btn"
            :class="{
              'btn-installed': isInstalled(plugin.id),
              'btn-pro': plugin.isPro && !store.isMember.value,
            }"
            @click="toggleInstall(plugin)"
          >
            <span v-if="isInstalled(plugin.id)">卸载</span>
            <span v-else-if="plugin.isPro && !store.isMember.value">🔒</span>
            <span v-else>安装</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 已安装插件的快速访问区 -->
    <div v-if="activeTab !== 'installed' && installedPlugins.length > 0 && !searchQuery" class="installed-section">
      <div class="section-title">已安装的插件</div>
      <div class="installed-chips">
        <div
          v-for="plugin in installedPlugins"
          :key="plugin.id"
          class="installed-chip"
          @click="openPlugin(plugin)"
        >
          <span>{{ plugin.icon }}</span>
          <span>{{ plugin.name }}</span>
        </div>
      </div>
    </div>

    <div style="height: 100px;"></div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import store from '../store/index.js'

const searchQuery = ref('')
const activeTab = ref('all')

const categories = [
  { key: 'measure', label: '测量' },
  { key: 'sensor', label: '传感器' },
  { key: 'utility', label: '工具' },
]

const allPlugins = [
  {
    id: 'protractor',
    name: '量角器',
    icon: '📐',
    desc: '通过相机实时测量角度，支持叠加显示',
    category: 'measure',
    categoryLabel: '测量',
    color: 'linear-gradient(135deg,rgba(108,99,255,0.6),rgba(108,99,255,0.2))',
    rating: '4.9',
    downloads: '12.3k',
    isNew: true,
    isPro: false,
  },
  {
    id: 'compass',
    name: '指南针',
    icon: '🧭',
    desc: '利用磁力计精准指示方向，支持真北与磁北',
    category: 'sensor',
    categoryLabel: '传感器',
    color: 'linear-gradient(135deg,rgba(67,233,123,0.6),rgba(67,233,123,0.2))',
    rating: '4.7',
    downloads: '9.8k',
    isNew: false,
    isPro: false,
  },
  {
    id: 'speedometer',
    name: '速度计',
    icon: '🏎️',
    desc: '基于 GPS 实时显示移动速度，支持 km/h 与 mph',
    category: 'sensor',
    categoryLabel: '传感器',
    color: 'linear-gradient(135deg,rgba(255,101,132,0.6),rgba(255,101,132,0.2))',
    rating: '4.5',
    downloads: '7.2k',
    isNew: false,
    isPro: false,
  },
  {
    id: 'altimeter',
    name: '海拔仪',
    icon: '⛰️',
    desc: '实时获取当前海拔高度，精度 ±3m',
    category: 'sensor',
    categoryLabel: '传感器',
    color: 'linear-gradient(135deg,rgba(255,200,60,0.6),rgba(255,200,60,0.2))',
    rating: '4.6',
    downloads: '5.4k',
    isNew: false,
    isPro: true,
  },
  {
    id: 'area-calc',
    name: '面积计算',
    icon: '📦',
    desc: '在屏幕上框选区域，自动换算实际面积',
    category: 'measure',
    categoryLabel: '测量',
    color: 'linear-gradient(135deg,rgba(0,210,255,0.6),rgba(0,210,255,0.2))',
    rating: '4.8',
    downloads: '8.1k',
    isNew: true,
    isPro: true,
  },
  {
    id: 'timer',
    name: '精密计时器',
    icon: '⏱️',
    desc: '毫秒级精度，支持多段计时与数据导出',
    category: 'utility',
    categoryLabel: '工具',
    color: 'linear-gradient(135deg,rgba(255,140,0,0.6),rgba(255,140,0,0.2))',
    rating: '4.4',
    downloads: '4.3k',
    isNew: false,
    isPro: false,
  },
  {
    id: 'magnifier',
    name: '放大镜',
    icon: '🔎',
    desc: '调用相机实现高倍数字放大，最高 10x',
    category: 'utility',
    categoryLabel: '工具',
    color: 'linear-gradient(135deg,rgba(150,100,255,0.6),rgba(150,100,255,0.2))',
    rating: '4.3',
    downloads: '6.7k',
    isNew: false,
    isPro: false,
  },
  {
    id: 'pedometer',
    name: '计步器',
    icon: '🚶',
    desc: '基于加速度计精确计步，统计每日步数',
    category: 'sensor',
    categoryLabel: '传感器',
    color: 'linear-gradient(135deg,rgba(50,200,150,0.6),rgba(50,200,150,0.2))',
    rating: '4.5',
    downloads: '11.0k',
    isNew: false,
    isPro: true,
  },
  {
    id: 'unit-converter',
    name: '单位换算',
    icon: '🔁',
    desc: '长度、重量、温度等 50+ 单位一键换算',
    category: 'utility',
    categoryLabel: '工具',
    color: 'linear-gradient(135deg,rgba(255,80,80,0.6),rgba(255,80,80,0.2))',
    rating: '4.7',
    downloads: '14.5k',
    isNew: false,
    isPro: false,
  },
]

const featuredPlugin = allPlugins[0]

const filteredPlugins = computed(() => {
  let list = allPlugins
  if (activeTab.value === 'installed') {
    list = list.filter(p => isInstalled(p.id))
  } else if (activeTab.value !== 'all') {
    list = list.filter(p => p.category === activeTab.value)
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(p =>
      p.name.toLowerCase().includes(q) ||
      p.desc.toLowerCase().includes(q) ||
      p.categoryLabel.includes(q)
    )
  }
  return list
})

const installedPlugins = computed(() =>
  allPlugins.filter(p => isInstalled(p.id))
)

const installedCount = computed(() => store.state.installedPlugins.length)

const sectionTitle = computed(() => {
  if (activeTab.value === 'installed') return '已安装插件'
  if (activeTab.value === 'all') return '全部插件'
  return categories.find(c => c.key === activeTab.value)?.label + '类插件'
})

const emptyText = computed(() => {
  if (activeTab.value === 'installed') return '暂未安装任何插件，去探索一下吧'
  if (searchQuery.value) return `未找到与 "${searchQuery.value}" 相关的插件`
  return '暂无插件'
})

function isInstalled(id) {
  return store.state.installedPlugins.includes(id)
}

function toggleInstall(plugin) {
  if (plugin.isPro && !store.isMember.value) {
    store.state.showMemberModal = true
    return
  }
  if (isInstalled(plugin.id)) {
    store.uninstallPlugin(plugin.id)
    store.showToast(`已卸载「${plugin.name}」`)
  } else {
    store.installPlugin(plugin.id)
    store.showToast(`🎉 已安装「${plugin.name}」`)
  }
}

function openPlugin(plugin) {
  store.showToast(`「${plugin.name}」插件即将上线，敬请期待`)
}
</script>

<style scoped>
.marketplace-page {
  background: var(--bg-dark);
  min-height: 100%;
  padding-bottom: 80px;
  overflow-y: auto;
}

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
  opacity: 0.12;
}

.orb-1 {
  width: 280px;
  height: 280px;
  background: radial-gradient(circle, #9B89FF, transparent);
  top: -80px;
  left: -60px;
  animation: pulse 9s ease-in-out infinite;
}

.orb-2 {
  width: 220px;
  height: 220px;
  background: radial-gradient(circle, #43E97B, transparent);
  bottom: 20%;
  right: -60px;
  animation: pulse 11s ease-in-out infinite 3s;
}

/* 顶部栏 */
.mp-header {
  position: relative;
  z-index: 10;
  padding: 16px 20px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.mp-header-title {
  display: flex;
  align-items: center;
  gap: 12px;
}

.mp-header-icon {
  font-size: 30px;
  filter: drop-shadow(0 0 10px rgba(155,137,255,0.5));
}

.mp-title {
  font-size: 20px;
  font-weight: 800;
  color: white;
}

.mp-subtitle {
  font-size: 11px;
  color: var(--text-muted);
  margin-top: 1px;
}

.mp-installed-badge {
  padding: 6px 14px;
  background: rgba(108,99,255,0.15);
  border: 1px solid rgba(108,99,255,0.3);
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
  color: var(--primary-light);
  cursor: pointer;
  transition: var(--transition);
}

.mp-installed-badge:active {
  background: rgba(108,99,255,0.3);
}

/* 搜索栏 */
.search-bar {
  position: relative;
  z-index: 10;
  margin: 0 20px 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-lg);
}

.search-icon {
  font-size: 16px;
  opacity: 0.5;
}

.search-input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: white;
  font-size: 14px;
}

.search-input::placeholder {
  color: var(--text-muted);
}

.search-clear {
  background: none;
  border: none;
  color: var(--text-muted);
  font-size: 14px;
  cursor: pointer;
  padding: 0 4px;
}

/* 标签切换 */
.tab-switcher {
  position: relative;
  z-index: 10;
  display: flex;
  gap: 8px;
  padding: 0 20px 14px;
  overflow-x: auto;
  scrollbar-width: none;
}

.tab-switcher::-webkit-scrollbar {
  display: none;
}

.switcher-btn {
  flex-shrink: 0;
  padding: 6px 14px;
  border-radius: var(--radius-full);
  border: 1px solid rgba(255,255,255,0.1);
  background: rgba(255,255,255,0.04);
  color: var(--text-secondary);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  white-space: nowrap;
}

.switcher-btn.active {
  background: rgba(108,99,255,0.2);
  border-color: rgba(108,99,255,0.5);
  color: var(--primary-light);
}

/* 精选横幅 */
.featured-banner {
  position: relative;
  z-index: 10;
  margin: 0 20px 20px;
  padding: 16px;
  background: linear-gradient(135deg, rgba(108,99,255,0.2), rgba(155,137,255,0.1));
  border: 1px solid rgba(108,99,255,0.3);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  gap: 12px;
}

.featured-icon {
  font-size: 28px;
  flex-shrink: 0;
}

.featured-text {
  flex: 1;
  min-width: 0;
}

.featured-title {
  font-size: 14px;
  font-weight: 700;
  color: white;
}

.featured-desc {
  font-size: 11px;
  color: var(--text-secondary);
  margin-top: 3px;
}

.featured-btn {
  flex-shrink: 0;
  padding: 8px 16px;
  background: var(--primary);
  border: none;
  border-radius: var(--radius-full);
  color: white;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
}

.featured-btn:active {
  transform: scale(0.95);
  opacity: 0.85;
}

/* 插件列表区 */
.plugins-section, .installed-section {
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
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-count {
  background: rgba(255,255,255,0.08);
  border-radius: var(--radius-full);
  padding: 1px 8px;
  font-size: 11px;
  color: var(--text-secondary);
  letter-spacing: 0;
}

/* 空状态 */
.empty-state {
  padding: 40px 0;
  text-align: center;
}

.empty-icon {
  font-size: 40px;
  opacity: 0.4;
  margin-bottom: 12px;
}

.empty-text {
  color: var(--text-muted);
  font-size: 13px;
}

/* 插件卡片 */
.plugins-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.plugin-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: var(--radius-lg);
  transition: var(--transition);
}

.plugin-card.plugin-installed {
  border-color: rgba(108,99,255,0.25);
  background: rgba(108,99,255,0.06);
}

.plugin-card:active {
  background: rgba(255,255,255,0.07);
}

.plugin-icon-wrap {
  width: 50px;
  height: 50px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.plugin-icon {
  font-size: 26px;
}

.plugin-body {
  flex: 1;
  min-width: 0;
}

.plugin-top {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 3px;
}

.plugin-name {
  font-size: 15px;
  font-weight: 700;
  color: white;
}

.plugin-badges {
  display: flex;
  gap: 4px;
}

.badge {
  padding: 1px 6px;
  border-radius: var(--radius-full);
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.5px;
}

.badge-new {
  background: rgba(67,233,123,0.2);
  color: #43E97B;
}

.badge-pro {
  background: rgba(255,215,0,0.2);
  color: #FFD700;
}

.badge-installed {
  background: rgba(108,99,255,0.2);
  color: var(--primary-light);
}

.plugin-desc {
  font-size: 12px;
  color: var(--text-secondary);
  margin-bottom: 6px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.plugin-meta {
  display: flex;
  gap: 10px;
  font-size: 11px;
  color: var(--text-muted);
}

.plugin-category {
  padding: 1px 6px;
  background: rgba(255,255,255,0.07);
  border-radius: var(--radius-full);
}

/* 安装按钮 */
.install-btn {
  flex-shrink: 0;
  padding: 8px 16px;
  background: var(--primary);
  border: none;
  border-radius: var(--radius-full);
  color: white;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: var(--transition);
  min-width: 56px;
  text-align: center;
}

.install-btn:active {
  transform: scale(0.95);
  opacity: 0.85;
}

.install-btn.btn-installed {
  background: rgba(255,255,255,0.08);
  color: var(--text-secondary);
}

.install-btn.btn-pro {
  background: linear-gradient(135deg, #B8860B, #FFD700);
  color: #1a1a1a;
}

/* 已安装 chips */
.installed-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.installed-chip {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: rgba(108,99,255,0.12);
  border: 1px solid rgba(108,99,255,0.25);
  border-radius: var(--radius-full);
  font-size: 13px;
  color: var(--primary-light);
  cursor: pointer;
  transition: var(--transition);
}

.installed-chip:active {
  background: rgba(108,99,255,0.25);
  transform: scale(0.97);
}
</style>
