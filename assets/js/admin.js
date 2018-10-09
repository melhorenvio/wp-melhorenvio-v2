pluginWebpack([0],[
/* 0 */,
/* 1 */,
/* 2 */,
/* 3 */,
/* 4 */,
/* 5 */,
/* 6 */,
/* 7 */,
/* 8 */,
/* 9 */,
/* 10 */,
/* 11 */,
/* 12 */,
/* 13 */,
/* 14 */,
/* 15 */,
/* 16 */,
/* 17 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
//
//
//
//
//
//

/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'App'
});

/***/ }),
/* 18 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
//
//
//
//
//
//

/* harmony default export */ __webpack_exports__["a"] = ({

    name: 'Home',

    data() {
        return {
            msg: 'Welcome to Your Vue.js Admin App'
        };
    }
});

/***/ }),
/* 19 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vuex__ = __webpack_require__(5);
var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Pedidos',
    data: () => {
        return {
            status: 'all',
            wpstatus: 'all'
        };
    },
    computed: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapGetters"])('orders', {
        orders: 'getOrders',
        show_loader: 'toggleLoader',
        msg_modal: 'setMsgModal',
        show_modal: 'showModal'
    }), Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapGetters"])('balance', ['getBalance'])),
    methods: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapActions"])('orders', ['retrieveMany', 'loadMore', 'addCart', 'removeCart', 'cancelCart', 'payTicket', 'createTicket', 'printTicket', 'closeModal']), Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapActions"])('balance', ['setBalance']), {
        updateInvoice(id, number, key) {
            this.$http.post(`${ajaxurl}?action=insert_invoice_order&id=${id}&number=${number}&key=${key}`).then(response => {}).catch(error => {});
        },
        close() {
            this.closeModal();
        },
        buttonCartShow(...args) {
            const [choose_method, non_commercial, number, key, status] = args;

            if (status == 'paid') {
                return false;
            }

            if (status == 'printed') {
                return false;
            }

            if (status == 'generated') {
                return false;
            }

            if (status == 'pending') {
                return false;
            }

            if (choose_method == 1 || choose_method == 2) {
                return true;
            }

            if ((choose_method == 3 || choose_method == 4) && non_commercial) {
                return true;
            }

            if ((choose_method == 3 || choose_method == 4) && !non_commercial && number != null && number != '' && key != null && key != '') {
                return true;
            }

            if (choose_method > 3 && number != null && number != '' && key != null && key != '') {
                return true;
            }

            return false;
        }
    }),
    watch: {
        status() {
            this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
        },
        wpstatus() {
            this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
        }
    },
    mounted() {
        if (Object.keys(this.orders).length === 0) {
            this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
        }
        this.setBalance();
    }
});

/***/ }),
/* 20 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vuex__ = __webpack_require__(5);
var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Configuracoes',
    data() {
        return {
            address: null,
            store: null,
            agency: null,
            show_loader: true
        };
    },
    computed: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapGetters"])('configuration', {
        addresses: 'getAddress',
        stores: 'getStores',
        agencies: 'getAgencies'
    })),
    methods: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapActions"])('configuration', ['getAddresses', 'setSelectedAddress', 'getStores', 'setSelectedStore', 'getAgencies', 'setSelectedAgency']), {
        updateConfig() {
            this.setSelectedAddress(this.address);
            this.setSelectedStore(this.store);
            this.setSelectedAgency(this.agency);
            alert('Dados atualizados');
        },
        showAgencies(data) {
            this.agency = '';

            this.getAgencies(data);
        }
    }),
    watch: {
        addresses() {
            if (this.addresses.length > 0) {
                this.addresses.filter(item => {
                    if (item.selected) {
                        this.address = item.id;
                    }
                });
            }
        },
        stores() {
            if (this.stores.length > 0) {
                this.stores.filter(item => {
                    if (item.selected) {
                        this.store = item.id;
                    }
                });
            }
        },
        agencies() {

            this.show_loader = false;

            if (this.agencies.length > 0) {
                this.agencies.filter(item => {
                    if (item.selected) {
                        this.agency = item.id;
                    }
                });
            }
        }
    },
    mounted() {
        this.getAddresses();
        this.getStores();
        this.getAgencies();
    }
});

/***/ }),
/* 21 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_axios__ = __webpack_require__(4);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_axios___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_axios__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//


/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Token',
    data() {
        return {
            token: '',
            show_loader: true
        };
    },
    methods: {
        getToken() {
            this.$http.get(`${ajaxurl}?action=get_token`).then(response => {
                this.token = response.data.token;
                this.show_loader = false;
            });
        },
        saveToken() {
            let bodyFormData = new FormData();
            bodyFormData.set('token', this.token);
            let data = { token: this.token };
            if (this.token && this.token.length > 0) {
                __WEBPACK_IMPORTED_MODULE_0_axios___default()({
                    url: `${ajaxurl}?action=save_token`,
                    data: bodyFormData,
                    method: "POST"
                }).then(response => {
                    this.$router.push('Configuracoes');
                }).catch(err => console.log(err));
            }
        }
    },
    mounted() {
        this.getToken();
    }
});

/***/ }),
/* 22 */,
/* 23 */,
/* 24 */,
/* 25 */,
/* 26 */,
/* 27 */,
/* 28 */,
/* 29 */,
/* 30 */,
/* 31 */,
/* 32 */,
/* 33 */,
/* 34 */,
/* 35 */,
/* 36 */,
/* 37 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _vue = __webpack_require__(3);

var _vue2 = _interopRequireDefault(_vue);

var _vuex = __webpack_require__(5);

var _vuex2 = _interopRequireDefault(_vuex);

var _axios = __webpack_require__(4);

var _axios2 = _interopRequireDefault(_axios);

var _App = __webpack_require__(56);

var _App2 = _interopRequireDefault(_App);

var _router = __webpack_require__(59);

var _router2 = _interopRequireDefault(_router);

var _adminMenuFix = __webpack_require__(72);

var _adminMenuFix2 = _interopRequireDefault(_adminMenuFix);

var _store = __webpack_require__(73);

var _store2 = _interopRequireDefault(_store);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_vue2.default.config.productionTip = false;
_vue2.default.use(_vuex2.default);

_vue2.default.prototype.$http = _axios2.default;

/* eslint-disable no-new */
new _vue2.default({
    el: '#vue-admin-app',
    store: _store2.default,
    router: _router2.default,
    render: function render(h) {
        return h(_App2.default);
    }
});

// fix the admin menu for the slug "vue-app"
(0, _adminMenuFix2.default)('vue-app');

/***/ }),
/* 38 */,
/* 39 */,
/* 40 */,
/* 41 */,
/* 42 */,
/* 43 */,
/* 44 */,
/* 45 */,
/* 46 */,
/* 47 */,
/* 48 */,
/* 49 */,
/* 50 */,
/* 51 */,
/* 52 */,
/* 53 */,
/* 54 */,
/* 55 */,
/* 56 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_App_vue__ = __webpack_require__(17);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_6bc4b6d8_hasScoped_false_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_App_vue__ = __webpack_require__(58);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(57)
}
var normalizeComponent = __webpack_require__(1)
/* script */


/* template */

/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = injectStyle
/* scopeId */
var __vue_scopeId__ = null
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_App_vue__["a" /* default */],
  __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_6bc4b6d8_hasScoped_false_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_App_vue__["a" /* default */],
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "assets/src/admin/App.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-6bc4b6d8", Component.options)
  } else {
    hotAPI.reload("data-v-6bc4b6d8", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

/* harmony default export */ __webpack_exports__["default"] = (Component.exports);


/***/ }),
/* 57 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 58 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { attrs: { id: "vue-backend-app" } }, [_c("router-view")], 1)
}
var staticRenderFns = []
render._withStripped = true
var esExports = { render: render, staticRenderFns: staticRenderFns }
/* harmony default export */ __webpack_exports__["a"] = (esExports);
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-6bc4b6d8", esExports)
  }
}

/***/ }),
/* 59 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _vue = __webpack_require__(3);

var _vue2 = _interopRequireDefault(_vue);

var _vueRouter = __webpack_require__(7);

var _vueRouter2 = _interopRequireDefault(_vueRouter);

var _Home = __webpack_require__(60);

var _Home2 = _interopRequireDefault(_Home);

var _Pedidos = __webpack_require__(63);

var _Pedidos2 = _interopRequireDefault(_Pedidos);

var _Configuracoes = __webpack_require__(66);

var _Configuracoes2 = _interopRequireDefault(_Configuracoes);

var _Token = __webpack_require__(69);

var _Token2 = _interopRequireDefault(_Token);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_vue2.default.use(_vueRouter2.default);

exports.default = new _vueRouter2.default({
    routes: [{
        path: '/',
        name: 'Home',
        component: _Home2.default
    }, {
        path: '/pedidos',
        name: 'Pedidos',
        component: _Pedidos2.default
    }, {
        path: '/configuracoes',
        name: 'Configuracoes',
        component: _Configuracoes2.default
    }, {
        path: '/token',
        name: 'Token',
        component: _Token2.default
    }]
});

/***/ }),
/* 60 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Home_vue__ = __webpack_require__(18);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_0ce03f2f_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Home_vue__ = __webpack_require__(62);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(61)
}
var normalizeComponent = __webpack_require__(1)
/* script */


