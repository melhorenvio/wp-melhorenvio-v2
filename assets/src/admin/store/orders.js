'use strict'
import Axios from 'axios'

const orders = {
    namespaced: true,
    state: {
        orders: [],
        filters: {
            limit: 10,
            skip: 10
        }
    },
    mutations: {
        retrieveMany: (state, data) => {
            state.orders = data
        },
        loadMore: (state, data) => {

            state.filters.skip += data.length

            data.map(item => {
                state.orders.push(item)
            })
        }
    },  
    getters: {
        getOrders: state => state.orders
    },
    actions: {
        retrieveMany: ({commit}) => {

            let data = {
                action: 'get_orders',
                limit: 10,
                skip: 0
            }

            Axios.get(`${ajaxurl}`, {
                params: data
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('retrieveMany', response.data)
                }
            })
        },
        loadMore: ({commit, state}) => {
            let data = {
                action: 'get_orders',
            }

            Axios.get(`${ajaxurl}`, {
                params: Object.assign(data, state.filters)
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('loadMore', response.data)
                }
            })
        }
    }
}

export default orders