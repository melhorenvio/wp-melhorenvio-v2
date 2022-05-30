'use strict'
import Axios from 'axios'
import StatusMelhorEnvio from '../utils/status'

const orders = {
    namespaced: true,
    state: {
        orders: [],
        status_woocommerce: [],
        show_loader: true,
        show_modal: false,
        show_more: true,
        msg_modal: '',
        filters: {
            limit: 5,
            skip: 5,
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
            delete order.content.service_id
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
            order.content.status = StatusMelhorEnvio.STATUS_CANCELED
            state.orders.splice(order.position, 1, order.content)
        },
        addCartSimple: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data.id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content.status = StatusMelhorEnvio.STATUS_PENDING
            order.content.order_id = data.order_id
            order.content.protocol = data.protocol
            order.content.service_id = data.service_id
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
            order.content.status = StatusMelhorEnvio.STATUS_RELEASED
            order.content.order_id = data.order_id
            order.content.protocol = data.protocol
            order.content.service_id = data.service_id
            state.orders.splice(order.position, 1, order.content)
        },
        refreshCotation: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id == data.id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            order.content = data
            order.content.status = null;
            order.content.protocol = null;
            order.content.order_id = null;
            state.orders.splice(order.position, 1, order.content)
        },
        updateQuotation: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id == data.order_id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })

            if (order) {
                order.content.cotation = data.quotations
                state.orders.splice(order.position, 1, order.content)
            }
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
            order.content.status = StatusMelhorEnvio.STATUS_RELEASED
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
            order.content.status = StatusMelhorEnvio.STATUS_GENERATED
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
            order.content.status = StatusMelhorEnvio.STATUS_RELEASED
            state.orders.splice(order.position, 1, order.content)
        },
        setStatusWc: (state, data) => {
            state.status_woocommerce = data
        },
        toggleLoader: (state, data) => {
            state.show_loader = data;
        },
        toggleModal: (state, data) => {
            if (data == false) {
                state.msg_modal = null;
            }
            state.show_modal = data;
        },
        toggleMore: (state, data) => {
            state.show_more = data;
        },
        setMsgModal: (state, data) => {
            state.msg_modal = data;
        },
        updateInvoice: (state, data) => {
            let order
            state.orders.find((item, index) => {
                if (item.id === data.id) {
                    order = {
                        position: index,
                        content: JSON.parse(JSON.stringify(item))
                    }
                }
            })
            state.orders.splice(order.position, 1, order.content)
        }
    },
    getters: {
        getOrders: state => state.orders,
        toggleLoader: state => state.show_loader,
        setMsgModal: state => state.msg_modal,
        showModal: state => state.show_modal,
        showMore: state => state.show_more,
        statusWooCommerce: state => state.status_woocommerce

    },
    actions: {
        showErrorAlert: ({ commit }, data) => {
            commit('setMsgModal', data)
            commit('toggleModal', true)
        },
        retrieveMany: ({ commit }, data) => {
            commit('toggleLoader', true)
            let content = {
                action: 'get_orders',
                limit: 5,
                skip: 0,
                status: (data.status) ? data.status : null,
                wpstatus: (data.wpstatus) ? data.wpstatus : null,
                _wpnonce: wpApiSettingsMelhorEnvio.nonce_orders
            }

            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('retrieveMany', response.data.orders)
                    commit('toggleMore', response.data.load)
                    commit('toggleLoader', false)
                }
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                commit('toggleMore', true)
                return false
            })
        },
        printMultiples: ({ commit, state }, dataPrint) => {

            commit('toggleLoader', true);
            let data = {
                action: 'buy_click',
                ids: dataPrint.orderSelecteds
            }
            Axios.get(`${ajaxurl}`, {
                params: Object.assign(data, state.filters)
            }).then(function (response) {
                commit('toggleLoader', false)
                window.open(response.data.url, '_blank');

            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                commit('toggleMore', true)
                return false
            })
        },
        loadMore: ({ commit, state }, status) => {

            commit('toggleLoader', true)
            let data = {
                action: 'get_orders',
                _wpnonce: wpApiSettingsMelhorEnvio.nonce_orders
            }
            state.filters.status = status.status
            state.filters.wpstatus = status.wpstatus
            Axios.get(`${ajaxurl}`, {
                params: Object.assign(data, state.filters)
            }).then(function (response) {

                if (response && response.status === 200) {
                    commit('loadMore', response.data.orders)
                    commit('toggleMore', response.data.load)
                    commit('toggleLoader', false)
                    return true;
                }

                if (response && response.status === 500) {
                    commit('toggleMore', false);
                    commit('toggleLoader', false)
                    return false;
                }

            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                commit('toggleMore', true)
                return false
            })
        },
        insertInvoice: ({ commit }, data) => {
            commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=insert_invoice_order&id=${data.id}&number=${data.invoice.number}&key=${data.invoice.key}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`).then(response => {
                commit('updateInvoice', data);
                commit('setMsgModal', response.data.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return true
            }).catch(error => {
                commit('setMsgModal', error.message)
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            })
        },
        initLoader: ({ commit }) => {
            commit('toggleLoader', true)
        },
        stopLoader: ({ commit }) => {
            commit('toggleLoader', false)
        },
        setMessageModal: ({ commit }, msg) => {
            commit('setMsgModal', msg)
            commit('toggleModal', true)
        },
        addCartSimple: ({ commit }, data) => {
            return new Promise((resolve, reject) => {
                if (!data) {
                    commit('toggleLoader', false)
                    reject();
                }
                if (data.id && data.service_id) {
                    Axios.post(`${ajaxurl}?action=add_cart&post_id=${data.id}&service=${data.service_id}&non_commercial=${data.non_commercial}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`, data)
                        .then(response => {
                            commit('toggleLoader', false)
                            commit('addCartSimple', {
                                id: data.id,
                                order_id: response.data.order_id,
                                service_id: data.service_id
                            })
                            resolve(response.data);
                        }).catch((error) => {
                            reject(error);
                        });
                }
            })
        },
        addCart: ({ commit }, data) => {
            return new Promise((resolve, reject) => {
                if (!data) {
                    commit('toggleLoader', false)
                    reject();
                    return false;
                }

                if (data.id && data.service_id) {

                    Axios.post(`${ajaxurl}?action=add_order&post_id=${data.id}&service_id=${data.service_id}&non_commercial=${data.non_commercial}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`, data)
                        .then(response => {
                            commit('toggleLoader', false)
                            if (!response.data.success) {
                                reject(response.data);
                            }
                            commit('addCart', {
                                id: data.id,
                                order_id: response.data.data.order_id,
                                service_id: data.service_id
                            })
                            resolve(response.data);
                        }).catch((error) => {
                            reject(error);
                        });
                }
            })
        },
        refreshCotation: (context, data) => {
            context.commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=update_order&id=${data.id}&order_id=${data.order_id}`).then(response => {
                context.commit('toggleLoader', false)
                context.commit('setMsgModal', 'Item #' + data.id + ' atualizado')
                context.commit('toggleModal', true)
                context.commit('refreshCotation', response.data)
            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })

        },
        removeCart: (context, data) => {
            context.commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=remove_order&id=${data.id}&order_id=${data.order_id}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`, data).then(response => {
                if (!response.data.success) {
                    context.commit('setMsgModal', response.data.message)
                    context.commit('toggleLoader', false)
                    context.commit('toggleModal', true)
                    return false
                }

                context.commit('removeCart', data.id)
                context.dispatch('balance/setBalance', null, { root: true })
                context.commit('toggleLoader', false)

            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })
        },
        updateQuotation: (context, data) => {
            context.commit('updateQuotation', data)
        },
        cancelOrder: (context, data) => {
            context.commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=cancel_order&post_id=${data.post_id}&order_id=${data.order_id}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`, data).then(response => {
                context.commit('setMsgModal', response.data.message)
                context.commit('toggleModal', true)
                context.commit('cancelCart', data.post_id)
                context.dispatch('balance/setBalance', null, { root: true })
                context.commit('toggleLoader', false)
            }).catch(error => {
                context.commit('setMsgModal', 'Etiqueta não pode ser cancelada.')
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
            })
        },
        payTicket: (context, data) => {
            context.commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=pay_ticket&id=${data.id}&order_id=${data.order_id}`, data).then(response => {

                if (!response.data.success) {
                    context.commit('setMsgModal', response.data.data)
                    context.commit('toggleLoader', false)
                    context.commit('toggleModal', true)
                    return false
                }
                context.commit('payTicket', data.id)
                context.dispatch('balance/setBalance', null, { root: true })
                context.commit('setMsgModal', 'Item #' + data.id + ' pago com sucesso')
                context.commit('toggleModal', true)
                context.commit('toggleLoader', false)
            }).catch(error => {
                context.commit('setMsgModal', error.message)
                context.commit('toggleLoader', false)
                context.commit('toggleModal', true)
                return false
            })
        },
        createTicket: ({ commit }, data) => {
            commit('toggleLoader', true)
            Axios.post(`${ajaxurl}?action=print_ticket&id=${data.id}&order_id=${data.order_id}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`, data).then(response => {
                if (!response.data.success) {
                    commit('setMsgModal', 'Etiquetas geradas!')
                    commit('toggleLoader', false)
                    commit('toggleModal', true)
                    return false
                }
                commit('printTicket', data.id)
                commit('toggleLoader', false)
                window.open(response.data.data.url, '_blank');
            }).catch(error => {
                commit('setMsgModal', error.message[0])
                commit('toggleLoader', false)
                commit('toggleModal', true)
                return false
            });
        },
        getStatusWooCommerce: ({ commit }) => {
            Axios.get(`${ajaxurl}?action=get_status_woocommerce&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_orders}`).then(response => {
                commit('setStatusWc', response.data.statusWc)
            });
        },
        closeModal: ({ commit }) => {
            commit('toggleModal', false)
        }
    }
}

export default orders