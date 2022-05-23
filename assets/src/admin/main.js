import Vue from 'vue'
import Axios from 'axios'
import App from './App.vue'
import router from './router'
import menuFix from './utils/admin-menu-fix'
import store from './store'
import VueSafeHTML from "vue-safe-html";

Vue.config.productionTip = false

Vue.use(VueSafeHTML);

Vue.prototype.$http = Axios

/* eslint-disable no-new */
const app = new Vue({
    el: '#vue-admin-app',
    store,
    router,
    render: h => h(App)    
});


// fix the admin menu for the slug "vue-app"
menuFix('vue-app');
