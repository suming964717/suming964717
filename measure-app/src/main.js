import { createApp, defineComponent, h, computed } from 'vue'
import store from './store/index.js'
import Home from './pages/Home.vue'
import Ruler from './pages/Ruler.vue'
import Level from './pages/Level.vue'
import Decibel from './pages/Decibel.vue'
import User from './pages/User.vue'
import Marketplace from './pages/Marketplace.vue'
import TabBar from './components/TabBar.vue'
import VipModal from './components/VipModal.vue'
import MemberModal from './components/MemberModal.vue'
import './assets/style.css'

// 根组件
const App = defineComponent({
  name: 'App',
  setup() {
    const currentPage = computed(() => store.state.currentPage)
    const showTabBar = computed(() =>
      ['home', 'ruler', 'level', 'decibel', 'user', 'marketplace'].includes(currentPage.value)
    )

    return () => h('div', { style: 'width:100%;height:100%;position:relative;overflow:hidden;' }, [
      // 页面渲染
      currentPage.value === 'home' ? h(Home) : null,
      currentPage.value === 'ruler' ? h(Ruler) : null,
      currentPage.value === 'level' ? h(Level) : null,
      currentPage.value === 'decibel' ? h(Decibel) : null,
      currentPage.value === 'user' ? h(User) : null,
      currentPage.value === 'marketplace' ? h(Marketplace) : null,

      // 底部导航栏
      showTabBar.value ? h(TabBar) : null,

      // 全局Toast
      store.state.toast ? h('div', { class: 'toast' }, store.state.toast) : null,

      // 全局VIP弹窗（来自store）
      store.state.showVipModal && currentPage.value !== 'user'
        ? h(VipModal, { onClose: () => { store.state.showVipModal = false } })
        : null,

      // 全局会员弹窗
      store.state.showMemberModal && currentPage.value !== 'user'
        ? h(MemberModal, { onClose: () => { store.state.showMemberModal = false } })
        : null,
    ])
  }
})

createApp(App).mount('#app')