/* template */

/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = injectStyle
/* scopeId */
var __vue_scopeId__ = "data-v-0ce03f2f"
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Home_vue__["a" /* default */],
  __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_0ce03f2f_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Home_vue__["a" /* default */],
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "assets/src/admin/components/Home.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-0ce03f2f", Component.options)
  } else {
    hotAPI.reload("data-v-0ce03f2f", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

/* harmony default export */ __webpack_exports__["default"] = (Component.exports);


/***/ }),
/* 61 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 62 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "home" }, [
    _c("span", [_vm._v(_vm._s(_vm.msg))])
  ])
}
var staticRenderFns = []
render._withStripped = true
var esExports = { render: render, staticRenderFns: staticRenderFns }
/* harmony default export */ __webpack_exports__["a"] = (esExports);
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-0ce03f2f", esExports)
  }
}

/***/ }),
/* 63 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Pedidos_vue__ = __webpack_require__(19);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_05a7e32e_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Pedidos_vue__ = __webpack_require__(65);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(64)
}
var normalizeComponent = __webpack_require__(1)
/* script */


/* template */

/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = injectStyle
/* scopeId */
var __vue_scopeId__ = "data-v-05a7e32e"
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Pedidos_vue__["a" /* default */],
  __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_05a7e32e_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Pedidos_vue__["a" /* default */],
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "assets/src/admin/components/Pedidos.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-05a7e32e", Component.options)
  } else {
    hotAPI.reload("data-v-05a7e32e", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

/* harmony default export */ __webpack_exports__["default"] = (Component.exports);


/***/ }),
/* 64 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 65 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "app-pedidos" },
    [
      [_vm._m(0)],
      _vm._v(" "),
      _c("table", { staticClass: "table-box", attrs: { border: "0" } }, [
        _c("tr", [
          _c("td", [
            _c("h1", [
              _vm._v("Saldo: "),
              _c("strong", [_vm._v(_vm._s(_vm.getBalance))])
            ])
          ])
        ]),
        _vm._v(" "),
        _c("tr", [
          _c("td", { attrs: { width: "50%" } }, [
            _c("h3", [_vm._v("Etiquetas")]),
            _vm._v(" "),
            _c(
              "select",
              {
                directives: [
                  {
                    name: "model",
                    rawName: "v-model",
                    value: _vm.status,
                    expression: "status"
                  }
                ],
                on: {
                  change: function($event) {
                    var $$selectedVal = Array.prototype.filter
                      .call($event.target.options, function(o) {
                        return o.selected
                      })
                      .map(function(o) {
                        var val = "_value" in o ? o._value : o.value
                        return val
                      })
                    _vm.status = $event.target.multiple
                      ? $$selectedVal
                      : $$selectedVal[0]
                  }
                }
              },
              [
                _c("option", { attrs: { value: "all" } }, [_vm._v("Todas")]),
                _vm._v(" "),
                _c("option", { attrs: { value: "printed" } }, [
                  _vm._v("Impressas")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "paid" } }, [_vm._v("Pagas")]),
                _vm._v(" "),
                _c("option", { attrs: { value: "pending" } }, [
                  _vm._v("Aguardando pagamento")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "generated" } }, [
                  _vm._v("Geradas")
                ])
              ]
            )
          ]),
          _vm._v(" "),
          _c("td", { attrs: { width: "50%" } }, [
            _c("h3", [_vm._v("Pedidos")]),
            _vm._v(" "),
            _c(
              "select",
              {
                directives: [
                  {
                    name: "model",
                    rawName: "v-model",
                    value: _vm.wpstatus,
                    expression: "wpstatus"
                  }
                ],
                on: {
                  change: function($event) {
                    var $$selectedVal = Array.prototype.filter
                      .call($event.target.options, function(o) {
                        return o.selected
                      })
                      .map(function(o) {
                        var val = "_value" in o ? o._value : o.value
                        return val
                      })
                    _vm.wpstatus = $event.target.multiple
                      ? $$selectedVal
                      : $$selectedVal[0]
                  }
                }
              },
              [
                _c("option", { attrs: { value: "all" } }, [_vm._v("Todos")]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-pending" } }, [
                  _vm._v("Pendentes")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-processing" } }, [
                  _vm._v("Processando")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-on-hold" } }, [
                  _vm._v("Em andamento")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-completed" } }, [
                  _vm._v("Completos")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-cancelled" } }, [
                  _vm._v("Cancelados")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-refunded" } }, [
                  _vm._v("Recusados")
                ]),
                _vm._v(" "),
                _c("option", { attrs: { value: "wc-failed" } }, [
                  _vm._v("Com erro")
                ])
              ]
            )
          ])
        ])
      ]),
      _vm._v(" "),
      _vm.orders.length > 0
        ? _c(
            "div",
            {
              staticClass: "table-box",
              class: { "-inative": !_vm.orders.length }
            },
            [
              _c("div", { staticClass: "table -woocommerce" }, [
                _vm._m(1),
                _vm._v(" "),
                _c(
                  "ul",
                  { staticClass: "body" },
                  _vm._l(_vm.orders, function(item, index) {
                    return _c("li", { key: index }, [
                      _c("ul", { staticClass: "body-list" }, [
                        _c("li", [
                          _c("span", [
                            _c(
                              "a",
                              {
                                attrs: {
                                  target: "_blank",
                                  href:
                                    "/wp-admin/post.php?post=" +
                                    item.id +
                                    "&action=edit"
                                }
                              },
                              [_c("strong", [_vm._v(_vm._s(item.id))])]
                            )
                          ])
                        ]),
                        _vm._v(" "),
                        _c("li", [_c("span", [_vm._v(_vm._s(item.total))])]),
                        _vm._v(" "),
                        _c("li", [
                          _c("span", [
                            _vm._v(
                              "\n                                " +
                                _vm._s(item.status) +
                                "\n                                "
                            ),
                            _c("strong", [
                              _vm._v(
                                _vm._s(item.to.first_name) +
                                  " " +
                                  _vm._s(item.to.last_name)
                              )
                            ]),
                            _vm._v(" "),
                            _c("br"),
                            _vm._v(
                              "\n                                " +
                                _vm._s(item.to.email) +
                                " "
                            ),
                            _c("br"),
                            _vm._v(
                              "\n                                " +
                                _vm._s(item.to.phone) +
                                " "
                            ),
                            _c("br"),
                            _vm._v(
                              "\n                                " +
                                _vm._s(item.to.address_1) +
                                " " +
                                _vm._s(item.to.address_2) +
                                " "
                            ),
                            _c("br"),
                            _vm._v(
                              "\n                                " +
                                _vm._s(item.to.city) +
                                " / " +
                                _vm._s(item.to.state) +
                                " - " +
                                _vm._s(item.to.postcode) +
                                " "
                            ),
                            _c("br")
                          ])
                        ]),
                        _vm._v(" "),
                        _c(
                          "li",
                          [
                            !item.order_id
                              ? [
                                  _c("div", { staticClass: "me-form" }, [
                                    _c("div", { staticClass: "formBox" }, [
                                      _c("label", [_vm._v("Métodos de envio")]),
                                      _vm._v(" "),
                                      _c(
                                        "fieldset",
                                        { staticClass: "selectLine" },
                                        [
                                          _c(
                                            "div",
                                            { staticClass: "inputBox" },
                                            [
                                              !(
                                                item.status == "paid" ||
                                                item.status == "printed" ||
                                                item.status == "generated"
                                              )
                                                ? _c(
                                                    "select",
                                                    {
                                                      directives: [
                                                        {
                                                          name: "model",
                                                          rawName: "v-model",
                                                          value:
                                                            item.cotation
                                                              .choose_method,
                                                          expression:
                                                            "item.cotation.choose_method"
                                                        }
                                                      ],
                                                      on: {
                                                        change: function(
                                                          $event
                                                        ) {
                                                          var $$selectedVal = Array.prototype.filter
                                                            .call(
                                                              $event.target
                                                                .options,
                                                              function(o) {
                                                                return o.selected
                                                              }
                                                            )
                                                            .map(function(o) {
                                                              var val =
                                                                "_value" in o
                                                                  ? o._value
                                                                  : o.value
                                                              return val
                                                            })
                                                          _vm.$set(
                                                            item.cotation,
                                                            "choose_method",
                                                            $event.target
                                                              .multiple
                                                              ? $$selectedVal
                                                              : $$selectedVal[0]
                                                          )
                                                        }
                                                      }
                                                    },
                                                    _vm._l(
                                                      item.cotation,
                                                      function(option) {
                                                        return option.id &&
                                                          option.price
                                                          ? _c(
                                                              "option",
                                                              {
                                                                key: option.id,
                                                                domProps: {
                                                                  value:
                                                                    option.id
                                                                }
                                                              },
                                                              [
                                                                _vm._v(
                                                                  "\n                                                        " +
                                                                    _vm._s(
                                                                      option
                                                                        .company
                                                                        .name
                                                                    ) +
                                                                    " " +
                                                                    _vm._s(
                                                                      option.name
                                                                    ) +
                                                                    " (R$" +
                                                                    _vm._s(
                                                                      option.price
                                                                    ) +
                                                                    ") \n                                                    "
                                                                )
                                                              ]
                                                            )
                                                          : _vm._e()
                                                      }
                                                    )
                                                  )
                                                : _vm._e()
                                            ]
                                          )
                                        ]
                                      )
                                    ])
                                  ])
                                ]
                              : [
                                  _c("label", [_vm._v("Ordem Melhor Envio")]),
                                  _vm._v(" "),
                                  _c("span", [_vm._v(_vm._s(item.order_id))])
                                ]
                          ],
                          2
                        ),
                        _vm._v(" "),
                        _c("li", [
                          _c("div", { staticClass: "me-form" }, [
                            _c(
                              "div",
                              { staticClass: "formBox paddingBox" },
                              [
                                item.cotation.choose_method == 3 ||
                                item.cotation.choose_method == 4
                                  ? [
                                      _c(
                                        "fieldset",
                                        { staticClass: "checkLine" },
                                        [
                                          _c(
                                            "div",
                                            { staticClass: "inputBox" },
                                            [
                                              _c("input", {
                                                directives: [
                                                  {
                                                    name: "model",
                                                    rawName: "v-model",
                                                    value: item.non_commercial,
                                                    expression:
                                                      "item.non_commercial"
                                                  }
                                                ],
                                                attrs: { type: "checkbox" },
                                                domProps: {
                                                  checked: Array.isArray(
                                                    item.non_commercial
                                                  )
                                                    ? _vm._i(
                                                        item.non_commercial,
                                                        null
                                                      ) > -1
                                                    : item.non_commercial
                                                },
                                                on: {
                                                  change: function($event) {
                                                    var $$a =
                                                        item.non_commercial,
                                                      $$el = $event.target,
                                                      $$c = $$el.checked
                                                        ? true
                                                        : false
                                                    if (Array.isArray($$a)) {
                                                      var $$v = null,
                                                        $$i = _vm._i($$a, $$v)
                                                      if ($$el.checked) {
                                                        $$i < 0 &&
                                                          _vm.$set(
                                                            item,
                                                            "non_commercial",
                                                            $$a.concat([$$v])
                                                          )
                                                      } else {
                                                        $$i > -1 &&
                                                          _vm.$set(
                                                            item,
                                                            "non_commercial",
                                                            $$a
                                                              .slice(0, $$i)
                                                              .concat(
                                                                $$a.slice(
                                                                  $$i + 1
                                                                )
                                                              )
                                                          )
                                                      }
                                                    } else {
                                                      _vm.$set(
                                                        item,
                                                        "non_commercial",
                                                        $$c
                                                      )
                                                    }
                                                  }
                                                }
                                              }),
                                              _vm._v(" "),
                                              _c("label", [
                                                _vm._v(
                                                  "Enviar com declaração de conteúdo    "
                                                )
                                              ])
                                            ]
                                          )
                                        ]
                                      ),
                                      _vm._v(" "),
                                      _c("br")
                                    ]
                                  : _vm._e(),
                                _vm._v(" "),
                                (item.cotation.choose_method >= 3 &&
                                  !item.non_commercial) ||
                                item.cotation.choose_method > 4
                                  ? [
                                      _c("fieldset", [
                                        _c("div", [
                                          _c("label", [_vm._v("Nota fiscal")]),
                                          _c("br"),
                                          _vm._v(" "),
                                          _c("input", {
                                            directives: [
                                              {
                                                name: "model",
                                                rawName: "v-model",
                                                value: item.invoice.number,
                                                expression:
                                                  "item.invoice.number"
                                              }
                                            ],
                                            attrs: { type: "text" },
                                            domProps: {
                                              value: item.invoice.number
                                            },
                                            on: {
                                              input: function($event) {
                                                if ($event.target.composing) {
                                                  return
                                                }
                                                _vm.$set(
                                                  item.invoice,
                                                  "number",
                                                  $event.target.value
                                                )
                                              }
                                            }
                                          }),
                                          _c("br"),
                                          _vm._v(" "),
                                          _c("label", [
                                            _vm._v("Chave da nota fiscal")
                                          ]),
                                          _c("br"),
                                          _vm._v(" "),
                                          _c("input", {
                                            directives: [
                                              {
                                                name: "model",
                                                rawName: "v-model",
                                                value: item.invoice.key,
                                                expression: "item.invoice.key"
                                              }
                                            ],
                                            attrs: { type: "text" },
                                            domProps: {
                                              value: item.invoice.key
                                            },
                                            on: {
                                              input: function($event) {
                                                if ($event.target.composing) {
                                                  return
                                                }
                                                _vm.$set(
                                                  item.invoice,
                                                  "key",
                                                  $event.target.value
                                                )
                                              }
                                            }
                                          }),
                                          _c("br"),
                                          _vm._v(" "),
                                          _c("br"),
                                          _vm._v(" "),
                                          _c(
                                            "button",
                                            {
                                              staticClass:
                                                "btn-border -full-blue",
                                              on: {
                                                click: function($event) {
                                                  _vm.updateInvoice(
                                                    item.id,
                                                    item.invoice.number,
                                                    item.invoice.key
                                                  )
                                                }
                                              }
                                            },
                                            [_vm._v("Salvar")]
                                          )
                                        ])
                                      ])
                                    ]
                                  : _vm._e()
                              ],
                              2
                            )
                          ])
                        ]),
                        _vm._v(" "),
                        _c("li", { staticClass: "-center" }, [
                          _vm.buttonCartShow(
                            item.cotation.choose_method,
                            item.non_commercial,
                            item.invoice.number,
                            item.invoice.key,
                            item.status
                          )
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -adicionar",
                                  attrs: {
                                    href: "javascript:;",
                                    "data-tip": "Adicionar"
                                  },
                                  on: {
                                    click: function($event) {
                                      _vm.addCart({
                                        id: item.id,
                                        choosen: item.cotation.choose_method,
                                        non_commercial: item.non_commercial
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      staticStyle: {
                                        "enable-background":
                                          "new 0 0 511.999 511.999"
                                      },
                                      attrs: {
                                        version: "1.1",
                                        id: "cart-add",
                                        xmlns: "http://www.w3.org/2000/svg",
                                        "xmlns:xlink":
                                          "http://www.w3.org/1999/xlink",
                                        x: "0px",
                                        y: "0px",
                                        viewBox: "0 0 511.999 511.999",
                                        "xml:space": "preserve"
                                      }
                                    },
                                    [
                                      _c("g", [
                                        _c("g", [
                                          _c("path", {
                                            attrs: {
                                              d:
                                                "M214.685,402.828c-24.829,0-45.029,20.2-45.029,45.029c0,24.829,20.2,45.029,45.029,45.029s45.029-20.2,45.029-45.029\n                                            C259.713,423.028,239.513,402.828,214.685,402.828z M214.685,467.742c-10.966,0-19.887-8.922-19.887-19.887\n                                            c0-10.966,8.922-19.887,19.887-19.887s19.887,8.922,19.887,19.887C234.572,458.822,225.65,467.742,214.685,467.742z"
                                            }
                                          })
                                        ])
                                      ]),
                                      _vm._v(" "),
                                      _c("g", [
                                        _c("g", [
                                          _c("path", {
                                            attrs: {
                                              d:
                                                "M372.63,402.828c-24.829,0-45.029,20.2-45.029,45.029c0,24.829,20.2,45.029,45.029,45.029s45.029-20.2,45.029-45.029\n                                            C417.658,423.028,397.458,402.828,372.63,402.828z M372.63,467.742c-10.966,0-19.887-8.922-19.887-19.887\n                                            c0-10.966,8.922-19.887,19.887-19.887c10.966,0,19.887,8.922,19.887,19.887C392.517,458.822,383.595,467.742,372.63,467.742z"
                                            }
                                          })
                                        ])
                                      ]),
                                      _vm._v(" "),
                                      _c("g", [
                                        _c("g", [
                                          _c("path", {
                                            attrs: {
                                              d:
                                                "M383.716,165.755H203.567c-6.943,0-12.571,5.628-12.571,12.571c0,6.943,5.629,12.571,12.571,12.571h180.149\n                                            c6.943,0,12.571-5.628,12.571-12.571C396.287,171.382,390.659,165.755,383.716,165.755z"
                                            }
                                          })
                                        ])
                                      ]),
                                      _vm._v(" "),
                                      _c("g", [
                                        _c("g", [
                                          _c("path", {
                                            attrs: {
                                              d:
                                                "M373.911,231.035H213.373c-6.943,0-12.571,5.628-12.571,12.571s5.628,12.571,12.571,12.571h160.537\n                                            c6.943,0,12.571-5.628,12.571-12.571C386.481,236.664,380.853,231.035,373.911,231.035z"
                                            }
                                          })
                                        ])
                                      ]),
                                      _vm._v(" "),
                                      _c("g", [
                                        _c("g", [
                                          _c("path", {
                                            attrs: {
                                              d:
                                                "M506.341,109.744c-4.794-5.884-11.898-9.258-19.489-9.258H95.278L87.37,62.097c-1.651-8.008-7.113-14.732-14.614-17.989\n                                            l-55.177-23.95c-6.37-2.767-13.773,0.156-16.536,6.524c-2.766,6.37,0.157,13.774,6.524,16.537L62.745,67.17l60.826,295.261\n                                            c2.396,11.628,12.752,20.068,24.625,20.068h301.166c6.943,0,12.571-5.628,12.571-12.571c0-6.943-5.628-12.571-12.571-12.571\n                                            H148.197l-7.399-35.916H451.69c11.872,0,22.229-8.44,24.624-20.068l35.163-170.675\n                                            C513.008,123.266,511.136,115.627,506.341,109.744z M451.69,296.301H135.619l-35.161-170.674l386.393,0.001L451.69,296.301z"
                                            }
                                          })
                                        ])
                                      ])
                                    ]
                                  )
                                ]
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          item.status == "paid" && item.order_id && item.id
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -excluir",
                                  attrs: { "data-tip": "Cancelar" },
                                  on: {
                                    click: function($event) {
                                      _vm.cancelCart({
                                        id: item.id,
                                        order_id: item.order_id
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      attrs: {
                                        xmlns: "http://www.w3.org/2000/svg",
                                        viewBox: "0 0 383.2 500"
                                      }
                                    },
                                    [
                                      _c("title", [_vm._v("Cancelar")]),
                                      _c(
                                        "g",
                                        {
                                          attrs: {
                                            id: "Camada_2",
                                            "data-name": "Camada 2"
                                          }
                                        },
                                        [
                                          _c(
                                            "g",
                                            {
                                              attrs: {
                                                id: "Camada_10",
                                                "data-name": "Camada 10"
                                              }
                                            },
                                            [
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              })
                                            ]
                                          )
                                        ]
                                      )
                                    ]
                                  )
                                ]
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          item.status &&
                          item.order_id &&
                          item.id &&
                          item.status == "pending"
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -adicionar",
                                  attrs: {
                                    href: "javascript:;",
                                    "data-tip": "Pagar"
                                  },
                                  on: {
                                    click: function($event) {
                                      _vm.payTicket({
                                        id: item.id,
                                        order_id: item.order_id
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      attrs: {
                                        version: "1.1",
                                        id: "pagar",
                                        xmlns: "http://www.w3.org/2000/svg",
                                        "xmlns:xlink":
                                          "http://www.w3.org/1999/xlink",
                                        x: "0px",
                                        y: "0px",
                                        viewBox: "0 0 24 24",
                                        "enable-background": "new 0 0 24 24",
                                        "xml:space": "preserve"
                                      }
                                    },
                                    [
                                      _c("path", {
                                        attrs: {
                                          d:
                                            "M12,2c5.514,0,10,4.486,10,10s-4.486,10-10,10S2,17.514,2,12S6.486,2,12,2z M12,0C5.373,0,0,5.373,0,12s5.373,12,12,12\n                                    s12-5.373,12-12S18.627,0,12,0z M16,14.083c0-2.145-2.232-2.742-3.943-3.546c-1.039-0.54-0.908-1.829,0.581-1.916\n                                    c0.826-0.05,1.675,0.195,2.443,0.465l0.362-1.647C14.536,7.163,13.724,7.037,13,7.018V6h-1v1.067\n                                    c-1.945,0.267-2.984,1.487-2.984,2.85c0,2.438,2.847,2.81,3.778,3.243c1.27,0.568,1.035,1.75-0.114,2.011\n                                    c-0.997,0.226-2.269-0.168-3.225-0.54L9,16.275c0.894,0.462,1.965,0.708,3,0.727V18h1v-1.053C14.657,16.715,16.002,15.801,16,14.083\n                                    z"
                                        }
                                      })
                                    ]
                                  )
                                ]
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          item.status && item.status == "paid" && item.order_id
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -adicionar",
                                  attrs: { "data-tip": "Gerar etiqueta" },
                                  on: {
                                    click: function($event) {
                                      _vm.createTicket({
                                        id: item.id,
                                        order_id: item.order_id
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      attrs: {
                                        version: "1.1",
                                        id: "imprimir",
                                        xmlns: "http://www.w3.org/2000/svg",
                                        "xmlns:xlink":
                                          "http://www.w3.org/1999/xlink",
                                        x: "0px",
                                        y: "0px",
                                        viewBox: "0 0 191.0681 184.5303",
                                        "enable-background":
                                          "new 0 0 191.0681 184.5303",
                                        "xml:space": "preserve"
                                      }
                                    },
                                    [
                                      _c("path", {
                                        attrs: {
                                          id: "imprimir-path4",
                                          d:
                                            "M60.1948,0H130.35c5.3073,0,10.1271,2.1659,13.6165,5.6554\n                                    c3.4895,3.4894,5.6554,8.3092,5.6554,13.6165v29.3652h21.6803c5.4433,0,10.3867,2.2215,13.9654,5.8006\n                                    c3.579,3.579,5.8005,8.5223,5.8005,13.9657v62.1068c0,5.4434-2.2215,10.3867-5.8005,13.9655\n                                    c-3.5787,3.579-8.5221,5.8005-13.9654,5.8005h-20.1121v17.763c0,4.5425-1.8533,8.6672-4.8385,11.6527\n                                    c-2.9854,2.9854-7.1101,4.8384-11.6529,4.8384H55.0601c-4.5428,0-8.6674-1.8533-11.6529-4.8384\n                                    c-2.9852-2.9855-4.8385-7.1102-4.8385-11.6527v-17.763H19.766c-5.4434,0-10.3867-2.2215-13.9655-5.8005\n                                    C2.2215,140.8969,0,135.9536,0,130.5102V68.4034C0,62.96,2.2215,58.0167,5.8005,54.4377c3.5788-3.5791,8.5221-5.8006,13.9655-5.8006\n                                    h21.1569V19.2719c0-5.3073,2.166-10.1271,5.6554-13.6165C50.0675,2.1659,54.8872,0,60.1948,0z M158.8788,72.9145\n                                    c4.4407,0,8.0407,3.6292,8.0407,8.1062c0,4.4767-3.6,8.1062-8.0407,8.1062c-4.4408,0-8.0408-3.6295-8.0408-8.1062\n                                    C150.838,76.5437,154.438,72.9145,158.8788,72.9145z M69.6444,160.0934c-2.3743,0-4.299-2.2124-4.299-4.9416\n                                    c0-2.7289,1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2125,4.299,4.9414c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z\n                                    M69.6444,141.9199c-2.3743,0-4.299-2.2124-4.299-4.9416s1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2122,4.299,4.9414\n                                    c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M136.3657,150.2762v-27.8807c0-0.4507-0.1899-0.866-0.4955-1.1716\n                                    c-0.3055-0.3056-0.7208-0.4952-1.1715-0.4952H55.0601c-0.4507,0-0.8659,0.1896-1.1715,0.4952\n                                    c-0.3056,0.3056-0.4952,0.7209-0.4952,1.1716v27.8807v17.763c0,0.4504,0.1896,0.8657,0.4952,1.1713\n                                    c0.3056,0.3056,0.7208,0.4955,1.1715,0.4955h79.6386c0.4507,0,0.866-0.1899,1.1715-0.4955\n                                    c0.3056-0.3056,0.4955-0.7209,0.4955-1.1713V150.2762L136.3657,150.2762z M149.6219,63.4618H40.9229H19.766\n                                    c-1.351,0-2.5849,0.5581-3.4841,1.4573c-0.8991,0.8991-1.4573,2.133-1.4573,3.4843v62.1068c0,1.351,0.5582,2.5849,1.4573,3.4841\n                                    c0.8992,0.8991,2.1331,1.4573,3.4841,1.4573h18.8027v-13.0561c0-4.5428,1.8531-8.6673,4.8385-11.653\n                                    c2.9855-2.9851,7.1101-4.8384,11.6529-4.8384h79.6386c4.5428,0,8.6675,1.8533,11.6529,4.8384\n                                    c2.9855,2.9857,4.8385,7.1102,4.8385,11.653v13.0561h20.1121c1.351,0,2.5849-0.5582,3.484-1.4573\n                                    c0.8992-0.8992,1.4573-2.1331,1.4573-3.4841V68.4035c0-1.3513-0.5581-2.5852-1.4573-3.4843\n                                    c-0.8991-0.8992-2.133-1.4573-3.484-1.4573L149.6219,63.4618L149.6219,63.4618z M130.35,14.8246H60.1948\n                                    c-1.2155,0-2.3258,0.5026-3.1354,1.3122c-0.8093,0.8096-1.3121,1.9199-1.3121,3.1351v29.3652h79.05V19.2719\n                                    c0-1.2152-0.5026-2.3255-1.3121-3.1351C132.6759,15.3272,131.5653,14.8246,130.35,14.8246z"
                                        }
                                      }),
                                      _vm._v(" "),
                                      _c("path", {
                                        attrs: {
                                          id: "imprimir-path6",
                                          d:
                                            "M158.8787,72.8156c2.2475,0,4.2825,0.9187,5.7555,2.4036\n                                    c1.4729,1.4849,2.3841,3.5362,2.3841,5.8014s-0.9112,4.3165-2.3841,5.8015c-1.473,1.4849-3.508,2.4035-5.7555,2.4035\n                                    s-4.2826-0.9186-5.7555-2.4035c-1.473-1.485-2.3841-3.5363-2.3841-5.8015c0-2.2652,0.9111-4.3165,2.3841-5.8014\n                                    C154.5961,73.7343,156.6312,72.8156,158.8787,72.8156z M164.4944,75.3581c-1.437-1.4486-3.4225-2.3448-5.6157-2.3448\n                                    c-2.1933,0-4.1788,0.8962-5.6158,2.3448c-1.4372,1.4489-2.3261,3.451-2.3261,5.6625c0,2.2116,0.8889,4.2137,2.3261,5.6625\n                                    c1.437,1.4487,3.4225,2.3449,5.6158,2.3449c2.1932,0,4.1787-0.8962,5.6157-2.3449c1.4372-1.4488,2.3262-3.4509,2.3262-5.6625\n                                    C166.8206,78.8091,165.9316,76.807,164.4944,75.3581z"
                                        }
                                      })
                                    ]
                                  )
                                ]
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          item.status &&
                          (item.status == "generated" ||
                            item.status == "printed")
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -adicionar",
                                  attrs: { "data-tip": "Imprimir etiqueta" },
                                  on: {
                                    click: function($event) {
                                      _vm.printTicket({
                                        id: item.id,
                                        order_id: item.order_id
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      attrs: {
                                        version: "1.1",
                                        id: "imprimirok",
                                        xmlns: "http://www.w3.org/2000/svg",
                                        "xmlns:xlink":
                                          "http://www.w3.org/1999/xlink",
                                        x: "0px",
                                        y: "0px",
                                        viewBox: "0 0 228.2998 219.331",
                                        "enable-background":
                                          "new 0 0 228.2998 219.331",
                                        "xml:space": "preserve"
                                      }
                                    },
                                    [
                                      _c("path", {
                                        attrs: {
                                          id: "imprimirok-path4",
                                          d:
                                            "M60.1948,34.8006H130.35c5.3073,0,10.1271,2.1659,13.6165,5.6554\n                                    c3.4895,3.4894,5.6554,8.3092,5.6554,13.6165v29.3652h21.6803c5.4433,0,10.3867,2.2215,13.9654,5.8006\n                                    c3.579,3.579,5.8005,8.5223,5.8005,13.9657v62.1068c0,5.4434-2.2215,10.3867-5.8005,13.9655\n                                    c-3.5787,3.579-8.5221,5.8005-13.9654,5.8005h-20.1121v17.763c0,4.5425-1.8533,8.6672-4.8385,11.6527\n                                    c-2.9854,2.9854-7.1101,4.8384-11.6529,4.8384H55.0601c-4.5428,0-8.6674-1.8533-11.6529-4.8384\n                                    c-2.9852-2.9855-4.8385-7.1102-4.8385-11.6527v-17.763H19.766c-5.4434,0-10.3867-2.2215-13.9655-5.8005\n                                    C2.2215,175.6975,0,170.7542,0,165.3108V103.204c0-5.4434,2.2215-10.3867,5.8005-13.9657\n                                    c3.5788-3.5791,8.5221-5.8006,13.9655-5.8006h21.1569V54.0725c0-5.3073,2.166-10.1271,5.6554-13.6165\n                                    C50.0675,36.9665,54.8872,34.8006,60.1948,34.8006z M158.8788,107.7151c4.4407,0,8.0407,3.6292,8.0407,8.1062\n                                    c0,4.4767-3.6,8.1062-8.0407,8.1062c-4.4408,0-8.0408-3.6295-8.0408-8.1062C150.838,111.3443,154.438,107.7151,158.8788,107.7151z\n                                    M69.6444,194.894c-2.3743,0-4.299-2.2124-4.299-4.9416c0-2.7289,1.9247-4.9414,4.299-4.9414h50.7291\n                                    c2.3743,0,4.299,2.2125,4.299,4.9414c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M69.6444,176.7205\n                                    c-2.3743,0-4.299-2.2124-4.299-4.9416s1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2122,4.299,4.9414\n                                    c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M136.3657,185.0768v-27.8807c0-0.4507-0.1899-0.866-0.4955-1.1716\n                                    c-0.3055-0.3056-0.7208-0.4952-1.1715-0.4952H55.0601c-0.4507,0-0.8659,0.1896-1.1715,0.4952\n                                    c-0.3056,0.3056-0.4952,0.7209-0.4952,1.1716v27.8807v17.763c0,0.4504,0.1896,0.8657,0.4952,1.1713\n                                    c0.3056,0.3056,0.7208,0.4955,1.1715,0.4955h79.6386c0.4507,0,0.866-0.1899,1.1715-0.4955\n                                    c0.3056-0.3056,0.4955-0.7209,0.4955-1.1713V185.0768L136.3657,185.0768z M149.6219,98.2624H40.9229H19.766\n                                    c-1.351,0-2.5849,0.5581-3.4841,1.4573c-0.8991,0.8991-1.4573,2.133-1.4573,3.4843v62.1068c0,1.351,0.5582,2.5849,1.4573,3.4841\n                                    c0.8992,0.8991,2.1331,1.4573,3.4841,1.4573h18.8027v-13.0561c0-4.5428,1.8531-8.6673,4.8385-11.653\n                                    c2.9855-2.9851,7.1101-4.8384,11.6529-4.8384h79.6386c4.5428,0,8.6675,1.8533,11.6529,4.8384\n                                    c2.9855,2.9857,4.8385,7.1102,4.8385,11.653v13.0561h20.1121c1.351,0,2.5849-0.5582,3.484-1.4573\n                                    c0.8992-0.8992,1.4573-2.1331,1.4573-3.4841v-62.1068c0-1.3513-0.5581-2.5852-1.4573-3.4843\n                                    c-0.8991-0.8992-2.133-1.4573-3.484-1.4573L149.6219,98.2624L149.6219,98.2624z M130.35,49.6252H60.1948\n                                    c-1.2155,0-2.3258,0.5026-3.1354,1.3122c-0.8093,0.8096-1.3121,1.9199-1.3121,3.1351v29.3652h79.05V54.0725\n                                    c0-1.2152-0.5026-2.3255-1.3121-3.1351C132.6759,50.1278,131.5653,49.6252,130.35,49.6252z"
                                        }
                                      }),
                                      _vm._v(" "),
                                      _c("path", {
                                        attrs: {
                                          id: "imprimirok-path6",
                                          d:
                                            "M158.8787,107.6162c2.2475,0,4.2825,0.9187,5.7555,2.4036\n                                    c1.4729,1.4849,2.3841,3.5362,2.3841,5.8014s-0.9112,4.3165-2.3841,5.8015c-1.473,1.4849-3.508,2.4035-5.7555,2.4035\n                                    s-4.2826-0.9186-5.7555-2.4035c-1.473-1.485-2.3841-3.5363-2.3841-5.8015c0-2.2652,0.9111-4.3165,2.3841-5.8014\n                                    C154.5961,108.5349,156.6312,107.6162,158.8787,107.6162z M164.4944,110.1587c-1.437-1.4486-3.4225-2.3448-5.6157-2.3448\n                                    c-2.1933,0-4.1788,0.8962-5.6158,2.3448c-1.4372,1.4489-2.3261,3.451-2.3261,5.6625c0,2.2116,0.8889,4.2137,2.3261,5.6625\n                                    c1.437,1.4487,3.4225,2.3449,5.6158,2.3449c2.1932,0,4.1787-0.8962,5.6157-2.3449c1.4372-1.4488,2.3262-3.4509,2.3262-5.6625\n                                    C166.8206,113.6097,165.9316,111.6076,164.4944,110.1587z"
                                        }
                                      }),
                                      _vm._v(" "),
                                      _c("path", {
                                        attrs: {
                                          id: "imprimirok-path8",
                                          fill: "#2BC866",
                                          d:
                                            "M228.2998,42.8513c0,23.6661-19.1852,42.8513-42.8513,42.8513l0,0\n                                    c-23.6661,0-42.8513-19.1852-42.8513-42.8513S161.7824,0,185.4485,0S228.2998,19.1852,228.2998,42.8513z"
                                        }
                                      }),
                                      _vm._v(" "),
                                      _c(
                                        "g",
                                        {
                                          attrs: { id: "imprimirok-layer1000" }
                                        },
                                        [
                                          _c("path", {
                                            attrs: {
                                              id: "imprimirok-path11",
                                              fill: "#FFFFFF",
                                              d:
                                                "M175.6407,63.0407c0.4235,0.4236,0.9982,0.6616,1.5973,0.6616\n                                        c0.5992,0,1.1738-0.2381,1.5972-0.6616l30.7956-30.7956c0.4238-0.4236,0.6617-0.9981,0.6617-1.5972\n                                        c0-0.5993-0.2379-1.1738-0.6617-1.5974l-6.3891-6.389c-0.882-0.882-2.3123-0.8822-3.1946,0l-22.8085,22.8088l-6.3894-6.3894\n                                        c-0.4236-0.4236-0.9982-0.6617-1.5973-0.6617c-0.5991,0-1.1735,0.2381-1.5972,0.6617l-6.3892,6.3891\n                                        c-0.882,0.8822-0.882,2.3124,0,3.1946L175.6407,63.0407L175.6407,63.0407z"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1001"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1002"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1003"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1004"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1005"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1006"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1007"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1008"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1009"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1010"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1011"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1012"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1013"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1014"
                                            }
                                          }),
                                          _vm._v(" "),
                                          _c("g", {
                                            attrs: {
                                              id: "imprimirok-layer1015"
                                            }
                                          })
                                        ]
                                      )
                                    ]
                                  )
                                ]
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          item.status &&
                          item.order_id &&
                          item.id &&
                          item.status != "paid"
                            ? _c(
                                "a",
                                {
                                  staticClass: "action-button -excluir",
                                  attrs: {
                                    href: "javascript:;",
                                    "data-tip": "Cancelar"
                                  },
                                  on: {
                                    click: function($event) {
                                      _vm.removeCart({
                                        id: item.id,
                                        order_id: item.order_id
                                      })
                                    }
                                  }
                                },
                                [
                                  _c(
                                    "svg",
                                    {
                                      staticClass: "ico",
                                      attrs: {
                                        xmlns: "http://www.w3.org/2000/svg",
                                        viewBox: "0 0 383.2 500"
                                      }
                                    },
                                    [
                                      _c("title", [_vm._v("Cancelar")]),
                                      _c(
                                        "g",
                                        {
                                          attrs: {
                                            id: "Camada_2",
                                            "data-name": "Camada 2"
                                          }
                                        },
                                        [
                                          _c(
                                            "g",
                                            {
                                              attrs: {
                                                id: "Camada_10",
                                                "data-name": "Camada 10"
                                              }
                                            },
                                            [
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              }),
                                              _c("path", {
                                                staticClass: "cls-1",
                                                attrs: {
                                                  d:
                                                    "M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
                                                }
                                              })
                                            ]
                                          )
                                        ]
                                      )
                                    ]
                                  )
                                ]
                              )
                            : _vm._e()
                        ])
                      ])
                    ])
                  })
                )
              ])
            ]
          )
        : _c("div", [_c("p", [_vm._v("Nenhum registro encontrado")])]),
      _vm._v(" "),
      _c(
        "button",
        {
          staticClass: "btn-border -full-green",
          on: {
            click: function($event) {
              _vm.loadMore({ status: _vm.status, wpstatus: _vm.wpstatus })
            }
          }
        },
        [_vm._v("Carregar mais")]
      ),
      _vm._v(" "),
      _c("transition", { attrs: { name: "fade" } }, [
        _c(
          "div",
          {
            directives: [
              {
                name: "show",
                rawName: "v-show",
                value: _vm.show_modal,
                expression: "show_modal"
              }
            ],
            staticClass: "me-modal"
          },
          [
            _c("div", [
              _c("p", { staticClass: "title" }, [_vm._v("Atenção")]),
              _vm._v(" "),
              _c("div", { staticClass: "content" }, [
                _c("p", { staticClass: "txt" }, [_vm._v(_vm._s(_vm.msg_modal))])
              ]),
              _vm._v(" "),
              _c("div", { staticClass: "buttons -center" }, [
                _c(
                  "button",
                  {
                    staticClass: "btn-border -full-blue",
                    attrs: { type: "button" },
                    on: { click: _vm.close }
                  },
                  [_vm._v("Fechar")]
                )
              ])
            ])
          ]
        )
      ]),
      _vm._v(" "),
      _c("div", {
        directives: [
          {
            name: "show",
            rawName: "v-show",
            value: _vm.show_loader,
            expression: "show_loader"
          }
        ],
        staticClass: "me-modal"
      })
    ],
    2
  )
}
var staticRenderFns = [
  function() {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("div", [
      _c("div", { staticClass: "grid" }, [
        _c("div", { staticClass: "col-12-12" }, [
          _c("h1", [_vm._v("Meus pedidos")])
        ]),
        _vm._v(" "),
        _c("hr"),
        _vm._v(" "),
        _c("br")
      ])
    ])
  },
  function() {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("ul", { staticClass: "head" }, [
      _c("li", [_c("span", [_vm._v("ID")])]),
      _vm._v(" "),
      _c("li", [_c("span", [_vm._v("Valor")])]),
      _vm._v(" "),
      _c("li", [_c("span", [_vm._v("Destinatário")])]),
      _vm._v(" "),
      _c("li", [_c("span", [_vm._v("Cotação")])]),
      _vm._v(" "),
      _c("li", [_c("span", [_vm._v("Documentos")])]),
      _vm._v(" "),
      _c("li", [_c("span", [_vm._v("Ações")])])
    ])
  }
]
render._withStripped = true
var esExports = { render: render, staticRenderFns: staticRenderFns }
/* harmony default export */ __webpack_exports__["a"] = (esExports);
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-05a7e32e", esExports)
  }
}

