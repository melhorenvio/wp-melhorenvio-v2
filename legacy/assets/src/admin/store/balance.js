'use strict'
import Axios from 'axios'

const balance = {
    namespaced: true,
    state: {
        balance: null,
        username: null,
        email: null
    },
    mutations: {
        setBalance: (state, data) => {
            state.balance = data;
        },
        setUser: (state, data) => {

        }
    },  
    getters: {
        getBalance: state => state.balance,
        getUsername: state => state.username,
        getEmail: state => state.email
    },
    actions: {
        setBalance: ({commit}, data) => {        
            Axios.get(`${ajaxurl}?action=get_balance&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_users}`, data).then(response => {
                commit('setBalance', response.data.balance)
            })
            
        },
        setUser: ({commit}, data) => {        
            Axios.get(`${ajaxurl}?action=user_info`).then(response => {
                commit('setUser', response.data.user)
            })
            
        }
    }
}

export default balance