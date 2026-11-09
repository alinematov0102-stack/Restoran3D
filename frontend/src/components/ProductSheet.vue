<script setup>
import { ref, watch, nextTick } from 'vue'
import { state, t, money, img, cartOp, qtyInCart } from '../store'
const show3d = ref(false)
let mvLoaded = false
function loadViewer() {
  if (mvLoaded || customElements.get('model-viewer')) { mvLoaded = true; return }
  mvLoaded = true
  const s = document.createElement('script'); s.type = 'module'; s.src = '../js/model-viewer.min.js'; document.head.appendChild(s)
}
function close() { state.sheet = null; show3d.value = false }
watch(() => state.sheet, v => { document.body.style.overflow = v ? 'hidden' : ''; show3d.value = false })
async function open3d() { loadViewer(); show3d.value = true; await nextTick() }
</script>

<template>
  <div v-if="state.sheet" class="sheet-wrap" @click.self="close">
    <div class="sheet" role="dialog" aria-modal="true">
      <button class="x sheet-x" :aria-label="t('close')" @click="close">×</button>
      <div class="sheet-media">
        <model-viewer v-if="show3d && state.sheet.model" :src="img(state.sheet.model)" camera-controls auto-rotate shadow-intensity="1" style="width:100%;height:100%"></model-viewer>
        <img v-else :src="img(state.sheet.image)" :alt="state.sheet.name" />
      </div>
      <div class="sheet-body">
        <h2>{{ state.sheet.name }}</h2>
        <p v-if="state.sheet.desc">{{ state.sheet.desc }}</p>
        <button v-if="state.sheet.model && !show3d" class="ghost" @click="open3d">🧊 {{ t('3d') }}</button>
        <button v-else-if="show3d" class="ghost" @click="show3d = false">🖼️</button>
        <div class="sheet-foot">
          <b class="price big">{{ money(state.sheet.price) }}</b>
          <div v-if="qtyInCart(state.sheet.id)" class="stepper">
            <button @click="cartOp('dec', state.sheet.id, true)">−</button><span>{{ qtyInCart(state.sheet.id) }}</span><button @click="cartOp('inc', state.sheet.id, true)">+</button>
          </div>
          <button v-else class="btn" @click="cartOp('add', state.sheet.id)">＋ {{ t('add') }}</button>
        </div>
      </div>
    </div>
  </div>
</template>
