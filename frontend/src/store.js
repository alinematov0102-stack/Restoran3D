// store.js - butun ilova holati (kichik reaktiv saqlagich)
import { reactive } from 'vue'
import { api, apiConfig } from './api'
import { messages } from './i18n'

const LANGS = ['uz', 'ru', 'en']
function initLang() {
  const m = document.cookie.match(/(?:^|; )ff_lang=([^;]+)/)
  const c = m && m[1]
  const l = LANGS.includes(c) ? c : (localStorage.getItem('ff-lang') || 'uz')
  return LANGS.includes(l) ? l : 'uz'
}
function initTheme() {
  const s = localStorage.getItem('ff-theme')
  if (s === 'dark' || s === 'light') return s
  return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

export const state = reactive({
  lang: initLang(), theme: initTheme(),
  menu: null, menuError: false, cart: { items: [], total: 0, count: 0 },
  toast: '', toastShow: false, drawer: false, sheet: null
})
apiConfig.lang = state.lang
document.documentElement.dataset.theme = state.theme
document.documentElement.lang = state.lang

export function t(key) { return (messages[state.lang] && messages[state.lang][key]) || messages.uz[key] || key }
export function errText(code) { return t('e_' + code) }
export function money(n) {
  const v = new Intl.NumberFormat('ru-RU').format(Math.round(Number(n) || 0))
  return v + ' ' + t('sum')
}
export function setLang(l) {
  if (!LANGS.includes(l) || l === state.lang) return
  state.lang = l; apiConfig.lang = l
  localStorage.setItem('ff-lang', l)
  document.cookie = 'ff_lang=' + l + '; path=/; max-age=31536000; SameSite=Lax'   // eski sayt bilan umumiy
  document.documentElement.lang = l
  loadMenu(); loadCart()
}
export function toggleTheme() {
  state.theme = state.theme === 'dark' ? 'light' : 'dark'
  localStorage.setItem('ff-theme', state.theme)
  document.documentElement.dataset.theme = state.theme
}
export async function loadMenu() {
  state.menuError = false
  const r = await api('menu')
  if (r.ok) state.menu = r; else { state.menuError = true }
}
export async function loadCart() {
  const r = await api('cart')
  if (r.ok) state.cart = { items: r.items, total: r.total, count: r.count }
}
export async function cartOp(op, id, quiet) {
  const r = await api('cart', { method: 'POST', body: { op, id } })
  if (r.ok) {
    state.cart = { items: r.items, total: r.total, count: r.count }
    if (op === 'add' && !quiet) showToast(t('added'))
    if (navigator.vibrate && op === 'add') navigator.vibrate(12)
  } else showToast(errText(r.error || 'server_error'))
  return r
}
export function qtyInCart(id) {
  const it = state.cart.items.find(i => i.id === id)
  return it ? it.quantity : 0
}
let tt
export function showToast(msg) {
  state.toast = msg; state.toastShow = true
  clearTimeout(tt); tt = setTimeout(() => { state.toastShow = false }, 1700)
}
export function img(path) { return path ? (/^https?:/.test(path) ? path : '../' + path) : '' }
export function catIcon(name) {
  const n = String(name || '').toLowerCase()
  const map = [
    ['⭐', ['aksiya', 'акци', 'promo', 'sale']], ['🍕', ['pitsa', 'pizza', 'пицц']], ['🍔', ['burger', 'бургер']],
    ['🌯', ['lavash', 'shaurma', 'shawarma', 'шаурм', 'лаваш', 'roll', 'ролл', 'doner', 'донер']],
    ['🍟', ['zakus', 'закус', 'snack', 'снек', 'fri', 'kartoshka', 'картоф', 'fries']],
    ['🥤', ['ichimlik', 'napit', 'напит', 'drink', 'cola', 'кола', 'sok', 'сок', 'juice', 'limonad']],
    ['☕', ['kofe', 'coffee', 'кофе', 'choy', 'чай', 'tea']], ['🥗', ['salat', 'салат', 'salad']],
    ['🍰', ['desert', 'десерт', 'shirin', 'tort', 'торт', 'dessert', 'muzqaymoq', 'морожен']],
    ['🍲', ['sho', 'суп', 'soup', 'osh', 'plov', 'плов', 'lag']], ['🍱', ['set', 'combo', 'комбо', 'сет']],
    ['🍗', ['tovuq', 'chicken', 'кур', 'nugget', 'крыл', 'wings']], ['🥩', ['kabob', 'kebab', 'кебаб', 'steak', 'мясо']]
  ]
  for (const [icon, words] of map) for (const w of words) if (n.includes(w)) return icon
  return '🍽️'
}
