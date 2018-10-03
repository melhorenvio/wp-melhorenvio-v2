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



/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Pedidos',
    data: () => {
        return {
            status: 'all',
            wpstatus: 'all'
        };
    },
    computed: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapGetters"])('orders', {
        orders: 'getOrders'
    }), Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapGetters"])('balance', ['getBalance'])),
    methods: _extends({}, Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapActions"])('orders', ['retrieveMany', 'loadMore', 'addCart', 'removeCart', 'cancelCart', 'payTicket', 'createTicket', 'printTicket']), Object(__WEBPACK_IMPORTED_MODULE_0_vuex__["mapActions"])('balance', ['setBalance']), {
        updateInvoice(id, number, key) {
            this.$http.post(`${ajaxurl}?action=insert_invoice_order&id=${id}&number=${number}&key=${key}`).then(response => {}).catch(error => {});
        },
        buttonCartShow(...args) {
            const [choose_method, non_commercial, number, key, status] = args;

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


/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Configuracoes',
    data() {
        return {
            address: null,
            store: null,
            agency: null
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
            if (this.agencies.length > 0) {
                this.agencies.filter(item => {
                    if (item.selected) {
                        this.agency = item.id;
                    }
                });
            }
        },
        address(e) {
            console.log(e);
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


/* harmony default export */ __webpack_exports__["a"] = ({
    name: 'Token',
    data() {
        return {
            token: ''
        };
    },
    methods: {
        getToken() {
            this.$http.get(`${ajaxurl}?action=get_token`).then(response => {
                this.token = response.data.token;
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
/* 36 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _vue = __webpack_require__(2);

var _vue2 = _interopRequireDefault(_vue);

var _vuex = __webpack_require__(5);

var _vuex2 = _interopRequireDefault(_vuex);

var _axios = __webpack_require__(4);

var _axios2 = _interopRequireDefault(_axios);

var _App = __webpack_require__(55);

var _App2 = _interopRequireDefault(_App);

var _router = __webpack_require__(58);

var _router2 = _interopRequireDefault(_router);

var _adminMenuFix = __webpack_require__(71);

var _adminMenuFix2 = _interopRequireDefault(_adminMenuFix);

var _store = __webpack_require__(72);

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
/* 37 */,
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
/* 55 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_App_vue__ = __webpack_require__(17);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_6bc4b6d8_hasScoped_false_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_App_vue__ = __webpack_require__(57);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(56)
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
/* 56 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 57 */
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
/* 58 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _vue = __webpack_require__(2);

var _vue2 = _interopRequireDefault(_vue);

var _vueRouter = __webpack_require__(7);

var _vueRouter2 = _interopRequireDefault(_vueRouter);

var _Home = __webpack_require__(59);

var _Home2 = _interopRequireDefault(_Home);

var _Pedidos = __webpack_require__(62);

var _Pedidos2 = _interopRequireDefault(_Pedidos);

var _Configuracoes = __webpack_require__(65);

var _Configuracoes2 = _interopRequireDefault(_Configuracoes);

var _Token = __webpack_require__(68);

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
/* 59 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Home_vue__ = __webpack_require__(18);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_0ce03f2f_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Home_vue__ = __webpack_require__(61);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(60)
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
/* 60 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 61 */
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
/* 62 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Pedidos_vue__ = __webpack_require__(19);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_05a7e32e_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Pedidos_vue__ = __webpack_require__(64);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(63)
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
/* 63 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 64 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "app-pedidos" }, [
    _c("hr"),
    _vm._v(" "),
    _c("h2", [_vm._v("Meus pedidos")]),
    _vm._v(" "),
    _c("label", [_vm._v("Status Melhor Envio")]),
    _c("br"),
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
        _c("option", { attrs: { value: "all" } }, [_vm._v("Todos")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "printed" } }, [_vm._v("Impresso")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "paid" } }, [_vm._v("Pago")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "pending" } }, [_vm._v("Pendente")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "generated" } }, [_vm._v("Gerado")])
      ]
    ),
    _c("br"),
    _vm._v(" "),
    _c("label", [_vm._v("Status WooCommerce")]),
    _c("br"),
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
        _c("option", { attrs: { value: "wc-pending" } }, [_vm._v("Pendente")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-processing" } }, [
          _vm._v("Processando")
        ]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-on-hold" } }, [_vm._v("Pendente")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-completed" } }, [
          _vm._v("Completo")
        ]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-cancelled" } }, [
          _vm._v("Cancelado")
        ]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-refunded" } }, [_vm._v("Recusado")]),
        _vm._v(" "),
        _c("option", { attrs: { value: "wc-failed" } }, [_vm._v("Falhado")])
      ]
    ),
    _vm._v(" "),
    _c("br"),
    _vm._v(" "),
    _c("br"),
    _vm._v(" "),
    _c("h2", [
      _vm._v("Saldo: R$"),
      _c("span", [_vm._v(_vm._s(_vm.getBalance))])
    ]),
    _vm._v(" "),
    _vm.orders.length > 0
      ? _c(
          "table",
          { attrs: { border: "1" } },
          [
            _vm._m(0),
            _vm._v(" "),
            _vm._l(_vm.orders, function(item, index) {
              return _c("tr", { key: index }, [
                _c("td", [_vm._v(_vm._s(item.id))]),
                _vm._v(" "),
                _c("td", [_vm._v(_vm._s(item.total))]),
                _vm._v(" "),
                _c("td", [
                  _c("p", [
                    _c("b", [
                      _vm._v(
                        _vm._s(item.to.first_name) +
                          " " +
                          _vm._s(item.to.last_name)
                      )
                    ])
                  ]),
                  _vm._v(" "),
                  _c("p", [_vm._v(_vm._s(item.to.email))]),
                  _vm._v(" "),
                  _c("p", [_vm._v(_vm._s(item.to.phone))]),
                  _vm._v(" "),
                  _c("p", [
                    _vm._v(
                      _vm._s(item.to.address_1) +
                        " " +
                        _vm._s(item.to.address_2)
                    )
                  ]),
                  _vm._v(" "),
                  _c("p", [
                    _vm._v(
                      _vm._s(item.to.city) +
                        " / " +
                        _vm._s(item.to.state) +
                        " - " +
                        _vm._s(item.to.postcode)
                    )
                  ])
                ]),
                _vm._v(" "),
                _c(
                  "td",
                  [
                    !item.order_id
                      ? [
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
                                      value: item.cotation.choose_method,
                                      expression: "item.cotation.choose_method"
                                    }
                                  ],
                                  on: {
                                    change: function($event) {
                                      var $$selectedVal = Array.prototype.filter
                                        .call($event.target.options, function(
                                          o
                                        ) {
                                          return o.selected
                                        })
                                        .map(function(o) {
                                          var val =
                                            "_value" in o ? o._value : o.value
                                          return val
                                        })
                                      _vm.$set(
                                        item.cotation,
                                        "choose_method",
                                        $event.target.multiple
                                          ? $$selectedVal
                                          : $$selectedVal[0]
                                      )
                                    }
                                  }
                                },
                                _vm._l(item.cotation, function(option) {
                                  return option.id && option.price
                                    ? _c(
                                        "option",
                                        {
                                          key: option.id,
                                          domProps: { value: option.id }
                                        },
                                        [
                                          _vm._v(
                                            "\n                            " +
                                              _vm._s(option.name) +
                                              " (R$" +
                                              _vm._s(option.price) +
                                              ") \n                        "
                                          )
                                        ]
                                      )
                                    : _vm._e()
                                })
                              )
                            : _vm._e()
                        ]
                      : [_c("span", [_vm._v(_vm._s(item.order_id))])],
                    _vm._v(" "),
                    _c("br")
                  ],
                  2
                ),
                _vm._v(" "),
                _c(
                  "td",
                  [
                    item.cotation.choose_method == 3 ||
                    item.cotation.choose_method == 4
                      ? [
                          _c("input", {
                            directives: [
                              {
                                name: "model",
                                rawName: "v-model",
                                value: item.non_commercial,
                                expression: "item.non_commercial"
                              }
                            ],
                            attrs: { type: "checkbox" },
                            domProps: {
                              checked: Array.isArray(item.non_commercial)
                                ? _vm._i(item.non_commercial, null) > -1
                                : item.non_commercial
                            },
                            on: {
                              change: function($event) {
                                var $$a = item.non_commercial,
                                  $$el = $event.target,
                                  $$c = $$el.checked ? true : false
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
                                          .concat($$a.slice($$i + 1))
                                      )
                                  }
                                } else {
                                  _vm.$set(item, "non_commercial", $$c)
                                }
                              }
                            }
                          }),
                          _vm._v(" "),
                          _c("label", [_vm._v("Usar declaração")]),
                          _vm._v(" "),
                          _c("br"),
                          _vm._v(" "),
                          _c("br")
                        ]
                      : _vm._e(),
                    _vm._v(" "),
                    (item.cotation.choose_method >= 3 &&
                      !item.non_commercial) ||
                    item.cotation.choose_method > 4
                      ? [
                          _c("label", [_vm._v("Nota fiscal")]),
                          _c("br"),
                          _vm._v(" "),
                          _c("input", {
                            directives: [
                              {
                                name: "model",
                                rawName: "v-model",
                                value: item.invoice.number,
                                expression: "item.invoice.number"
                              }
                            ],
                            attrs: { type: "text" },
                            domProps: { value: item.invoice.number },
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
                          _c("label", [_vm._v("Chave da nota fiscal")]),
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
                            domProps: { value: item.invoice.key },
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
                        ]
                      : _vm._e()
                  ],
                  2
                ),
                _vm._v(" "),
                _c("td", [
                  _vm._v(
                    "\n                " +
                      _vm._s(item.status) +
                      "\n            "
                  )
                ]),
                _vm._v(" "),
                _c("td", [
                  _vm.buttonCartShow(
                    item.cotation.choose_method,
                    item.non_commercial,
                    item.invoice.number,
                    item.invoice.key,
                    item.status
                  )
                    ? _c(
                        "button",
                        {
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
                        [_vm._v("Add cart")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  item.status &&
                  item.order_id &&
                  item.id &&
                  item.status != "paid"
                    ? _c(
                        "button",
                        {
                          on: {
                            click: function($event) {
                              _vm.removeCart({
                                id: item.id,
                                order_id: item.order_id
                              })
                            }
                          }
                        },
                        [_vm._v("Remove cart")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  item.status == "paid" && item.order_id && item.id
                    ? _c(
                        "button",
                        {
                          on: {
                            click: function($event) {
                              _vm.cancelCart({
                                id: item.id,
                                order_id: item.order_id
                              })
                            }
                          }
                        },
                        [_vm._v("Cancel")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  item.status &&
                  item.order_id &&
                  item.id &&
                  item.status == "pending"
                    ? _c(
                        "button",
                        {
                          on: {
                            click: function($event) {
                              _vm.payTicket({
                                id: item.id,
                                order_id: item.order_id
                              })
                            }
                          }
                        },
                        [_vm._v("Pay")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  item.status && item.status == "paid" && item.order_id
                    ? _c(
                        "button",
                        {
                          on: {
                            click: function($event) {
                              _vm.createTicket({
                                id: item.id,
                                order_id: item.order_id
                              })
                            }
                          }
                        },
                        [_vm._v("Create ticket")]
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  item.status &&
                  (item.status == "generated" || item.status == "printed")
                    ? _c(
                        "button",
                        {
                          on: {
                            click: function($event) {
                              _vm.printTicket({
                                id: item.id,
                                order_id: item.order_id
                              })
                            }
                          }
                        },
                        [_vm._v("Print ticket")]
                      )
                    : _vm._e()
                ])
              ])
            })
          ],
          2
        )
      : _c("div", [_c("p", [_vm._v("Nenhum registro encontrado")])]),
    _vm._v(" "),
    _c(
      "button",
      {
        on: {
          click: function($event) {
            _vm.loadMore({ status: _vm.status, wpstatus: _vm.wpstatus })
          }
        }
      },
      [_vm._v("Carregar mais")]
    )
  ])
}
var staticRenderFns = [
  function() {
    var _vm = this
    var _h = _vm.$createElement
    var _c = _vm._self._c || _h
    return _c("tr", [
      _c("th", [_vm._v("#")]),
      _vm._v(" "),
      _c("th", [_vm._v("Valor pedido")]),
      _vm._v(" "),
      _c("th", [_vm._v("Cliente")]),
      _vm._v(" "),
      _c("th", [_vm._v("Cotação")]),
      _vm._v(" "),
      _c("th", [_vm._v("Documentos")]),
      _vm._v(" "),
      _c("th", [_vm._v("Status")]),
      _vm._v(" "),
      _c("th", [_vm._v("Ações")])
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
/* 65 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Configuracoes_vue__ = __webpack_require__(20);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_260cb748_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Configuracoes_vue__ = __webpack_require__(67);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(66)
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
/* 66 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 67 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "app-configuracoes" },
    [
      _c("h1", [_vm._v("Minhas configurações")]),
      _vm._v(" "),
      _c("label", [_vm._v("Meus endereços")]),
      _c("br"),
      _vm._v(" "),
      _vm._l(_vm.addresses, function(option) {
        return _c("div", { key: option.id, attrs: { value: option.id } }, [
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
              change: function($event) {
                _vm.address = option.id
              }
            }
          }),
          _vm._v(" "),
          _c("label", { attrs: { for: option.id } }, [
            _c("b", [_vm._v(_vm._s(option.label))]),
            _vm._v(
              " (" +
                _vm._s(option.address) +
                " " +
                _vm._s(option.number) +
                ", " +
                _vm._s(option.district) +
                " - " +
                _vm._s(option.city) +
                "/" +
                _vm._s(option.state) +
                " )"
            )
          ]),
          _vm._v(" "),
          _c("br")
        ])
      }),
      _vm._v(" "),
      _c("br"),
      _c("br"),
      _vm._v(" "),
      _c("label", [_vm._v("Minhas lojas")]),
      _c("br"),
      _vm._v(" "),
      _vm._l(_vm.stores, function(option) {
        return _c("div", { key: option.id, attrs: { value: option.id } }, [
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
          _c("label", { attrs: { for: option.id } }, [
            _c("b", [_vm._v(_vm._s(option.name))]),
            _vm._v(
              " (Documento: " +
                _vm._s(option.document) +
                " - Registro estatual: " +
                _vm._s(option.state_register) +
                ")"
            )
          ]),
          _vm._v(" "),
          _c("br")
        ])
      }),
      _vm._v(" "),
      _c("br"),
      _c("br"),
      _vm._v(" "),
      _c("label", [_vm._v("Agência Jadlog para postagem")]),
      _c("br"),
      _vm._v(" "),
      _vm._l(_vm.agencies, function(option) {
        return _c("div", { key: option.id, attrs: { value: option.id } }, [
          _c("input", {
            directives: [
              {
                name: "model",
                rawName: "v-model",
                value: _vm.agency,
                expression: "agency"
              }
            ],
            attrs: { type: "radio", id: option.id },
            domProps: {
              value: option.id,
              checked: _vm._q(_vm.agency, option.id)
            },
            on: {
              change: function($event) {
                _vm.agency = option.id
              }
            }
          }),
          _vm._v(" "),
          _c("label", { attrs: { for: option.id } }, [
            _c("b", [_vm._v(_vm._s(option.company_name))]),
            _vm._v(" (" + _vm._s(option.name) + ")")
          ]),
          _vm._v(" "),
          _c("br")
        ])
      }),
      _vm._v(" "),
      _c("br"),
      _c("br"),
      _vm._v(" "),
      _c("button", { on: { click: _vm.updateConfig } }, [_vm._v("salvar")])
    ],
    2
  )
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
/* 68 */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__babel_loader_node_modules_vue_loader_lib_selector_type_script_index_0_Token_vue__ = __webpack_require__(21);
/* empty harmony namespace reexport */
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__node_modules_vue_loader_lib_template_compiler_index_id_data_v_1bf58fd9_hasScoped_true_buble_transforms_node_modules_vue_loader_lib_selector_type_template_index_0_Token_vue__ = __webpack_require__(70);
var disposed = false
function injectStyle (ssrContext) {
  if (disposed) return
  __webpack_require__(69)
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
/* 69 */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),
/* 70 */
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
    _c("p", { staticStyle: { "white-space": "pre-line" } }),
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
      attrs: { placeholder: "Token" },
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
    _c(
      "button",
      {
        staticClass: "button is-danger",
        on: {
          click: function($event) {
            _vm.saveToken()
          }
        }
      },
      [_vm._v("Salvar")]
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
    require("vue-hot-reload-api")      .rerender("data-v-1bf58fd9", esExports)
  }
}

/***/ }),
/* 71 */
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
/* 72 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


Object.defineProperty(exports, "__esModule", {
    value: true
});

var _vue = __webpack_require__(2);

var _vue2 = _interopRequireDefault(_vue);

var _vuex = __webpack_require__(5);

var _vuex2 = _interopRequireDefault(_vuex);

var _orders = __webpack_require__(73);

var _orders2 = _interopRequireDefault(_orders);

var _balance = __webpack_require__(74);

var _balance2 = _interopRequireDefault(_balance);

var _configuration = __webpack_require__(75);

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
/* 73 */
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
        }
    },
    getters: {
        getOrders: function getOrders(state) {
            return state.orders;
        }
    },
    actions: {
        retrieveMany: function retrieveMany(_ref, data) {
            var commit = _ref.commit;

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
                }
            });
        },
        loadMore: function loadMore(_ref2, status) {
            var commit = _ref2.commit,
                state = _ref2.state;

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
                }
            });
        },
        addCart: function addCart(_ref3, data) {
            var commit = _ref3.commit;

            if (!data) {
                return false;
            }

            if (data.id && data.choosen) {
                _axios2.default.post(ajaxurl + '?action=add_order&order_id=' + data.id + '&choosen=' + data.choosen + '&non_commercial=' + data.non_commercial, data).then(function (response) {
                    commit('addCart', {
                        id: data.id,
                        order_id: response.data.data.id
                    });
                });
            }
        },
        removeCart: function removeCart(context, data) {
            _axios2.default.post(ajaxurl + '?action=remove_order&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {
                context.commit('removeCart', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
            });
        },
        cancelCart: function cancelCart(context, data) {
            _axios2.default.post(ajaxurl + '?action=cancel_order&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {
                context.commit('cancelCart', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
            });
        },
        payTicket: function payTicket(context, data) {
            _axios2.default.post(ajaxurl + '?action=pay_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {
                context.commit('payTicket', data.id);
                context.dispatch('balance/setBalance', null, { root: true });
            });
        },
        createTicket: function createTicket(_ref4, data) {
            var commit = _ref4.commit;

            _axios2.default.post(ajaxurl + '?action=create_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {
                commit('createTicket', data.id);
            });
        },
        printTicket: function printTicket(_ref5, data) {
            var commit = _ref5.commit;

            _axios2.default.post(ajaxurl + '?action=print_ticket&id=' + data.id + '&order_id=' + data.order_id, data).then(function (response) {
                commit('printTicket', data.id);
                console.log(response);

                window.open(response.data.data.url, '_blank');
            });
        }
    }
};

exports.default = orders;

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
/* 75 */
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

            var content = {
                action: 'get_agency_jadlog'
            };
            _axios2.default.get('' + ajaxurl, {
                params: content
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
],[36]);