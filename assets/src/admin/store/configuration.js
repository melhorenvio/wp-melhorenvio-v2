'use strict'

import Axios from 'axios'
import _ from 'lodash'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: [],
        agencies: [],
        allAgencies: [],
        styleCalculator: [],
        path_plugins: null,
        show_calculator: false,
        show_all_jadlog_agencies: false,
        options_calculator: {
            ar: false,
            mp: false,
            vs: true
        },
        where_calculator: 'woocommerce_after_add_to_cart_form',
        agencySelected: null,
        methods_shipments: [],
        show_load: true,
        configs: [],
        services_codes: []
    },
    mutations: {
        toggleLoader: (state, data) => {
            state.show_load = data
        },
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
        setAgencySelected: (state, data) => { 
            state.agencySelected = data
        },
        setAllAgency: (state, data) => {
            state.allAgencies = data
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
        setShowAllJadlogAgencies: (state, data) => {
            state.show_all_jadlog_agencies = data
        },
        setMethodShipments: (state, data) => {
            state.methods_shipments = data
        },
        setWhereCalculator: (state, data) => {
            state.where_calculator = data
        },
        setOptionsCalculator: (state, data) => {
            state.options_calculator = data;
        },
        setServicesCodes: (state, data) => {
            state.services_codes = data;
        }
    },  
    getters: {
        getAddress: state => state.addresses,
        getStores: state => state.stores,
        getAgencies: state => state.agencies,
        getAllAgencies: state => state.allAgencies,
        getAgencySelected: state => state.agencySelected,
        getStyleCalculator: state => state.styleCalculator,
        getPathPlugins: state => state.path_plugins,
        getShowCalculator: state => state.show_calculator, 
        getShowAllJadlogAgencies: state => state.show_all_jadlog_agencies,
        showLoad: state => state.show_load,
        getMethodsShipments: state => state.methods_shipments,
        getWhereCalculator: state => state.where_calculator,
        getConfigs: state => state.configs,
        getOptionsCalculator: state => state.options_calculator,
        getServicesCodes: state => state.services_codes
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
                            commit('setAllAgency', response.data.allAgencies);
                        }
                        if (response.data.stores && !_.isEmpty(response.data.stores)) {
                            commit('setStore', response.data.stores)
                        }    
                        commit('setAgencySelected', response.data.agencySelected)
                        commit('setStyleCalculator', response.data.style_calculator)  
                        commit('setPathPlugins', response.data.path_plugins)
                        commit('setShowCalculator', response.data.calculator)
                        commit('setShowAllJadlogAgencies', response.data.all_agencies_jadlog)
                        commit('setMethodShipments', response.data.metodos)
                        commit('setWhereCalculator', response.data.where_calculator)
                        commit('setOptionsCalculator', response.data.options_calculator)
                        commit('setServicesCodes', response.data.services_codes)
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
        getAllAgencies: ({commit}, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_jadlog&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAllAgencies', response.data.agencies);
                }
            })
        },
        saveAll: ({commit}, data) => {

            return new Promise((resolve, reject) => {

                const form = new FormData();

                if (data.address != null) {
                    form.append('address', data.address);
                }

                if (data.store != null) {
                    form.append('store', data.store);
                }

                if (data.agency != null) {
                    form.append('agency', data.agency);
                }

                if (data.show_calculator != null) {
                    form.append('show_calculator', data.show_calculator);
                }

                if (data.show_all_agencies_jadlog != null) {
                    form.append('show_all_agencies_jadlog', data.show_all_agencies_jadlog);
                }

                if (data.methods_shipments != null) {

                    data.methods_shipments.forEach(function(item, index) {

                        form.append('methods_shipments[' + index +'][id]', item.code);
                        form.append('methods_shipments[' + index +'][tax]', item.tax);
                        form.append('methods_shipments[' + index +'][time]', item.time);
                        form.append('methods_shipments[' + index +'][name]', item.name);
                        form.append('methods_shipments[' + index +'][perc]', item.perc);
                        form.append('methods_shipments[' + index +'][ar]', item.ar);
                        form.append('methods_shipments[' + index +'][mp]', item.mp);
                    });
                }

                if (data.where_calculator != null) {
                    form.append('where_calculator', data.where_calculator);
                }

                if (data.path_plugins != null) {
                    form.append('path_plugins', data.path_plugins);
                }

                if (!is_null(data.options_calculator)) {
                    form.append('options_calculator[ar]', data.options_calculator.ar);
                    form.append('options_calculator[mp]', data.options_calculator.mp);
                    form.append('options_calculator[vs]', data.options_calculator.vs);
                }

                Axios.post(`${ajaxurl}?action=save_configuracoes`, form).then(function (response) {
                    if (response && response.status === 200) {
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
        },
        setAllAgencies: ({commit}, data) => {
            commit('setAllAgency', data)
        },
    }
}

export default configuration