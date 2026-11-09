<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { state, t } from '../store'
import ProductRow from '../components/ProductRow.vue'
const route = useRoute()
const q = ref('')
const catId = computed(() => Number(route.params.cat || 0))
const title = computed(() => {
  if (!state.menu || !catId.value) return t('menu')
  const c = state.menu.categories.find(c => c.id === catId.value)
  return c ? c.name : t('menu')
})
const items = computed(() => {
  if (!state.menu) return []
  const s = q.value.trim().toLowerCase()
  return state.menu.products.filter(p => (!catId.value || p.cat === catId.value) && (!s || p.name.toLowerCase().includes(s)))
})
</script>

<template>
  <div class="menu-head">
    <h1>{{ title }}</h1>
    <input v-model="q" class="search" type="search" :placeholder="t('search')" />
  </div>
  <div v-if="!state.menu && !state.menuError" class="skeleton-list"><div v-for="i in 5" :key="i" class="skeleton"></div></div>
  <div v-else-if="state.menuError" class="empty"><p>{{ t('e_network') }}</p></div>
  <div v-else class="list grid"><ProductRow v-for="p in items" :key="p.id" :p="p" /></div>
  <p v-if="state.menu && !items.length" class="empty">{{ q ? t('nothing_found') : t('empty_menu') }}</p>
</template>
