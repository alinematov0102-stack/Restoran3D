<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { t, money, errText, showToast } from '../store'
import { api } from '../api'
const orders = ref(null), cancelId = ref(0), reason = ref(''), err = ref('')
let timer
async function load() { const r = await api('orders'); if (r.ok) orders.value = r.orders }
function sLabel(s) { return t('s_' + (s === 'bekor qilingan' ? 'bekor' : s)) }
async function cancel(id) {
  err.value = ''
  const r = await api('cancel', { method: 'POST', body: { order_id: id, reason: reason.value } })
  if (r.ok) { cancelId.value = 0; reason.value = ''; await load() } else err.value = errText(r.error || 'server_error')
}
onMounted(() => { load(); timer = setInterval(load, 15000) })
onUnmounted(() => clearInterval(timer))
</script>

<template>
  <h1 class="page-title">{{ t('orders') }}</h1>
  <div v-if="orders === null" class="skeleton-list"><div v-for="i in 3" :key="i" class="skeleton"></div></div>
  <div v-else-if="!orders.length" class="empty big"><div class="empty-icon">🛍️</div><h2>{{ t('no_orders') }}</h2><router-link to="/menu" class="btn">{{ t('menu') }}</router-link></div>
  <div v-else class="orders">
    <article v-for="o in orders" :key="o.id" class="order">
      <div class="order-top"><b class="mono">{{ o.code }}</b><span class="status" :class="o.status === 'bekor qilingan' ? 'bekor' : o.status">{{ sLabel(o.status) }}</span></div>
      <div class="order-meta"><span>{{ o.created_at }}</span><span>{{ o.type === 'table' ? '🍽️ ' + t('in_restaurant') : '🛵 ' + t('delivery') }}</span><b>{{ money(o.total) }}</b></div>
      <ul class="order-items"><li v-for="(it, i) in o.items" :key="i">{{ it.name }} × {{ it.quantity }}</li></ul>
      <template v-if="o.can_cancel">
        <button v-if="cancelId !== o.id" class="ghost danger" @click="cancelId = o.id; err = ''">{{ t('cancel_order') }}</button>
        <div v-else class="cancel-box">
          <textarea v-model="reason" rows="2" :placeholder="t('cancel_reason')"></textarea>
          <p v-if="err" class="err">{{ err }}</p>
          <div class="actions"><button class="btn danger" @click="cancel(o.id)">{{ t('send') }}</button><button class="btn outline" @click="cancelId = 0">{{ t('back') }}</button></div>
        </div>
      </template>
    </article>
  </div>
</template>
