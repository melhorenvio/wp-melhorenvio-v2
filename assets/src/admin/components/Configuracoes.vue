<template>
    <div>
        <div class="wpme_config">
            <h2>Escolha o endereço para cálculo de frete</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li v-for="option in addresses" v-bind:value="option.id" :key="option.id">
                        <label for="41352">
                            <div class="wpme_address-top">
                                <input type="radio" :id="option.id" :value="option.id" v-model="address" @click="showAgencies({city: option.city, state: option.state})">
                                <h2>{{option.label}}</h2>
                            </div>
                            <div class="wpme_address-body">
                                <ul>
                                    <li>{{ `${option.address}, ${option.number}` }}</li>
                                    <li>{{ `${option.district} - ${option.city}/${option.state}` }}</li>
                                    <li>{{ `${option.complement}` }}</li>
                                    <li>{{ `CEP: ${option.postal_code}` }}</li>
                                </ul>
                            </div>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wpme_config">
            <h2>Escolha a unidade Jadlog</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <select name="agencies" id="agencies" v-model="agency">
                            <option value="">Selecione...</option>
                            <option v-for="option in agencies" :value="option.id" :key="option.id"><strong>{{option.name}}</strong>  </option>
                        </select>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wpme_config">
            <h2>Escolha a sua loja</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li v-for="option in stores" v-bind:value="option.id" :key="option.id">
                        <label for="41352">
                            <div class="wpme_address-top">
                                <input type="radio" :id="option.id" :value="option.id" v-model="store" >
                                <h2>{{option.name}}</h2>
                            </div>
                            <div class="wpme_address-body">
                                <ul>
                                    <li>CNPJ {{ `${option.document}` }}</li>
                                    <li>Inscrição estadual {{ `${option.state_register}` }}</li>
                                    <li>E-mail {{ `${option.email} ` }}</li>
                                </ul>
                            </div>
                        </label>
                    </li>
                </ul>
            </div>
        </div>


        <div class="wpme_config">
            <h2>Configuração calculadora na página de produto</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <label for="41352">
                            <div class="wpme_address-top">
                                <input type="checkbox" value="exibir"  v-model="show_calculator">
                                <label for="two">exibir a calculdora na tela do produto</label>
                            </div>
                        </label>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wpme_config">
            <h2>Onde deseja exibir a cotação do produto?</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <select name="agencies" id="agencies" v-model="where_calculator">
                            <option v-for="option in where_calculator_collect" :value="option.id" :key="option.id"><strong>{{option.name}}</strong>  </option>
                        </select>
                    </li>
                </ul>
            </div>
        </div>

        <div class="wpme_config">
            <h2>Personalizar métodos de envio</h2>
            <div class="wpme_flex">
                <ul v-for="option in methods_shipments" :value="option.id" :key="option.id" class="wpme_address">
                    <li>
                        <h2>{{option.title}}</h2>
                        <label>Nome de exibição</label><br>
                        <input v-model="option.name" type="text" /><br><br>
                        <label>Tempo extra</label><br>
                        <input v-model="option.time" type="number" /><br><br>
                        <label>Taxa extra</label><br>
                        <input v-model="option.tax" type="number" />
                    </li>
                </ul>
            </div>
        </div>

        <button class="btn-border -blue" @click="updateConfig">salvar</button>

        <transition name="fade">
            <div class="me-modal" v-show="show_modal">
                <div>
                    <p class="title">Atenção</p>
                    <div class="content">
                        <p class="txt">dados atualizados</p>
                    </div>
                    <div class="buttons -center">
                        <button type="button" @click="close" class="btn-border -full-blue">Fechar</button>
                    </div>
                </div>
            </div>
        </transition>

        <div class="me-modal" v-show="show_load">
            <svg style="float:left; margin-top:10%; margin-left:50%;" class="ico" width="88" height="88" viewBox="0 0 44 44" xmlns="http://www.w3.org/2000/svg" stroke="#3598dc">
                    <g fill="none" fill-rule="evenodd" stroke-width="2">
                    <circle cx="22" cy="22" r="1">
                        <animate attributeName="r"
                        begin="0s" dur="1.8s"
                        values="1; 20"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.165, 0.84, 0.44, 1"
                        repeatCount="indefinite" />
                        <animate attributeName="stroke-opacity"
                        begin="0s" dur="1.8s"
                        values="1; 0"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.3, 0.61, 0.355, 1"
                        repeatCount="indefinite" />
                    </circle>
                    <circle cx="22" cy="22" r="1">
                        <animate attributeName="r"
                        begin="-0.9s" dur="1.8s"
                        values="1; 20"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.165, 0.84, 0.44, 1"
                        repeatCount="indefinite" />
                        <animate attributeName="stroke-opacity"
                        begin="-0.9s" dur="1.8s"
                        values="1; 0"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.3, 0.61, 0.355, 1"
                        repeatCount="indefinite" />
                    </circle>
                    </g>
                </svg>
        </div>

    </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
