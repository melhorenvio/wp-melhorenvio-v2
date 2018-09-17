'use strict'
import Axios from 'axios'

const ajax = parameters => {
    Axios.get(`${ajaxurl}`, {
        params: parameters
    }).then(function (response) {
        return response.data
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

            let payload = ajax(data)

            console.log('Payload: ', payload)
            

            commit('retrieveMany', payload)
        }
        // loadMore: ({commit}) => {
        //     let data = {
        //         action: 'get_orders',
        //         limit: (filters.limit) ? filters.limit : 10,
        //         skip: (filters.skip) ? filters.skip : 0 // per_page
        //     }

        //     data = ajax(data)

        //     commit('loadMore', data)
        // }
    }
}

export default orders