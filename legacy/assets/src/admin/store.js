import Vue from 'vue';
import Vuex from 'vuex'
import orders from './store/orders'
import balance from './store/balance'
import configuration from './store/configuration'
import log from './store/log'

Vue.use(Vuex)

const store = new Vuex.Store({
    modules: {
        orders,
        balance,
        configuration,
        log
    }
})

export default store