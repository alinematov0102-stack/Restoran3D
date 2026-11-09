import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
// Natija ../shop papkasiga yig'iladi (PHP sayt shu papkani statik fayl sifatida beradi)
export default defineConfig({
  plugins: [vue()],
  base: './',
  build: { outDir: '../shop', emptyOutDir: true, target: 'es2019' },
  server: { proxy: { '/api': 'http://127.0.0.1:8000' } }
})