/***/ }),
/* 66 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Configuracoes_vue__ = __webpack_require__(20);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_260cb748_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Configuracoes_vue__ = __webpack_require__(68);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(67)
}
var normalizeComponent = __webpack_require__(1)
/* script */


/* template */

/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = injectStyle
/* scopeId */
var __vue_scopeId__ = "data-v-260cb748"
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Configuracoes_vue__["a" /* default */],
  __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_260cb748_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Configuracoes_vue__["a" /* default */],
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "assets/src/admin/components/Configuracoes.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-260cb748", Component.options)
  } else {
    hotAPI.reload("data-v-260cb748", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

/* harmony default export */ __webpack_exports__["default"] = (Component.exports);


/***/ }),
/* 67 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 68 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", [
    _c("div", { staticClass: "wpme_config" }, [
      _c("h2", [_vm._v("Escolha o endereço para cálculo de frete")]),
      _vm._v(" "),
      _c("div", { staticClass: "wpme_flex" }, [
        _c(
          "ul",
          { staticClass: "wpme_address" },
          _vm._l(_vm.addresses, function(option) {
            return _c("li", { key: option.id, attrs: { value: option.id } }, [
              _c("label", { attrs: { for: "41352" } }, [
                _c("div", { staticClass: "wpme_address-top" }, [
                  _c("input", {
                    directives: [
                      {
                        name: "model",
                        rawName: "v-model",
                        value: _vm.address,
                        expression: "address"
                      }
                    ],
                    attrs: { type: "radio", id: option.id },
                    domProps: {
                      value: option.id,
                      checked: _vm._q(_vm.address, option.id)
                    },
                    on: {
                      click: function($event) {
                        _vm.showAgencies({
                          city: option.city,
                          state: option.state
                        })
                      },
                      change: function($event) {
                        _vm.address = option.id
                      }
                    }
                  }),
                  _vm._v(" "),
                  _c("h2", [_vm._v(_vm._s(option.label))])
                ]),
                _vm._v(" "),
                _c("div", { staticClass: "wpme_address-body" }, [
                  _c("ul", [
                    _c("li", [
                      _vm._v(_vm._s(option.address + ", " + option.number))
                    ]),
                    _vm._v(" "),
                    _c("li", [
                      _vm._v(
                        _vm._s(
                          option.district +
                            " - " +
                            option.city +
                            "/" +
                            option.state
                        )
                      )
                    ]),
                    _vm._v(" "),
                    _c("li", [_vm._v(_vm._s("" + option.complement))]),
                    _vm._v(" "),
                    _c("li", [_vm._v(_vm._s("CEP: " + option.postal_code))])
                  ])
                ])
              ])
            ])
          })
        )
      ])
    ]),
    _vm._v(" "),
    _c("div", { staticClass: "table-box" }, [
      _c("label", [_vm._v("Agências Jadlog")]),
      _c("br"),
      _vm._v(" "),
      _c(
        "select",
        {
          directives: [
            {
              name: "model",
              rawName: "v-model",
              value: _vm.agency,
              expression: "agency"
            }
          ],
          attrs: { name: "agencies", id: "agencies" },
          on: {
            change: function($event) {
              var $$selectedVal = Array.prototype.filter
                .call($event.target.options, function(o) {
                  return o.selected
                })
                .map(function(o) {
                  var val = "_value" in o ? o._value : o.value
                  return val
                })
              _vm.agency = $event.target.multiple
                ? $$selectedVal
                : $$selectedVal[0]
            }
          }
        },
        [
          _c("option", { attrs: { value: "" } }, [_vm._v("Selecione...")]),
          _vm._v(" "),
          _vm._l(_vm.agencies, function(option) {
            return _c(
              "option",
              { key: option.id, domProps: { value: option.id } },
              [_vm._v(_vm._s(option.name))]
            )
          })
        ],
        2
      )
    ]),
    _vm._v(" "),
    _c("div", { staticClass: "wpme_config" }, [
      _c("h2", [_vm._v("Escolha a sua loja")]),
      _vm._v(" "),
      _c("div", { staticClass: "wpme_flex" }, [
        _c(
          "ul",
          { staticClass: "wpme_address" },
          _vm._l(_vm.stores, function(option) {
            return _c("li", { key: option.id, attrs: { value: option.id } }, [
              _c("label", { attrs: { for: "41352" } }, [
                _c("div", { staticClass: "wpme_address-top" }, [
                  _c("input", {
                    directives: [
                      {
                        name: "model",
                        rawName: "v-model",
                        value: _vm.store,
                        expression: "store"
                      }
                    ],
                    attrs: { type: "radio", id: option.id },
                    domProps: {
                      value: option.id,
                      checked: _vm._q(_vm.store, option.id)
                    },
                    on: {
                      change: function($event) {
                        _vm.store = option.id
                      }
                    }
                  }),
                  _vm._v(" "),
                  _c("h2", [_vm._v(_vm._s(option.name))])
                ]),
                _vm._v(" "),
                _c("div", { staticClass: "wpme_address-body" }, [
                  _c("ul", [
                    _c("li", [_vm._v("CNPJ " + _vm._s("" + option.document))]),
                    _vm._v(" "),
                    _c("li", [
                      _vm._v(
                        "Registro estadual " +
                          _vm._s("" + option.state_register)
                      )
                    ]),
                    _vm._v(" "),
                    _c("li", [_vm._v("E-mail " + _vm._s(option.email + " "))])
                  ])
                ])
              ])
            ])
          })
        )
      ])
    ]),
    _vm._v(" "),
    _c(
      "button",
      { staticClass: "btn-border -blue", on: { click: _vm.updateConfig } },
      [_vm._v("salvar")]
    )
  ])
}
var staticRenderFns = []
render._withStripped = true
var esExports = { render: render, staticRenderFns: staticRenderFns }
/* harmony default export */ __webpack_exports__["a"] = (esExports);
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-260cb748", esExports)
  }
}

