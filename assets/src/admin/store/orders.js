'use strict'
import Axios from 'axios'

const ajax = (commit, parameters) => {
    Axios.get(`${ajaxurl}`, {
        params: parameters
    }).then(function (response) {
        if (response && response.status === 200) {
            commit(response.data)
        }
    })
}

const orders = {
    namespaced: true,
    state: {
        orders: []
    },
    mutations: {
        retrieveMany: (state, data) => {
            state.orders = data
        },
        loadMore: (state, data) => {
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
        loadMore: ({commit}, filters) => {
            let data = {
                action: 'get_orders',
                limit: (filters.limit) ? filters.limit : 10,
                skip: (filters.skip) ? filters.skip : 0 // per_page
            }

            Axios.get(`${ajaxurl}`, {
                params: data
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('loadMore', response.data)
                }
            })
        }
    }
}

export default orders