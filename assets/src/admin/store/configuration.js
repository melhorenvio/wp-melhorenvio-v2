'use strict'
import Axios from 'axios'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: [],
        agencies: [],
        // calculator: true,
        show_load: true
    },
    mutations: {
        setAddress: (state, data) => {
            state.addresses = data;
        },
        setStore: (state, data) => {
            state.stores = data;
        },
        setAgency: (state, data) => {
            state.agencies = data;
        },
        toggleLoader: (state, data) => {
            state.show_load = data
        },
        // setCalculator: (state, data) => {
        //     state.calculator = data
        // }
    },  
    getters: {
        getAddress: state => state.addresses,
        getStores: state => state.stores,
        getAgencies: state => state.agencies,
        // getCalculator: state => state.calculator,
        showLoad: state => state.show_load
    },
    actions: {
        getAddresses: ({commit}, data) => {
            let content = {
                action: 'get_addresses',
            }
            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAddress', response.data.addresses)
                }
            })
        },
        getStores: ({commit}, data) => {
            let content = {
                action: 'get_stores',
            }
            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setStore', response.data.stores)
                }
            })
        },
        getAgencies: ({commit}, data) => {
            commit('toggleLoader', true)
            data = Object.assign({action: 'get_agency_jadlog'}, data)
            Axios.get(`${ajaxurl}`, {
                params: data
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAgency', response.data.agencies)
                    commit('toggleLoader', false)
                }
            })
        },
        // getCalculatorShow: ({commit}, data) => {
        //     commit('toggleLoader', true)
        //     data = Object.assign({action: 'get_calculator_show'}, data)
        //     Axios.get(`${ajaxurl}`, {
        //         params: data
        //     }).then(function (response) {
        //         if (response && response.status === 200) {
        //             commit('setCalculator', response.data)
        //         }
        //     })
        // },
        setSelectedAddress: ({commit}, data) => {
            Axios.post(`${ajaxurl}?action=set_address&id=${data}`).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            })
        },
        setSelectedStore: ({commit}, data) => {
            Axios.post(`${ajaxurl}?action=set_store&id=${data}`).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            })
        },
        setSelectedAgency: ({commit}, data) => {
            Axios.post(`${ajaxurl}?action=set_agency_jadlog&id=${data}`).then(function (response) {
                if (response && response.status === 200) {
                    // commit('setAddress', response.data.id)
                }
            })
        },
        // setCalculatorShow: ({commit}, data) => {
        //     Axios.post(`${ajaxurl}?action=set_calculator_show&data=${data}`).then(function (response) {
        //         if (response && response.status === 200) {
        //             // commit('setAddress', response.data.id)
        //         }
        //     })
        // }
    }
}

export default configuration