'use strict'

import Axios from 'axios'
import _ from 'lodash'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: [],
        agencies: [],
        styleCalculator: [],
        path_plugins: null,
        show_calculator: false,
        where_calculator: 'woocommerce_after_add_to_cart_form',
        methods_shipments: [],
        show_load: true,
        configs: []
    },
    mutations: {
        setStyleCalculator: (state, data) => {
            state.styleCalculator = data;
        },
        setAddress: (state, data) => {
            state.addresses = data
        },
        setStore: (state, data) => {
            state.stores = data
        },
        setAgency: (state, data) => {
            state.agencies = data
        },
        setPathPlugins: (state, data) => {
            state.path_plugins = data;
        },
        setConfigs: (state, data) => {
            state.configs = data
        },
        setShowCalculator: (state, data) => {
            state.show_calculator = data
        },
        setMethodShipments: (state, data) => {
            state.methods_shipments = data
        },
        setWhereCalculator: (state, data) => {
            state.where_calculator = data
        },
        toggleLoader: (state, data) => {
            state.show_load = data
        }
    },  
    getters: {
        getAddress: state => state.addresses,
        getStores: state => state.stores,
        getAgencies: state => state.agencies,
        getStyleCalculator: state => state.styleCalculator,
        getPathPlugins: state => state.path_plugins,
        getShowCalculator: state => state.show_calculator,
        showLoad: state => state.show_load,
        getMethodsShipments: state => state.methods_shipments,
        getWhereCalculator: state => state.where_calculator,
        getConfigs: state => state.configs
    },
    actions: {
        getConfigs: ({commit}, data) => {
            let content = {
                action: 'get_configuracoes'
            }
            return new Promise((resolve, reject) => {
                Axios.get(`${ajaxurl}`, {
                    params: content
                }).then(function (response) {
                    if (response && response.status === 200) {
                        if (response.data.addresses && !_.isEmpty(response.data.addresses)) {
                            commit('setAddress', response.data.addresses);
                        }
                        if (response.data.agencies && !_.isNull(response.data.agencies)) {
                            commit('setAgency', response.data.agencies);
                        }
                        if (response.data.stores && !_.isEmpty(response.data.stores)) {
                            commit('setStore', response.data.stores)
                        }    
                        commit('setStyleCalculator', response.data.style_calculator)
                        commit('setPathPlugins', response.data.path_plugins)
                        commit('setShowCalculator', response.data.calculator)
                        commit('setMethodShipments', response.data.metodos)
                        commit('setWhereCalculator', response.data.where_calculator)
                        resolve(true)
                    }
                }).catch((error) => {
                    console.log(error)
                })
            })
        },
        getAgencies: ({commit}, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_jadlog&city=${data.city}&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgency', response.data.agencies);
                }
            })
        },
        setSelectedAddress: ({commit}, data) => {
            return new Promise((resolve, reject) => {
                Axios.post(`${ajaxurl}?action=set_address&id=${data}`).then(function (response) {
                    if (response && response.status === 200) {
                        resolve(true)
                    }
                })
            })
        },
        setSelectedStore: ({commit}, data) => {
            return new Promise((resolve, reject) => {
                Axios.post(`${ajaxurl}?action=set_store&id=${data}`).then(function (response) {
                    if (response && response.status === 200) {
                        resolve(true)
                    }
                })
            });
        },
        setSelectedAgency: ({commit}, data) => {
            return new Promise((resolve, reject) => {
                Axios.post(`${ajaxurl}?action=set_agency_jadlog&id=${data}`).then(function (response) {
                    if (response && response.status === 200) {
                        resolve(true)
                    }
                })
            });
        },
        setShowCalculator: ({commit}, data) => {
            return new Promise((resolve, reject) => {
                Axios.post(`${ajaxurl}?action=set_calculator_show&data=${data}`).then(function (response) {
                    if (response && response.status === 200) {
                        commit('setShowCalculator', data);
                        resolve(true)
                    }
                })
            });
        },
        setLoader: ({commit}, data) => {
            commit('toggleLoader', data)
        },
        setAgencies: ({commit}, data) => {
            commit('setAgency', data)
        }
    }
}

export default configuration