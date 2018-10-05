'use strict'
import Axios from 'axios'

const orders = {
    namespaced: true,
    state: {
        orders: [],
        show_loader: true,
        show_modal: false,
        msg_modal: '',
        filters: {
            limit: 10,
            skip: 10,
            status: 'all',
            wpstatus: 'all'
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
            delete order.content.order_id
            state.orders.splice(order.position, 1, order.content)
        },
        cancelCart: (state, data) => {
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
        addCart: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data.id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = 'pending'
            order.content.order_id = data.order_id
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
        },
        toggleLoader: (state, data) => {
            state.show_loader = data;
        },
        toggleModal: (state, data) => {
            state.show_modal = data;
        },
        setMsgModal: (state, data) => {
            state.msg_modal = data;
        }
    },  
    getters: {
        getOrders: state => state.orders,
        toggleLoader: state => state.show_loader,
        setMsgModal: state => state.msg_modal,
        showModal: state => state.show_modal

    },
    actions: {
        retrieveMany: ({commit}, data) => {
            let content = {
                action: 'get_orders',
                limit: 10,
                skip: 0,
                status: (data.status) ? data.status : null,
                wpstatus: (data.wpstatus) ? data.wpstatus : null
            }

            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('retrieveMany', response.data)
                    commit('toggleLoader', false)
                }
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            })
        },
        loadMore: ({commit, state}, status) => {

            commit('toggleLoader', true)
            let data = {
                action: 'get_orders',
            }
            state.filters.status = status.status
            state.filters.wpstatus = status.wpstatus
            Axios.get(`${ajaxurl}`, {
                params: Object.assign(data, state.filters)
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('loadMore', response.data)
                    commit('toggleLoader', false)
                }
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            })
        },
        addCart: ({commit}, data) => {  
            commit('toggleLoader', true)
            if (!data) {
                commit('toggleLoader', false)
                return false;
            }
            if (data.id && data.choosen) {
                Axios.post(`${ajaxurl}?action=add_order&order_id=${data.id}&choosen=${data.choosen}&non_commercial=${data.non_commercial}`, data).then(response => {
                    if(!response.data.success) {
                        commit('setMsgModal', response.data.message)
                        commit('toggleLoader', false)
                        commit('toggleModal', true)
                        return false
                    }
                    commit('addCart',{
                        id: data.id,
                        order_id: response.data.data.id
                    })
                    commit('toggleLoader', false)
                }).catch(error => {
                    commit('setMsgModal', error.message)
                    commit('toggleLoader', false)
                    commit('toggleModal', true)
                    return false
                })
            }
        },
        removeCart: (context, data) => {    
            context.commit('toggleLoader', true) 
            Axios.post(`${ajaxurl}?action=remove_order&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if(!response.data.success) {
                    context.commit('setMsgModal', response.data.message)
                    context.commit('toggleLoader', false)
                    context.commit('toggleModal', true)
                    return false
                }

                context.commit('removeCart', data.id)
                context.dispatch('balance/setBalance', null, {root: true})
                context.commit('toggleLoader', false)
            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })
        },
        cancelCart: (context, data) => {   
            context.commit('toggleLoader', true)      
            Axios.post(`${ajaxurl}?action=cancel_order&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if(!response.data.success) {
                    context.commit('setMsgModal', response.data.message)
                    context.commit('toggleLoader', false)
                    context.commit('toggleModal', true)
                    return false
                }

                context.commit('cancelCart', data.id)
                context.dispatch('balance/setBalance', null, {root: true})
                context.commit('toggleLoader', false) 
            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })
        },
        payTicket: (context, data) => {    
            context.commit('toggleLoader', true)     
            Axios.post(`${ajaxurl}?action=pay_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if(!response.data.success) {
                    context.commit('setMsgModal', response.data.message)
                    context.commit('toggleLoader', false)
                    context.commit('toggleModal', true)
                    return false
                }

                context.commit('payTicket', data.id)
                context.dispatch('balance/setBalance', null, {root: true})
                context.commit('toggleLoader', false) 
            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })
        },
        createTicket: ({commit}, data) => {   
            commit('toggleLoader', true)     
            Axios.post(`${ajaxurl}?action=create_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if(!response.data.success) {
                    commit('setMsgModal', response.data.message)
                    commit('toggleLoader', false)
                    commit('toggleModal', true)
                    return false
                }

                commit('createTicket', data.id)
                commit('toggleLoader', false)
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            })
        },
        printTicket: ({commit}, data) => {  
            commit('toggleLoader', true)      
            Axios.post(`${ajaxurl}?action=print_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if(!response.data.success) {
                    commit('setMsgModal', response.data.message)
                    commit('toggleLoader', false)
                    commit('toggleModal', true)
                    return false
                }

                commit('printTicket', data.id)
                commit('toggleLoader', false)
                window.open(response.data.data.url,'_blank');
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            })
        },
        closeModal: ({commit}) => {
            commit('toggleModal', false)
        }
    }
}

export default orders