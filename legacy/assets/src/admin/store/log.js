'use strict'
import Axios from 'axios'

const log = {
    namespaced: true,
    state: {
        logs: null,
    },
    mutations: {
        retrieveLogs: (state, data) => state.logs = data 
    },  
    getters: {
        logs: state => state.logs
    },
    actions: {
        retrieveLogs: ({commit}, id) => {
            let content = {
                action: 'get_logs_order',
                order_id: id,
            }
            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                commit('retrieveLogs', response.data)
            }).catch(error => {

            })
        }
    }
}

export default log