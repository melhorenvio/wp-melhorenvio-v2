'use strict'
import Axios from 'axios'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: [],
        agencies: []
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
        }
    },  
    getters: {
        getAddress: state => state.addresses,
        getStores: state => state.stores,
        getAgencies: state => state.agencies
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
            let content = {
                action: 'get_agency_jadlog',
            }
            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAgency', response.data.agencies)
                }
            })
        },
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
        }
    }
}

export default configuration