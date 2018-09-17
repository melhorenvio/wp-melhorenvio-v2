import Vue from 'vue'
import Vuex from 'vuex'
import orders from './store/orders'

Vue.use(Vuex)

const store = new Vuex.Store({
    modules: {
        orders: orders,
    }
})

export default store