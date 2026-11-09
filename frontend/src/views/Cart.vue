<script setup>
import { state, t, money, img, cartOp } from '../store'
</script>

<template>
  <h1 class="page-title">{{ t('cart') }}</h1>
  <div v-if="!state.cart.items.length" class="empty big">
    <div class="empty-icon">🛒</div><h2>{{ t('cart_empty') }}</h2><p>{{ t('cart_empty_hint') }}</p>
    <router-link to="/menu" class="btn">{{ t('menu') }}</router-link>
  </div>
  <template v-else>
    <div class="cart-list">
      <article v-for="it in state.cart.items" :key="it.id" class="cart-row">
        <img :src="img(it.image)" :alt="it.name" loading="lazy" />
        <div class="cart-info"><h3>{{ it.name }}</h3><b class="price">{{ money(it.price) }}</b></div>
        <div class="stepper"><button @click="cartOp('dec', it.id, true)">−</button><span>{{ it.quantity }}</span><button @click="cartOp('inc', it.id, true)">+</button></div>
        <button class="trash" :aria-label="t('remove')" @click="cartOp('remove', it.id, true)">🗑️</button>
      </article>
    </div>
    <div class="summary"><span>{{ t('total') }}</span><b class="price big">{{ money(state.cart.total) }}</b></div>
    <router-link to="/checkout" class="btn block">{{ t('checkout') }}</router-link>
  </template>
</template>
