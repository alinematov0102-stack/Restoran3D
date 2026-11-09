<script setup>
import { ref, onMounted } from 'vue'
import { t, errText } from '../store'
import { api } from '../api'
const tables = ref([]), tableId = ref(''), name = ref(''), phone = ref(''), date = ref(''), time = ref('')
const error = ref(''), ok = ref(false), busy = ref(false)
const today = new Date(); today.setMinutes(today.getMinutes() - today.getTimezoneOffset())
const minDate = today.toISOString().slice(0, 10)
onMounted(async () => { const r = await api('tables'); if (r.ok) tables.value = r.tables; name.value = localStorage.getItem('ff-name') || ''; phone.value = localStorage.getItem('ff-phone') || '' })
async function submit() {
  error.value = ''; ok.value = false; busy.value = true
  const r = await api('reserve', { method: 'POST', body: { table_id: tableId.value, name: name.value, phone: phone.value, date: date.value, time: time.value } })
  busy.value = false
  if (r.ok) ok.value = true; else error.value = errText(r.error || 'server_error')
}
</script>

<template>
  <h1 class="page-title">{{ t('reserve_title') }}</h1>
  <div v-if="ok" class="success"><div class="big-ok">✅</div><p>{{ t('reserved_ok') }}</p><router-link to="/" class="btn">{{ t('home') }}</router-link></div>
  <form v-else class="form" @submit.prevent="submit">
    <label>{{ t('table') }}
      <select v-model="tableId" required>
        <option value="" disabled>{{ t('choose_table') }}</option>
        <option v-for="tb in tables" :key="tb.id" :value="tb.id">{{ t('table') }} {{ tb.table_number }}<template v-if="tb.seats"> · {{ tb.seats }} {{ t('seats') }}</template></option>
      </select>
    </label>
    <label>{{ t('your_name') }}<input v-model="name" autocomplete="name" maxlength="120" required /></label>
    <label>{{ t('phone') }}<input v-model="phone" type="tel" inputmode="tel" autocomplete="tel" maxlength="40" required /></label>
    <div class="two">
      <label>{{ t('date') }}<input v-model="date" type="date" :min="minDate" required /></label>
      <label>{{ t('time') }}<input v-model="time" type="time" required /></label>
    </div>
    <p v-if="error" class="err">{{ error }}</p>
    <button class="btn block" :disabled="busy">{{ busy ? t('loading') : t('book') }}</button>
  </form>
</template>
