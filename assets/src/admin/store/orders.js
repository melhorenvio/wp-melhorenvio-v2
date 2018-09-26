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
        },
        removeCart: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            delete order.content.status
            state.orders.splice(order.position, 1, order.content)
        },
        addCart: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = 'pending'
            state.orders.splice(order.position, 1, order.content)
        },
        payTicket: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = 'paid'
            state.orders.splice(order.position, 1, order.content)
        },
        createTicket: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = 'generated'
            state.orders.splice(order.position, 1, order.content)
        },
        printTicket: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = 'printed'
            state.orders.splice(order.position, 1, order.content)
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
            if (data.id && data.choosen) {
                Axios.post(`${ajaxurl}?action=add_order&order_id=${data.id}&choosen=${data.choosen}`, data).then(response => {
                    commit('addCart', data.id)
                })
            }
        },
        removeCart: ({commit}, data) => {        
            Axios.post(`${ajaxurl}?action=remove_order&id=${data.id}&order_id=${data.order_id}`, data).then(response => {
                commit('removeCart', data.id)
            })
        },
        payTicket: ({commit}, data) => {        
            Axios.post(`${ajaxurl}?action=pay_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {
                commit('payTicket', data.id)
            })
        },
        createTicket: ({commit}, data) => {        
            Axios.post(`${ajaxurl}?action=create_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {
                commit('createTicket', data.id)
            })
        },
        printTicket: ({commit}, data) => {        
            Axios.post(`${ajaxurl}?action=print_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {
                commit('printTicket', data.id)
                window.open(response.data.data.url,'_blank');
            })
        }
    }
}

export default orders