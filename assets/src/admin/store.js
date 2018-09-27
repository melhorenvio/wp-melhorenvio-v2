import Vue from 'vue'
import Vuex from 'vuex'
import orders from './store/orders'
import balance from './store/balance'

Vue.use(Vuex)

const store = new Vuex.Store({
    modules: {
        orders: orders,
        balance: balance
    }
})

export default store