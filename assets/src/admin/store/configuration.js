'use strict'
import Axios from 'axios'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
        stores: []
    },
    mutations: {
        setAddress: (state, data) => {
            state.addresses = data;
        },
        setStore: (state, data) => {
            state.stores = data;
        },
    },  
    getters: {
        getAddress: state => state.addresses,
        getStores: state => state.stores
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
        }
    }
}

export default configuration