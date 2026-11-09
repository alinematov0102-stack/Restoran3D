<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { t, money } from '../store'
import { api } from '../api'
const route = useRoute()
const order = ref(null), missing = ref(false)
let timer
async function load() {
  const r = await api('order', { params: { id: route.params.id } })
  if (r.ok) order.value = r.order; else if (!order.value) missing.value = true
}
function sLabel(s) { return t('s_' + (s === 'bekor qilingan' ? 'bekor' : s)) }
onMounted(() => { load(); timer = setInterval(load, 15000) })
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <div v-if="order" class="success">
    <div class="big-ok">✅</div>
    <h1>{{ t('order_accepted') }}</h1>
    <p class="code">{{ t('order_number') }}: <b>{{ order.code }}</b></p>
    <span class="status" :class="order.status === 'bekor qilingan' ? 'bekor' : order.status">{{ sLabel(order.status) }}</span>
    <div class="items">
      <div v-for="(it, i) in order.items" :key="i" class="item"><span>{{ it.name }} × {{ it.quantity }}</span><b>{{ money(it.price * it.quantity) }}</b></div>
      <div class="item total"><span>{{ t('total') }}</span><b>{{ money(order.total) }}</b></div>
    </div>
    <div class="actions"><router-link to="/orders" class="btn">{{ t('orders') }}</router-link><router-link to="/menu" class="btn outline">{{ t('menu') }}</router-link></div>
  </div>
  <div v-else-if="missing" class="empty big"><router-link to="/" class="btn">{{ t('home') }}</router-link></div>
  <div v-else class="skeleton-list"><div class="skeleton"></div></div>
</template>