export default {
    name: 'Configuracoes',
    data () {
        return {
            address: null,
            store: null,
            agency: null,
            show_modal: false,
            show_calculator: true,
            methods_shipments: [],
            where_calculator: null,
            where_calculator_collect: [
                {
                    'id': 'woocommerce_before_single_product',
                    'name': 'Antes do titulo do produto (Depende do tema do projeto)'
                },
                {
                    'id': 'woocommerce_after_single_product',
                    'name': 'Depois do titulo do produto'
                },
                {
                    'id': 'woocommerce_single_product_summary',
                    'name': 'Antes da descrição do produto'
                },
                {
                    'id': 'woocommerce_before_add_to_cart_form',
                    'name': 'Antes do fórmulario de comprar'
                },
                {
                    'id': 'woocommerce_before_variations_form',
                    'name': 'Antes das opçoes do produto'
                },
                {
                    'id': 'woocommerce_before_add_to_cart_button',
                    'name': 'Antes do botão de comprar'
                },
                {
                    'id': 'woocommerce_before_single_variation',
                    'name': 'Antes do campo de variações'
                },
                {
                    'id': 'woocommerce_single_variation',
                    'name': 'Antes das variações'
                },
                {
                    'id': 'woocommerce_after_add_to_cart_form',
                    'name': 'Depois do botão de comprar'
                },
                {
                    'id': 'woocommerce_product_meta_start',
                    'name': 'Antes das informações do produto'
                },
                {
                    'id': 'woocommerce_share',
                    'name': 'Depois dos botões de compartilhamento'
                }
            ]
        }
    },
    computed: {
        ...mapGetters('configuration', {
            addresses: 'getAddress',
            stores: 'getStores',
            agencies: 'getAgencies',
            show_load: 'showLoad'
        })
    },
    methods: {
        ...mapActions('configuration', [
            'getAddresses',
            'setSelectedAddress',
            'getStores',
            'setSelectedStore',
            'getAgencies',
            'setSelectedAgency'
        ]),
        updateConfig () {
            this.setSelectedAddress(this.address)
            this.setSelectedStore(this.store)
            this.setSelectedAgency(this.agency)
            this.setShowCalculator()
            this.setFieldsmethodsShipments()
            this.setWhereCalculator()
            this.show_modal = true
        },
        showAgencies (data) {
            this.agency = ''
            this.getAgencies(data)
        },
        close() {
            this.show_modal = false;
        },
        getShowCalculator () {
            let data = {action: 'get_calculator_show'}
            this.$http.get(`${ajaxurl}`, {
                params: data
            }).then( (response) => {
                if (response && response.status === 200) {
                    this.show_calculator = response.data
                }
            })
        },
        getWhereCalculator () {
            let data = {action: 'get_where_calculator'}
            this.$http.get(`${ajaxurl}`, {
                params: data
            }).then( (response) => {
                if (response && response.status === 200) {
                    this.where_calculator = response.data.option
                }
            })
        },
        getMethodsShipments () {
            let data = {action: 'get_metodos'}
            this.$http.get(`${ajaxurl}`, {
                params: data
            }).then( (response) => {
                if (response && response.status === 200) {
                    this.methods_shipments = response.data
                }
            })
        },
        setShowCalculator () {
            this.$http.post(`${ajaxurl}?action=set_calculator_show&data=${this.show_calculator}`).then( (response) => {
                if (response && response.status === 200) {
                    this.show_calculator = response.data
                }
            })
        },
        setWhereCalculator() {
            this.$http.post(`${ajaxurl}?action=save_where_calculator&option=${this.where_calculator}`).then( (response) => {})
        },
        setFieldsmethodsShipments () {
            this.methods_shipments.forEach((item) => {
                this.$http.post(`${ajaxurl}?action=save_options&id=${item.code}&tax=${item.tax}&time=${item.time}&name=${item.name}`).then( (response) => {})
            });
        }
    },
    watch : {
        addresses () {
            if (this.addresses.length > 0) {
                this.addresses.filter(item => {
                    if (item.selected) {
                        this.address = item.id
                    }
                })
            }
        },
        stores () {
            if (this.stores.length > 0) {
                this.stores.filter(item => {
                    if (item.selected) {
                        this.store = item.id
                    }
                })
            }
        },
        agencies () {
            this.show_loader = false;
            if (this.agencies.length > 0) {
                this.agencies.filter(item => {
                    if (item.selected) {
                        this.agency = item.id
                    }
                })
            }
        }
    },
    mounted () {
        this.getAddresses()
        this.getStores()
        this.getAgencies()
        this.getShowCalculator()
        this.getWhereCalculator()
        this.getMethodsShipments()
    }
}
</script>

<style lang="css" scoped>
</style>