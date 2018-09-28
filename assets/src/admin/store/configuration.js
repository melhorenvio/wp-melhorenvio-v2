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
            let content = {
                action: 'get_addresses',
            }

            Axios.get(`${ajaxurl}`, {
                params: content
            }).then(function (response) {
                if (response && response.status === 200) {
                    commit('setAddress', response.data.address)
                }
            })
        },
        setSelectedAddress: ({commit}, data) => {
            console.log(data);
        }
    }
}

export default configuration