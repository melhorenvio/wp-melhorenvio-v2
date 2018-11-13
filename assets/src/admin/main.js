import Vue from 'vue'
import Vuex from 'vuex'
import Axios from 'axios'
import App from './App.vue'
import router from './router'
import menuFix from './utils/admin-menu-fix'
import store from './store'

Vue.config.productionTip = false
Vue.use(Vuex)

Vue.prototype.$http = Axios

/* eslint-disable no-new */
new Vue({
    el: '#vue-admin-app',
    store,
    router,
    render: h => h(App)
});


// fix the admin menu for the slug "vue-app"
menuFix('vue-app');
