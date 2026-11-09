<script setup>
import { computed } from 'vue'
import { state, t, img, catIcon } from '../store'
import ProductRow from '../components/ProductRow.vue'
const featured = computed(() => (state.menu ? state.menu.products.slice(0, 6) : []))
</script>

<template>
  <section class="hero" :style="state.menu && state.menu.hero ? { backgroundImage: 'linear-gradient(120deg,rgba(20,12,10,.82),rgba(20,12,10,.45)),url(' + img(state.menu.hero) + ')' } : {}">
    <h1>{{ t('welcome') }}</h1>
    <p>{{ t('tagline') }}</p>
    <div class="hero-actions">
      <router-link to="/menu" class="btn gold">{{ t('see_menu') }}</router-link>
      <router-link to="/reserve" class="btn outline">{{ t('book_table') }}</router-link>
    </div>
  </section>

  <div v-if="!state.menu && !state.menuError" class="skeleton-list"><div v-for="i in 4" :key="i" class="skeleton"></div></div>
  <div v-else-if="state.menuError" class="empty"><p>{{ t('e_network') }}</p></div>
  <template v-else>
    <h2 class="sec">{{ t('categories') }}</h2>
    <div class="cat-grid">
      <router-link v-for="c in state.menu.categories" :key="c.id" :to="'/menu/' + c.id" class="cat-card"><i>{{ catIcon(c.name) }}</i><span>{{ c.name }}</span></router-link>
    </div>
    <h2 class="sec">{{ t('popular') }}</h2>
    <div class="list"><ProductRow v-for="p in featured" :key="p.id" :p="p" /></div>
    <p v-if="!featured.length" class="empty">{{ t('empty_menu') }}</p>
  </template>
</template>
