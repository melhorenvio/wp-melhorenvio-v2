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
        },
        addCart: ({commit}, data) => {        
            if (!data) {
                return false;
            }

            // TODO separar data da url
            if (data.id && data.choosen) {
                Axios.post(`${ajaxurl}?action=add_order&order_id=${data.id}&choosen=${data.choosen}`, data).then(response => {
                    console.log(response);
                })
            }
            
        }
    }
}

export default orders