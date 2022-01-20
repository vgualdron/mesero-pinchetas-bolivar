import Vue from 'vue'
import Router from 'vue-router'

// Containers
import Full from '@/containers/Full'

// Views
import Home from '@/views/Home'
import Error from '@/views/Error'

import Gasto from '@/views/Gasto'
import Pedido from '@/views/Pedido'
import DetallePedido from '@/views/DetallePedido'
import OpenBox from '@/views/OpenBox'
Vue.use(Router)
export default new Router({
  mode: 'hash',
  linkActiveClass: 'open active',
  scrollBehavior: () => ({ y: 0 }),
  routes: [
    {
      path: '/',
      redirect: '/home',
      name: 'Home',
      component: Full,
      children: [
        {
          path: 'home',
          name: 'Home',
          component: Home
        },
        {
          path: 'gasto',
          name: 'Gastos',
          component: Gasto
        },
        {
          path: 'pedido',
          name: 'Pedido',
          component: Pedido
        },
        {
          path: 'detallepedido',
          name: 'Detalle Pedido',
          component: DetallePedido
        },
        {
          path: 'openbox',
          name: 'Open Box',
          component: OpenBox
        }
      ]
    },
    {
      path: '/error',
      name: 'Error',
      component: Error
    }
  ]
})
