<script setup>
import { state, t, money, thumb, onImgError, cartOp, qtyInCart } from '../store'
const props = defineProps({ p: Object })
function open() { state.sheet = props.p }
</script>

<template>
  <article class="row">
    <button class="row-img" @click="open" :aria-label="p.name">
      <img :src="thumb(p.image, 240)" :alt="p.name" loading="lazy" decoding="async" @error="onImgError($event, p.image)" />
      <span v-if="p.model" class="chip3d">3D</span>
    </button>
    <div class="row-info">
      <h3 @click="open">{{ p.name }}</h3>
      <p v-if="p.desc">{{ p.desc }}</p>
      <div class="row-bottom">
        <b class="price">{{ money(p.price) }}</b>
        <div v-if="qtyInCart(p.id)" class="stepper">
          <button @click="cartOp('dec', p.id, true)" aria-label="-">−</button>
          <span>{{ qtyInCart(p.id) }}</span>
          <button @click="cartOp('inc', p.id, true)" aria-label="+">+</button>
        </div>
        <button v-else class="add" @click="cartOp('add', p.id)">＋ <span>{{ t('add') }}</span></button>
      </div>
    </div>
  </article>
</template>
