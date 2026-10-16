import Vue from 'vue'
import Router from 'vue-router'
import Home from 'admin/components/Home.vue'
import Pedidos from 'admin/components/Pedidos.vue'
import Configuracoes from 'admin/components/Configuracoes.vue'
import Token from 'admin/components/Token.vue'
import Log from 'admin/components/Log.vue'

Vue.use(Router)

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Home',
            component: Home
        },
        {
            path: '/pedidos',
            name: 'Pedidos',
            component: Pedidos
        },
        {
            path: '/configuracoes',
            name: 'Configuracoes',
            component: Configuracoes
        },
        {
            path: '/token',
            name: 'Token',
            component: Token
        },
        {
            path: '/log/:id',
            name: 'Log',
            component: Log
        }
    ]
})
