'use strict'
import Axios from 'axios'

const balance = {
    namespaced: true,
    state: {
        balance: null,
    },
    mutations: {
        setBalance: (state, data) => {
            state.balance = data;
        }
    },  
    getters: {
        getBalance: state => state.balance
    },
    actions: {
        setBalance: ({commit}, data) => {        
            Axios.get(`${ajaxurl}?action=get_balance`, data).then(response => {
                commit('setBalance', response.data.balance)
            })
            
        }
    }
}

export default balance