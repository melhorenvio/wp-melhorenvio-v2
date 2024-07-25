'use strict'

import Axios from 'axios'
import isEmpty from 'lodash/isEmpty'
import isNull from 'lodash/isNull'

const configuration = {
    namespaced: true,
    state: {
        wp_nonce: null,
        origin: [],
        label: {
            name: "",
            email: "",
            phone: "",
            document: "",
            company_document: "",
            state_register: "",
            economic_activity_code: ""
        },
        dimension: {
            width: 10,
            height: 10,
            length: 10,
            weight: 1
        },
        agencies: [],
        agenciesJadlogCentralized: [],
        agenciesLoggi: [],
        agenciesAzul: [],
        agenciesLatam: [],
        agenciesJeT: [],
        allAgencies: [],
        allAgenciesJadlogCentralized: [],
        allAgenciesLoggi: [],
        allAgenciesAzul: [],
        allAgenciesLatam: [],
        allAgenciesJeT: [],
        styleCalculator: [],
        path_plugins: null,
        show_calculator: false,
        show_all_jadlog_agencies: false,
        options_calculator: {
            receipt: false,
            own_hand: false,
            insurance_value: true
        },
        where_calculator: 'woocommerce_after_add_to_cart_form',
        agencySelected: null,
        agencyJadlogCentralizedSelected: null,
        agencyLoggiSelected: null,
        agencyAzulSelected: null,
        agencyLatamSelected: null,
        agencyJeTSelected: null,
        token_enviroment: 'production',
        methods_shipments: [],
        show_load: true,
        configs: []
    },
    mutations: {
        toggleLoader: (state, data) => {
            state.show_load = data
        },
        setStyleCalculator: (state, data) => {
            state.styleCalculator = data;
        },
        setOrigin: (state, data) => {
            state.origin = data
        },
        setLabel: (state, data) => {
            state.label = data
        },
        setDimension: (state, data) => {
            state.dimension = data
        },
        setStore: (state, data) => {
            state.stores = data
        },
        setAgency: (state, data) => {
            state.agencies = data
        },
        setAgencyAzul: (state, data) => {
            state.agenciesAzul = data
        },
        setAgencyJadlogCentralized: (state, data) => {
            state.agenciesJadlogCentralized = data
        },
        setAgencyLoggi: (state, data) => {
            state.agenciesLoggi = data
        },
        setAgencyLatam: (state, data) => {
            state.agenciesLatam = data
        },
        setAgencyJeT: (state, data) => {
            state.agenciesJeT = data
        },
        setAgencySelected: (state, data) => {
            state.agencySelected = data
        },
        setAgencyAzulSelected: (state, data) => {
            state.agencyAzulSelected = data
        },
        setAgencyJadlogCentralizedSelected: (state, data) => {
            state.agencyJadlogCentralizedSelected = data
        },
        setAgencyLoggiSelected: (state, data) => {
            state.agencyLoggiSelected = data
        },
        setAgencyLatamSelected: (state, data) => {
            state.agencyLatamSelected = data
        },
        setAgencyJeTSelected: (state, data) => {
            state.agencyJeTSelected = data
        },
        setAllAgency: (state, data) => {
            state.allAgencies = data
        },
        setAllAgencyAzul: (state, data) => {
            state.allAgenciesAzul = data
        },
        setAllAgencyJadlogCentralized: (state, data) => {
            state.allAgenciesJadlogCentralized = data
        },
        setAllAgencyLoggi: (state, data) => {
            state.allAgenciesLoggi = data
        },
        setAllAgencyLatam: (state, data) => {
            state.allAgenciesLatam = data
        },
        setAllAgencyJeT: (state, data) => {
            state.allAgenciesJeT = data
        },
        setPathPlugins: (state, data) => {
            state.path_plugins = data;
        },
        setConfigs: (state, data) => {
            state.configs = data
        },
        setShowCalculator: (state, data) => {
            state.show_calculator = data
        },
        setShowAllJadlogAgencies: (state, data) => {
            state.show_all_jadlog_agencies = data
        },
        setMethodShipments: (state, data) => {
            state.methods_shipments = data
        },
        setWhereCalculator: (state, data) => {
            state.where_calculator = data
        },
        setOptionsCalculator: (state, data) => {
            state.options_calculator = data;
        },
        setTokenEnvironment: (state, data) => {
            state.token_enviroment = data;
        }
    },
    getters: {
        getOrigin: state => state.origin,
        getLabel: state => state.label,
        getDimension: state => state.dimension,
        getAgencies: state => state.agencies,
        getAgenciesAzul: state => state.agenciesAzul,
        getAgenciesJadlogCentralized: state => state.agenciesJadlogCentralized,
        getAgenciesLoggi: state => state.agenciesLoggi,
        getAgenciesLatam: state => state.agenciesLatam,
        getAgenciesJeT: state => state.agenciesJeT,
        getAllAgencies: state => state.allAgencies,
        getAgencySelected: state => state.agencySelected,
        getAgencyAzulSelected: state => state.agencyAzulSelected,
        getAgencyJadlogCentralizedSelected: state => state.agencyJadlogCentralizedSelected,
        getAgencyLoggiSelected: state => state.agencyLoggiSelected,
        getAgencyLatamSelected: state => state.agencyLatamSelected,
        getAgencyJeTSelected: state => state.agencyJeTSelected,
        getStyleCalculator: state => state.styleCalculator,
        getPathPlugins: state => state.path_plugins,
        getShowCalculator: state => state.show_calculator,
        getShowAllJadlogAgencies: state => state.show_all_jadlog_agencies,
        showLoad: state => state.show_load,
        getMethodsShipments: state => state.methods_shipments,
        getWhereCalculator: state => state.where_calculator,
        getConfigs: state => state.configs,
        getOptionsCalculator: state => state.options_calculator,
        getEnvironment: state => state.token_enviroment,
    },
    actions: {
        getConfigs: ({ commit }, data) => {
            let content = {
                action: 'get_configuracoes',
                _wpnonce: wpApiSettingsMelhorEnvio.nonce_configs
            }
            return new Promise((resolve, reject) => {
                Axios.get(`${ajaxurl}`, {
                    params: content
                }).then(function (response) {
                    if (response && response.status === 200) {

                        if (response.data.origin && !isEmpty(response.data.origin)) {
                            commit('setOrigin', response.data.origin);
                        }

                        if (response.data.label && !isEmpty(response.data.label)) {
                            commit('setLabel', response.data.label);
                        }

                        if (response.data.agencies && !isNull(response.data.agencies)) {
                            commit('setAgency', response.data.agencies);
                            commit('setAllAgency', response.data.allAgencies);
                        }

                        if (response.data.dimension_default && !isNull(response.data.dimension_default)) {
                            commit('setDimension', response.data.dimension_default);
                        }

                        if (response.data.agenciesAzul && !isNull(response.data.agenciesAzul)) {
                            commit('setAgencyAzul', response.data.agenciesAzul);
                            commit('setAllAgencyAzul', response.data.allAgenciesAzul);
                        }

                        if (response.data.agenciesJadlogCentralized && !isNull(response.data.agenciesJadlogCentralized)) {
                            commit('setAgencyJadlogCentralized', response.data.agenciesJadlogCentralized);
                            commit('setAllAgencyJadlogCentralized', response.data.allAgenciesJadlogCentralized);
                        }

                        if (response.data.agenciesLoggi && !isNull(response.data.agenciesLoggi)) {
                            commit('setAgencyLoggi', response.data.agenciesLoggi);
                            commit('setAllAgencyLoggi', response.data.allAgenciesLoggi);
                        }

                        if (response.data.agenciesLatam && !isNull(response.data.agenciesLatam)) {
                            commit('setAgencyLatam', response.data.agenciesLatam);
                            commit('setAllAgencyLatam', response.data.allAgenciesLatam);
                        }

                        if (response.data.agenciesJeT && !isNull(response.data.agenciesJeT)) {
                            commit('setAgencyJeT', response.data.agenciesJeT);
                            commit('setAllAgencyJeT', response.data.allAgenciesJeT);
                        }

                        if (response.data.stores && !isEmpty(response.data.stores)) {
                            commit('setStore', response.data.stores)
                        }
                        commit('setAgencySelected', response.data.agencySelected)
                        commit('setAgencyAzulSelected', response.data.agencyAzulSelected)
                        commit('setAgencyJadlogCentralizedSelected', response.data.agencyJadlogCentralizedSelected)
                        commit('setAgencyLoggiSelected', response.data.agencyLoggiSelected)
                        commit('setAgencyLatamSelected', response.data.agencyLatamSelected)
                        commit('setAgencyJeTSelected', response.data.agencyJeTSelected)
                        commit('setStyleCalculator', response.data.style_calculator)
                        commit('setPathPlugins', response.data.path_plugins)
                        commit('setShowCalculator', response.data.calculator)
                        commit('setShowAllJadlogAgencies', response.data.all_agencies_jadlog)
                        commit('setMethodShipments', response.data.metodos)
                        commit('setWhereCalculator', response.data.where_calculator)
                        commit('setOptionsCalculator', response.data.options_calculator)
                        commit('setTokenEnvironment', response.data.token_environment)
                        resolve(true)
                    }
                }).catch((error) => {
                    alert('Aconteceu um erro ao obter os dados de configuração');
                })
            })
        },
        getAgencies: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_jadlog&city=${data.city}&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgency', response.data.agencies);
                }
            })
        },
        getAgenciesAzul: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_azul&city=${data.city}&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgencyAzul', response.data.agencies);
                }
            })
        },
        getAgenciesJadlogCentralized: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agencies&city=${data.city}&state=${data.state}&serviceId=27&company=2`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgencyJadlogCentralized', response.data.agencies);
                }
            })
        },
        getAgenciesLoggi: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agencies&city=${data.city}&state=${data.state}&serviceId=31&company=14`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgencyLoggi', response.data.agencies);
                }
            })
        },
        getAgenciesLatam: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_latam&city=${data.city}&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgencyLatam', response.data.agencies);
                }
            })
        },
        getAgenciesJeT: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_jet&city=${data.city}&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAgencyJeT', response.data.agencies);
                }
            })
        },
        getAllAgencies: ({ commit }, data) => {
            commit('toggleLoader', true);
            Axios.post(`${ajaxurl}?action=get_agency_jadlog&state=${data.state}`).then(function (response) {
                commit('toggleLoader', false);
                if (response && response.status === 200) {
                    commit('setAllAgencies', response.data.agencies);
                }
            })
        },
        saveAll: ({ commit }, data) => {

            return new Promise((resolve, reject) => {
                const form = new FormData();

                form.append('_wpnonce', wpApiSettingsMelhorEnvio.nonce_configs);

                if (data.origin) {
                    form.append('origin', data.origin)
                }
                if (data.label) {
                    const labels = Object.entries(data.label);
                    labels.forEach((item) => {
                        form.append(`label[${item[0]}]`, item[1]);
                    })
                }

                if (data.dimension_default) {
                    form.append('dimension_default[width]', data.dimension_default.width);
                    form.append('dimension_default[height]', data.dimension_default.height);
                    form.append('dimension_default[length]', data.dimension_default.length);
                    form.append('dimension_default[weight]', data.dimension_default.weight);
                }

                if (data.agency) {
                    form.append('agency', data.agency);
                }

                if (data.agency_correios_centralized) {
                    form.append('agency_correios_centralized', data.agency_correios_centralized);
                }

                if (data.agency_jadlog_centralized) {
                    form.append('agency_jadlog_centralized', data.agency_jadlog_centralized);
                }

                if (data.agency_loggi) {
                    form.append('agency_loggi', data.agency_loggi);
                }

                if (data.agency_azul) {
                    form.append('agency_azul', data.agency_azul);
                }

                if (data.agency_latam) {
                    form.append('agency_latam', data.agency_latam);
                }

                if (data.agency_jet) {
                    form.append('agency_jet', data.agency_jet);
                }

                if (data.show_calculator) {
                    form.append('show_calculator', data.show_calculator);
                }

                if (data.show_all_agencies_jadlog) {
                    form.append('show_all_agencies_jadlog', data.show_all_agencies_jadlog);
                }

                if (data.where_calculator) {
                    form.append('where_calculator', data.where_calculator);
                }

                if (data.path_plugins) {
                    form.append('path_plugins', data.path_plugins);
                }

                form.append('options_calculator[receipt]', data.options_calculator.receipt);
                form.append('options_calculator[own_hand]', data.options_calculator.own_hand);
                form.append('options_calculator[insurance_value]', data.options_calculator.insurance_value);

                Axios.post(`${ajaxurl}?action=save_configuracoes`, form).then(function (response) {
                    if (response && response.status === 200) {
                        resolve(true)
                    }
                })
            });
        },
        setLoader: ({ commit }, data) => {
            commit('toggleLoader', data)
        },
        setAgencies: ({ commit }, data) => {
            commit('setAgency', data)
        },
        setAgenciesAzul: ({ commit }, data) => {
            commit('setAgencyAzul', data)
        },
        setAgenciesCorreiosCentralized: ({ commit }, data) => {
            commit('setAgencyCorreiosCentralized', data)
        },
        setAgenciesJadlogCentralized: ({ commit }, data) => {
            commit('setAgencyJadlogCentralized', data)
        },
        setAgenciesLoggi: ({ commit }, data) => {
            commit('setAgencyLoggi', data)
        },
        setAgenciesLatam: ({ commit }, data) => {
            commit('setAgencyLatam', data)
        },
        setAgenciesJeT: ({ commit }, data) => {
            commit('setAgencyJeT', data)
        },
        setAllAgencies: ({ commit }, data) => {
            commit('setAllAgency', data)
        },
    }
}

export default configuration
