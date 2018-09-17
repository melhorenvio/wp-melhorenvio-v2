import Vue from 'vue'
import Axios from 'axios'
import App from './App.vue'
import router from './router'
import menuFix from './utils/admin-menu-fix'

Vue.config.productionTip = false

Vue.prototype.$http = Axios

/* eslint-disable no-new */
new Vue({
    el: '#vue-admin-app',
    router,
    render: h => h(App)
});


// fix the admin menu for the slug "vue-app"
menuFix('vue-app');
