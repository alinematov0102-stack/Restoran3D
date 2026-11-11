<script setup>
import { computed, onMounted, watch, ref, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { state, t, setLang, toggleTheme, loadMenu, loadCart, catIcon } from './store'
import ProductSheet from './components/ProductSheet.vue'

const LOGO = '../img/logo-mark.svg'
const route = useRoute()
const cats = computed(() => (state.menu ? state.menu.categories : []))
const menuMode = computed(() => route.name === 'menu' || route.name === 'home')
const activeCat = computed(() => (route.name === 'menu' ? Number(route.params.cat || 0) : -1))
const hideFab = computed(() => ['cart', 'checkout'].includes(route.name))
const navTabs = computed(() => [
  { to: '/', icon: '🏠', label: t('home'), on: route.name === 'home' },
  { to: '/menu', icon: '🍽️', label: t('menu'), on: route.name === 'menu' },
  { to: '/reserve', icon: '📅', label: t('reserve_short'), on: route.name === 'reserve' },
  { to: '/orders', icon: '🧾', label: t('orders_short'), on: route.name === 'orders' || route.name === 'order' }
])
const tabbar = ref(null)
const more = ref({ left: false, right: false })
function updateMore() {
  const el = tabbar.value; if (!el) return
  more.value = { left: el.scrollLeft > 6, right: el.scrollWidth - el.clientWidth - el.scrollLeft > 6 }
}
async function focusActiveTab() {
  await nextTick(); const el = tabbar.value; if (!el) return
  const on = el.querySelector('a.on'); if (on && on.scrollIntoView) { try { on.scrollIntoView({ inline: 'center', block: 'nearest' }) } catch (e) {} }
  updateMore()
}
function close() { state.drawer = false }
watch(() => route.fullPath, () => { close(); focusActiveTab() })
watch(cats, focusActiveTab)
watch(() => state.drawer, v => { document.body.style.overflow = v ? 'hidden' : '' })
onMounted(() => { loadMenu(); loadCart(); window.addEventListener('resize', updateMore); focusActiveTab() })
</script>

<template>
  <header class="top">
    <button class="burger" :aria-label="t('menu')" @click="state.drawer = true"><span></span><span></span><span></span></button>
    <router-link to="/" class="logo"><img :src="LOGO" alt="" width="34" height="34" /><span>FastFood</span></router-link>
    <nav class="top-nav">
      <router-link to="/">{{ t('home') }}</router-link>
      <router-link to="/menu">{{ t('menu') }}</router-link>
      <router-link to="/reserve">{{ t('reserve') }}</router-link>
      <router-link to="/orders">{{ t('orders') }}</router-link>
      <router-link to="/cart" class="cart-link">🛒 {{ t('cart') }} <b v-if="state.cart.count">{{ state.cart.count }}</b></router-link>
      <span class="lang"><button v-for="l in ['uz','ru','en']" :key="l" :class="{ on: state.lang === l }" @click="setLang(l)">{{ l.toUpperCase() }}</button></span>
      <button class="theme-btn" :aria-label="t('theme')" @click="toggleTheme">{{ state.theme === 'dark' ? '☀️' : '🌙' }}</button>
    </nav>
    <span class="top-space"></span>
  </header>

  <main class="page"><router-view /></main>

  <!-- Yon menyu (telefon) -->
  <div class="backdrop" :class="{ show: state.drawer }" @click="close"></div>
  <aside class="drawer" :class="{ open: state.drawer }" :aria-hidden="!state.drawer">
    <div class="drawer-head"><img :src="LOGO" alt="" width="34" height="34" /><strong>FastFood</strong>
      <button class="x" :aria-label="t('close')" @click="close">×</button></div>
    <nav class="drawer-nav">
      <router-link to="/"><span>🏠</span>{{ t('home') }}</router-link>
      <router-link to="/menu"><span>🍽️</span>{{ t('menu') }}</router-link>
      <router-link to="/reserve"><span>📅</span>{{ t('reserve') }}</router-link>
      <router-link to="/cart"><span>🛒</span>{{ t('cart') }}<b class="count" v-if="state.cart.count">{{ state.cart.count }}</b></router-link>
      <router-link to="/orders"><span>🧾</span>{{ t('orders') }}</router-link>
    </nav>
    <div class="drawer-tools">
      <div class="label">{{ t('settings') }}</div>
      <div class="row">
        <button class="theme-btn" @click="toggleTheme">{{ state.theme === 'dark' ? '☀️' : '🌙' }}</button>
        <span class="lang"><button v-for="l in ['uz','ru','en']" :key="l" :class="{ on: state.lang === l }" @click="setLang(l)">{{ l.toUpperCase() }}</button></span>
      </div>
    </div>
  </aside>

  <!-- Pastki tablar -->
  <nav ref="tabbar" class="tabbar" :class="{ 'more-right': more.right, 'more-left': more.left }" @scroll.passive="updateMore">
    <template v-if="menuMode">
      <router-link to="/menu" :class="{ on: activeCat === 0 }"><i>🍽️</i><span>{{ t('all') }}</span></router-link>
      <router-link v-for="c in cats" :key="c.id" :to="'/menu/' + c.id" :class="{ on: activeCat === c.id }"><i>{{ catIcon(c.name) }}</i><span>{{ c.name }}</span></router-link>
    </template>
    <template v-else>
      <router-link v-for="n in navTabs" :key="n.to" :to="n.to" :class="{ on: n.on }"><i>{{ n.icon }}</i><span>{{ n.label }}</span></router-link>
    </template>
  </nav>

  <router-link v-if="!hideFab" to="/cart" class="fab" :aria-label="t('cart')">🛒<b v-if="state.cart.count">{{ state.cart.count }}</b></router-link>
  <div class="toast" :class="{ show: state.toastShow }" role="status">{{ state.toast }}</div>
  <ProductSheet />
</template>
