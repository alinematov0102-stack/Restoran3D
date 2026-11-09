import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import Home from './views/Home.vue'
import Menu from './views/Menu.vue'
import Cart from './views/Cart.vue'
import Checkout from './views/Checkout.vue'
import Order from './views/Order.vue'
import Orders from './views/Orders.vue'
import Reserve from './views/Reserve.vue'
import './style.css'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'home', component: Home },
    { path: '/menu/:cat?', name: 'menu', component: Menu },
    { path: '/cart', name: 'cart', component: Cart },
    { path: '/checkout', name: 'checkout', component: Checkout },
    { path: '/order/:id', name: 'order', component: Order },
    { path: '/orders', name: 'orders', component: Orders },
    { path: '/reserve', name: 'reserve', component: Reserve },
    { path: '/:pathMatch(.*)*', redirect: '/' }
  ],
  scrollBehavior: () => ({ top: 0 })
})
const ic = document.createElement('link'); ic.rel = 'icon'; ic.type = 'image/svg+xml'; ic.href = '../img/favicon.svg'; document.head.appendChild(ic)
createApp(App).use(router).mount('#app')
