import Vue from 'vue'
import Vuex from 'vuex'
import orders from './store/orders'
import balance from './store/balance'
import configuration from './store/configuration'

Vue.use(Vuex)

const store = new Vuex.Store({
    modules: {
        orders: orders,
        balance: balance,
        configuration: configuration
    }
})

export default store