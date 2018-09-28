'use strict'
import Axios from 'axios'

const configuration = {
    namespaced: true,
    state: {
        addresses: [],
    },
    mutations: {
        setAddress: (state, data) => {
            state.addresses = data;
        }
    },  
    getters: {
        getAddress: state => state.addresses
    },
    actions: {
        getAddresses: ({commit}, data) => {

            console.log('here');

            // let content = {
            //     action: 'get_addresses',
            // }

            // Axios.get(`${ajaxurl}`, {
            //     params: content
            // }).then(function (response) {

            //     // if (response && response.status === 200) {
            //     //     commit('retrieveMany', response.data)
            //     // }
            // })
        },
    }
}

export default configuration