/***/ }),
/* 69 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Token_vue__ = __webpack_require__(21);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_1bf58fd9_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Token_vue__ = __webpack_require__(71);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(70)
}
var normalizeComponent = __webpack_require__(1)
/* script */


/* template */

/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = injectStyle
/* scopeId */
var __vue_scopeId__ = "data-v-1bf58fd9"
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Token_vue__["a" /* default */],
  __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_1bf58fd9_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Token_vue__["a" /* default */],
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "assets/src/admin/components/Token.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-1bf58fd9", Component.options)
  } else {
    hotAPI.reload("data-v-1bf58fd9", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

/* harmony default export */ __webpack_exports__["default"] = (Component.exports);


/***/ }),
/* 70 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 71 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "app-token" }, [
    _c("h1", [_vm._v("Meu Token")]),
    _vm._v(" "),
    _c("span", [_vm._v("Insira o token gerado na Melhor Envio")]),
    _vm._v(" "),
    _c("br"),
    _vm._v(" "),
    _c("textarea", {
      directives: [
        {
          name: "model",
          rawName: "v-model",
          value: _vm.token,
          expression: "token"
        }
      ],
      attrs: { rows: "20", cols: "100", placeholder: "Token" },
      domProps: { value: _vm.token },
      on: {
        input: function($event) {
          if ($event.target.composing) {
            return
          }
          _vm.token = $event.target.value
        }
      }
    }),
    _vm._v(" "),
    _c("br"),
    _vm._v(" "),
    _c("br"),
    _vm._v(" "),
    _c(
      "button",
      {
        staticClass: "btn-border -full-green",
        on: {
          click: function($event) {
            _vm.saveToken()
          }
        }
      },
      [_vm._v("Salvar")]
    ),
    _vm._v(" "),
    _c("div", {
      directives: [
        {
          name: "show",
          rawName: "v-show",
          value: _vm.show_loader,
          expression: "show_loader"
        }
      ],
      staticClass: "me-modal"
    })
  ])
}
var staticRenderFns = []
render._withStripped = true
var esExports = { render: render, staticRenderFns: staticRenderFns }
/* harmony default export */ __webpack_exports__["a"] = (esExports);
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-1bf58fd9", esExports)
  }
}

