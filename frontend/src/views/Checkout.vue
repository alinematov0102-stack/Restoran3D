<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { state, t, money, errText, loadCart } from '../store'
import { api } from '../api'
const router = useRouter()
const type = ref('yetkazib berish')
const name = ref(''), phone = ref(''), address = ref(''), table = ref('')
const tables = ref([]), error = ref(''), busy = ref(false)
const freeTables = computed(() => tables.value)
onMounted(async () => {
  await loadCart()
  if (!state.cart.items.length) router.replace('/cart')
  const r = await api('tables'); if (r.ok) tables.value = r.tables
  name.value = localStorage.getItem('ff-name') || ''; phone.value = localStorage.getItem('ff-phone') || ''; address.value = localStorage.getItem('ff-address') || ''
})
async function submit() {
  error.value = ''; busy.value = true
  const r = await api('checkout', { method: 'POST', body: { name: name.value, phone: phone.value, order_type: type.value, address: address.value, table_number: table.value } })
  busy.value = false
  if (r.ok) {
    localStorage.setItem('ff-name', name.value); localStorage.setItem('ff-phone', phone.value); localStorage.setItem('ff-address', address.value)
    await loadCart(); router.replace('/order/' + r.order_id)
  } else error.value = errText(r.error || 'server_error')
}
</script>

<template>
  <h1 class="page-title">{{ t('order_details') }}</h1>
  <form class="form" @submit.prevent="submit">
    <div class="seg">
      <button type="button" :class="{ on: type === 'yetkazib berish' }" @click="type = 'yetkazib berish'">🛵 {{ t('delivery') }}</button>
      <button type="button" :class="{ on: type === 'stol' }" @click="type = 'stol'">🍽️ {{ t('in_restaurant') }}</button>
    </div>
    <label>{{ t('your_name') }}<input v-model="name" autocomplete="name" maxlength="120" required /></label>
    <label v-if="type === 'yetkazib berish'">{{ t('phone') }}<input v-model="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+998 90 123 45 67" maxlength="40" required /></label>
    <label v-if="type === 'yetkazib berish'">{{ t('address') }}<textarea v-model="address" rows="3" maxlength="255" required></textarea></label>
    <label v-if="type === 'stol'">{{ t('table') }}
      <select v-model="table" required>
        <option value="" disabled>{{ t('choose_table') }}</option>
        <option v-for="tb in freeTables" :key="tb.id" :value="tb.table_number" :disabled="tb.status === 'band'">{{ t('table') }} {{ tb.table_number }}<template v-if="tb.seats"> · {{ tb.seats }} {{ t('seats') }}</template><template v-if="tb.status === 'band'"> · {{ t('busy') }}</template></option>
      </select>
    </label>
    <div class="summary"><span>{{ t('total') }}</span><b class="price big">{{ money(state.cart.total) }}</b></div>
    <p v-if="error" class="err">{{ error }}</p>
    <button class="btn block" :disabled="busy">{{ busy ? t('loading') : t('place_order') }}</button>
  </form>
</template>