/***/ }),
/* 72 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});
/**
 * As we are using hash based navigation, hack fix
 * to highlight the current selected menu
 *
 * Requires jQuery
 */
function menuFix(slug) {
    var $ = jQuery;

    var menuRoot = $('#toplevel_page_' + slug);
    var currentUrl = window.location.href;
    var currentPath = currentUrl.substr(currentUrl.indexOf('admin.php'));

    menuRoot.on('click', 'a', function () {
        var self = $(this);

        $('ul.wp-submenu li', menuRoot).removeClass('current');

        if (self.hasClass('wp-has-submenu')) {
            $('li.wp-first-item', menuRoot).addClass('current');
        } else {
            self.parents('li').addClass('current');
        }
    });

    $('ul.wp-submenu a', menuRoot).each(function (index, el) {
        if ($(el).attr('href') === currentPath) {
            $(el).parent().addClass('current');
            return;
        }
    });
}

exports.default = menuFix;

/***/ }),
/* 73 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _vue = __webpack_require__(3);

var _vue2 = _interopRequireDefault(_vue);

var _vuex = __webpack_require__(5);

var _vuex2 = _interopRequireDefault(_vuex);

var _orders = __webpack_require__(74);

var _orders2 = _interopRequireDefault(_orders);

var _balance = __webpack_require__(75);

var _balance2 = _interopRequireDefault(_balance);

var _configuration = __webpack_require__(76);

var _configuration2 = _interopRequireDefault(_configuration);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

_vue2.default.use(_vuex2.default);

var store = new _vuex2.default.Store({
    modules: {
        orders: _orders2.default,
        balance: _balance2.default,
        configuration: _configuration2.default
    }
});

exports.default = store;

/***/ }),
/* 74 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _axios = __webpack_require__(4);

var _axios2 = _interopRequireDefault(_axios);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var orders = {
    namespaced: true,
    state: {
        orders: [],
        show_loader: true,
        show_modal: false,
        msg_modal: '',
        filters: {
            limit: 10,
            skip: 10,
            status: 'all',
            wpstatus: 'all'
        }
    },
    mutations: {
        retrieveMany: function retrieveMany(state, data) {
            state.orders = data;
        },
        loadMore: function loadMore(state, data) {

            state.filters.skip += data.length;
            data.map(function (item) {
                state.orders.push(item);
            });
        },
        removeCart: function removeCart(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            delete order.content.status;
            delete order.content.order_id;
            state.orders.splice(order.position, 1, order.content);
        },
        cancelCart: function cancelCart(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            order.content.status = 'pending';
            state.orders.splice(order.position, 1, order.content);
        },
        addCart: function addCart(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data.id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            order.content.status = 'pending';
            order.content.order_id = data.order_id;
            state.orders.splice(order.position, 1, order.content);
        },
        payTicket: function payTicket(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            order.content.status = 'paid';
            state.orders.splice(order.position, 1, order.content);
        },
        createTicket: function createTicket(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            order.content.status = 'generated';
            state.orders.splice(order.position, 1, order.content);
        },
        printTicket: function printTicket(state, data) {
            var order = void 0;
            state.orders.find(function (item, index) {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    };
                }
            });
            order.content.status = 'printed';
            state.orders.splice(order.position, 1, order.content);
        },
        toggleLoader: function toggleLoader(state, data) {
            state.show_loader = data;
        },
        toggleModal: function toggleModal(state, data) {
            state.show_modal = data;
        },
        setMsgModal: function setMsgModal(state, data) {
            state.msg_modal = data;
        }
    },
    getters: {
        getOrders: function getOrders(state) {
            return state.orders;
        },
        toggleLoader: function toggleLoader(state) {
            return state.show_loader;
        },
        setMsgModal: function setMsgModal(state) {
            return state.msg_modal;
        },
        showModal: function showModal(state) {
            return state.show_modal;
        }

    },
    actions: {
        retrieveMany: function retrieveMany(_ref, data) {
            var commit = _ref.commit;

            commit('toggleLoader', true);
            var content = {
                action: 'get_orders',
                limit: 10,
                skip: 0,
                status: data.status ? data.status : null,
                wpstatus: data.wpstatus ? data.wpstatus : null
            };

            _axios2.default.get('' + ajaxurl, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('retrieveMany', response.data);
                    commit('toggleLoader', false);
                }
            }).catch(function (error) {

                commit('setMsgModal', error.message);
                commit('toggleLoader', false);
                commit('toggleModal', true);
                return false;
            });
        },
        loadMore: function loadMore(_ref2, status) {
            var commit = _ref2.commit,
                state = _ref2.state;


            commit('toggleLoader', true);
            var data = {
                action: 'get_orders'
            };
            state.filters.status = status.status;
            state.filters.wpstatus = status.wpstatus;
            _axios2.default.get('' + ajaxurl, {
                params: Object.assign(data, state.filters)
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('loadMore', response.data);
                    commit('toggleLoader', false);
                }
            }).catch(function (error) {
                commit('setMsgModal', error.message);
                commit('toggleLoader', false);
                commit('toggleModal', true);
                return false;
            });
        },
        addCart: function addCart(_ref3, data) {
            var commit = _ref3.commit;

            commit('toggleLoader', true);
            if (!data) {
                commit('toggleLoader', false);
                return false;
            }
            if (data.id && data.choosen) {
                _axios2.default.post(ajaxurl + '?action=add_order&order_id=' + data.id + '&choosen=' + data.choosen + '&non_commercial=' + data.non_commercial, data).then(function (response) {
                    if (!response.data.success) {
                        commit('setMsgModal', response.data.message);
                        commit('toggleLoader', false);
                        commit('toggleModal', true);
                        return false;
                    }
                    commit('addCart', {
                        id: data.id,
                        order_id: response.data.data.id
                    });
                    commit('toggleLoader', false);
                }).catch(function (error) {
                    commit('setMsgModal', errorMessage);
                    commit('toggleLoader', false);
                    commit('toggleModal', true);
                    return false;
                });
            }
        },
        removeCart: function removeCart(context, data) {
            context.commit('toggleLoader', true);
            _axios2.default.post(ajaxurl + '?action=remove_order&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {

                if (!response.data.success) {
                    context.commit('setMsgModal', response.data.message);
                    context.commit('toggleLoader', false);
                    context.commit('toggleModal', true);
                    return false;
                }

                context.commit('removeCart', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
                context.commit('toggleLoader', false);
            }).catch(function (error) {
                context.commit('setMsgModal', error.message);
                context.commit('toggleLoader', false);
                context.commit('toggleModal', true);
                return false;
            });
        },
        cancelCart: function cancelCart(context, data) {
            context.commit('toggleLoader', true);
            _axios2.default.post(ajaxurl + '?action=cancel_order&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {

                if (!response.data.success) {
                    context.commit('setMsgModal', response.data.message);
                    context.commit('toggleLoader', false);
                    context.commit('toggleModal', true);
                    return false;
                }

                context.commit('cancelCart', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
                context.commit('toggleLoader', false);
            }).catch(function (error) {
                context.commit('setMsgModal', error.message);
                context.commit('toggleLoader', false);
                context.commit('toggleModal', true);
                return false;
            });
        },
        payTicket: function payTicket(context, data) {
            context.commit('toggleLoader', true);
            _axios2.default.post(ajaxurl + '?action=pay_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {

                if (!response.data.success) {
                    context.commit('setMsgModal', response.data.message);
                    context.commit('toggleLoader', false);
                    context.commit('toggleModal', true);
                    return false;
                }

                context.commit('payTicket', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
                context.commit('toggleLoader', false);
            }).catch(function (error) {
                context.commit('setMsgModal', error.message);
                context.commit('toggleLoader', false);
                context.commit('toggleModal', true);
                return false;
            });
        },
        createTicket: function createTicket(_ref4, data) {
            var commit = _ref4.commit;

            commit('toggleLoader', true);
            _axios2.default.post(ajaxurl + '?action=create_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {

                if (!response.data.success) {
                    commit('setMsgModal', response.data.message);
                    commit('toggleLoader', false);
                    commit('toggleModal', true);
                    return false;
                }

                commit('createTicket', data.id);
                commit('toggleLoader', false);
            }).catch(function (error) {
                commit('setMsgModal', error.message);
                commit('toggleLoader', false);
                commit('toggleModal', true);
                return false;
            });
        },
        printTicket: function printTicket(_ref5, data) {
            var commit = _ref5.commit;

            commit('toggleLoader', true);
            _axios2.default.post(ajaxurl + '?action=print_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {

                if (!response.data.success) {
                    commit('setMsgModal', response.data.message);
                    commit('toggleLoader', false);
                    commit('toggleModal', true);
                    return false;
                }

                commit('printTicket', data.id);
                commit('toggleLoader', false);
                window.open(response.data.data.url, '_blank');
            }).catch(function (error) {
                commit('setMsgModal', error.message);
                commit('toggleLoader', false);
                commit('toggleModal', true);
                return false;
            });
        },
        closeModal: function closeModal(_ref6) {
            var commit = _ref6.commit;

            commit('toggleModal', false);
        }
    }
};

exports.default = orders;

/***/ }),
/* 75 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _axios = __webpack_require__(4);

var _axios2 = _interopRequireDefault(_axios);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var balance = {
    namespaced: true,
    state: {
        balance: null
    },
    mutations: {
        setBalance: function setBalance(state, data) {
            state.balance = data;
        }
    },
    getters: {
        getBalance: function getBalance(state) {
            return state.balance;
        }
    },
    actions: {
        setBalance: function setBalance(_ref, data) {
            var commit = _ref.commit;

            _axios2.default.get(ajaxurl + '?action=get_balance', data).then(function (response) {
                commit('setBalance', response.data.balance);
            });
        }
    }
};

exports.default = balance;

/***/ }),
/* 76 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _axios = __webpack_require__(4);

var _axios2 = _interopRequireDefault(_axios);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: [],
        agencies: []
    },
    mutations: {
        setAddress: function setAddress(state, data) {
            state.addresses = data;
        },
        setStore: function setStore(state, data) {
            state.stores = data;
        },
        setAgency: function setAgency(state, data) {
            state.agencies = data;
        }
    },
    getters: {
        getAddress: function getAddress(state) {
            return state.addresses;
        },
        getStores: function getStores(state) {
            return state.stores;
        },
        getAgencies: function getAgencies(state) {
            return state.agencies;
        }
    },
    actions: {
        getAddresses: function getAddresses(_ref, data) {
            var commit = _ref.commit;

            var content = {
                action: 'get_addresses'
            };
            _axios2.default.get('' + ajaxurl, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAddress', response.data.addresses);
                }
            });
        },
        getStores: function getStores(_ref2, data) {
            var commit = _ref2.commit;

            var content = {
                action: 'get_stores'
            };
            _axios2.default.get('' + ajaxurl, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setStore', response.data.stores);
                }
            });
        },
        getAgencies: function getAgencies(_ref3, data) {
            var commit = _ref3.commit;

            data = Object.assign({ action: 'get_agency_jadlog' }, data);

            _axios2.default.get('' + ajaxurl, {
                params: data
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAgency', response.data.agencies);
                }
            });
        },
        setSelectedAddress: function setSelectedAddress(_ref4, data) {
            var commit = _ref4.commit;

            _axios2.default.post(ajaxurl + '?action=set_address&id=' + data).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            });
        },
        setSelectedStore: function setSelectedStore(_ref5, data) {
            var commit = _ref5.commit;

            _axios2.default.post(ajaxurl + '?action=set_store&id=' + data).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            });
        },
        setSelectedAgency: function setSelectedAgency(_ref6, data) {
            var commit = _ref6.commit;

            _axios2.default.post(ajaxurl + '?action=set_agency_jadlog&id=' + data).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            });
        }
    }
};

exports.default = configuration;

/***/ })
],[37]);