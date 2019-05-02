<style>
    .boxBanner {
        float: left;
        width: 100%;
        margin-bottom: 1%;
    }

    .boxBanner img {
        float: left;
        width: 100%;
    }
</style>

<template>
    <div>
        <div class="boxBanner">
            <img src="https://ps.w.org/melhor-envio-cotacao/assets/banner-1544x500.png?rev=2030733" />
        </div>
        <h1>Configurações gerais</h1>


        <div class="wpme_config">
            <h2>Endereço</h2>
            <p>Escolha o endereço para cálculo de frete, esse endereço será utlizado para realizar as cotações</p>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li v-for="option in addresses" v-bind:value="option.id" :key="option.id">
                        <label :for="option.id">
                            <div class="wpme_address-top">
                                <input type="radio" :id="option.id" :value="option.id" v-model="address" @click="showAgencies({city: option.city, state: option.state})">
                                <h2>{{option.label}}</h2>
                            </div>
                            <div class="wpme_address-body">
                                <ul>
                                    <li><b>Endereço: </b>{{ `${option.address}, ${option.number}` }}</li>
                                    <li>{{ `${option.district} - ${option.city}/${option.state}` }}</li>
                                    <li v-if="option.complement">{{ `${option.complement}` }}</li>
                                    <li><b>CEP: </b>{{ `${option.postal_code}` }}</li>
                                </ul>
                            </div>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        <hr>

        <div class="wpme_config">
            <h2>Jadlog</h2>
            <p>Escolha a agência Jadlog de sua preferência para realizar o envio dos seus produtos</p>
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
        <hr>

        <template v-if="stores.length > 0">
            <div class="wpme_config">
                <h2>Loja</h2>
                <p>Escolha qual a sua loja padrão dentre as suas lojas cadastradas no Melhor Envio. A etiqueta será gerada com base nas informações da loja selecionada.</p>
                <div class="wpme_flex">
                    <ul class="wpme_address">
                        <li  v-for="option in stores" v-bind:value="option.id" :key="option.id">
                            <label :for="option.id">
                                <div class="wpme_address-top">
                                    <input type="radio" :id="option.id" :value="option.id" v-model="store" >
                                    <h2>{{option.name}}</h2>
                                </div>
                                <div class="wpme_address-body">
                                    <ul>
                                        <li v-if="option.document"><b>CNPJ:</b> {{ `${option.document}` }}</li>
                                        <li v-if="option.state_register"><b>Inscrição estadual:</b> {{ `${option.state_register}` }}</li>
                                        <li v-if="option.email"><b>E-mail:</b> {{ `${option.email} ` }}</li>
                                    </ul>
                                </div>
                            </label>
                        </li>
                    </ul>
                </div>
            </div>
            <hr>
        </template>

        <div class="wpme_config">
            <h2>Personalizar métodos de envio</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li v-for="option in methods_shipments" v-bind:value="option.id" :key="option.id">
                        <label :for="option.id">
                            <div class="wpme_address-top">
                                <h2>{{option.name}}</h2>
                            </div>
                            <div class="wpme_address-body">
                                <ul>
                                    <li><b>Nome:</b> {{option.name}}</li>
                                    <li><b>Tempo extra:</b> {{option.time}} </li>
                                    <li><b>Taxa extra:</b> {{option.tax}} </li>
                                    <li><b>Percentual extra:</b> {{option.perc}} </li>
                                </ul>
                                <hr>
                                <a @click="showModalEditMethod(option.code)">Editar</a>
                            </div>

                            <transition name="fade">
                                <div class="me-modal" v-show="codeshiping[option.code]['status']">
                                    <div>
                                        <p class="title">{{option.name}}</p>
                                        <div class="content">
                                            <ul>
                                                <li>
                                                    <label>Nome de exibição</label><br>
                                                    <input v-model="option.name" type="text" /><br><br>
                                                    <label><b>Tempo extra</b> <br>Será adicionado ao tempo de previsão de entrega</label><br>
                                                    <input v-model="option.time" type="number" /><br><br>
                                                    <label><b>Taxa extra</b> <br>Será adicionado um valor extra para o cliente sobre o valor da cotação. </label><br>
                                                    <input v-model="option.tax" type="number" /><br><br>
                                                    <label><b>Percentual extra</b> <br>Será adicionado um valor de percentual extra para o cliente sobre o valor da cotação. </label><br>
                                                    <input v-model="option.perc" type="number" />
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="buttons -center">
                                            <button @click="closeShowModalEditMethod()" type="button"  class="btn-border -full-blue">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        <hr>
        
        <div class="wpme_config">
            <h2>Customização estilo da calculadora de frete</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <input type="checkbox" value="Personalizar"  v-model="custom_calculator">
                        Customizar?
                        <div v-show="custom_calculator" v-for="option in style_calculator" :value="option.id" :key="option.id">
                            <h2>{{option.name}}</h2>
                            <textarea v-model="option.style" type="text" placeholder="width:100%; height:50px; background:#e1e1e1;"></textarea>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <hr>

        <div class="wpme_config">
            <h2>Calculadora</h2>
            <p>Ao habilitar essa opção, será exibida a calculadora de fretes com cotações do Melhor Envio na tela do produto</p>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <label for="41352">
                            <div class="wpme_address-top" style="border-bottom: none;">
                                <input type="checkbox" value="exibir"  v-model="show_calculator">
                                <label for="two">exibir a calculdora na tela do produto</label>
                            </div></br>
                    
                            <select v-show="show_calculator" name="agencies" id="agencies" v-model="where_calculator">
                                <option v-for="option in where_calculator_collect" :value="option.id" :key="option.id"><strong>{{option.name}}</strong>  </option>
                            </select>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        <hr>

        <div class="wpme_config" style="width:50%;">
            <h2>Diretório dos plugins</h2>
            <p>Em algumas instâncias do wordpress, o caminho do diretório de plugins pode ser direferente, ocorrendo falhas no plugin, sendo necessário definir o caminho manualmente no campo abaixo. Tome cuidado ao realizar essa ação.</p>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li>
                        <input type="checkbox" value="Personalizar"  v-model="show_path">
                        <span>Estou ciente dos riscos</span></br></br>
                        <input v-show="show_path" v-model="path_plugins" type="text" placeholder="/home/htdocs/html/wp-content/plugins" /><br><br>
                    </li>
                </ul>
            </div>
        </div>
        <hr>

        <button class="btn-border -blue" @click="updateConfig">salvar</button>

        <transition name="fade">
            <div class="me-modal" v-show="show_modal">
                <div>
                    <p class="title">Sucesso!</p>
                    <div class="content">
                        <p class="txt">dados atualizados com sucesso!</p>
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
            custom_calculator: false,
            show_calculator: false,
            path_plugins: '',
            show_path: false,
            codeshiping: [
                {'id':1, 'status':false},
                {'id':2, 'status':false},
                {'id':3, 'status':false},
                {'id':4, 'status':false},
                {'id':5, 'status':false},
                {'id':6, 'status':false},
                {'id':7, 'status':false},
                {'id':8, 'status':false},
                {'id':9, 'status':false},
                {'id':10, 'status':false},
                {'id':11, 'status':false}
            ],
            where_calculator: 'woocommerce_after_add_to_cart_form',
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
            style_calculator: 'getStyleCalculator',
            methods_shipments: 'getMethodsShipments',
            show_load: 'showLoad',
            path_plugins_: 'getPathPlugins',
            where_calculator_: 'getWhereCalculator',
            show_calculator_: 'getShowCalculator',
            configs: 'getConfigs'
        })
    },
    methods: {
        ...mapActions('configuration', [
            'getConfigs',
            'setSelectedAgency',
            'setPathPlugins',
            'setSelectedStore',
            'setSelectedAddress',
            'setShowCalculator',
            'setLoader',
            'setAgencies'
        ]),
        showModalEditMethod(code) {
            this.codeshiping[code]['status'] = true;
        },
        closeShowModalEditMethod() {
            this.codeshiping = [
                {'id':1, 'status':false},
                {'id':2, 'status':false},
                {'id':3, 'status':false},
                {'id':4, 'status':false},
                {'id':5, 'status':false},
                {'id':6, 'status':false},
                {'id':7, 'status':false},
                {'id':8, 'status':false},
                {'id':9, 'status':false},
                {'id':10, 'status':false},
                {'id':11, 'status':false}
            ];
        },
        updateConfig () {
            this.setLoader(true);
            var p1 = this.setSelectedAddress(this.address)
            var p2 = this.setSelectedStore(this.store)
            var p3 = this.setSelectedAgency(this.agency)
            var p4 = this.setShowCalculator()
            var p5 = this.setFieldsmethodsShipments()
            var p6 = this.setWhereCalculator()
            var p8 = this.setStyleCalculator()
            var p9 = this.setPathPlugins()
            var p10 = this.clearSession()

            Promise.all([p1, p2, p3, p4, p5, p6, p8, p9, p10]).then((resolve) => {
                this.setLoader(false);
                this.show_modal = true
            });  
        },
        showAgencies (data) {
            this.setLoader(true);
            this.agency = ''
            var responseAgencies = [];
            var promiseAgencies = new Promise((resolve, reject) => {
                this.$http.post(`${ajaxurl}?action=get_agency_jadlog&city=${data.city}&state=${data.state}`).then(function (response) {
                    if (response && response.status === 200) {
                        responseAgencies = response.data.agencies;
                        resolve(true);
                    }
                })
            });

            promiseAgencies.then((resolve) => {
                this.setAgencies(responseAgencies);
                this.setLoader(false);
            })
        },
        close() {
            this.show_modal = false;
        },
        setShowCalculator () {
            return new Promise((resolve, reject) => {
                this.$http.post(`${ajaxurl}?action=set_calculator_show&data=${this.show_calculator}`).then( (response) => {
                    if (response && response.status === 200) {
                        this.show_calculator = response.data
                        resolve(true)
                    }
                })
            });
        },
        setWhereCalculator() {
            return new Promise((resolve, reject) => {
                this.$http.post(`${ajaxurl}?action=save_where_calculator&option=${this.where_calculator}`).then( (response) => {
                    resolve(true)
                })
            })
        },
        setFieldsmethodsShipments () {
            return new Promise((resolve, reject) =>  {
                this.methods_shipments.forEach((item) => {
                    this.$http.post(`${ajaxurl}?action=save_options&id=${item.code}&tax=${item.tax}&time=${item.time}&name=${item.name}&perc=${item.perc}`).then( (response) => {
                        resolve(true)
                    })
                });
            })
        },
        setStyleCalculator () {
            return new Promise((resolve, reject) =>  {
                Object.entries(this.style_calculator).forEach(([key, val]) => {
                    this.$http.post(`${ajaxurl}?action=save_style_calculator&id=${key}&style=${val.style}`).then( (response) => {
                        resolve(true)
                    })
                });
            })
        },
        setPathPlugins () {
            return new Promise((resolve, reject) => {
                this.$http.post(`${ajaxurl}?action=set_path_plugins&path=${this.path_plugins}`).then( (response) => {
                    resolve(true)
                })
            })
        },
        clearSession() {
            return new Promise((resolve, reject) => {
                this.$http.get(`${ajaxurl}?action=delete_melhor_envio_session`).then( (response) => {
                    resolve(true)
                })
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
            this.setLoader(true);
            if (this.agencies.length > 0) {
                this.agencies.filter(item => {
                    if (item.selected) {
                        this.agency = item.id
                    }
                })
            }
            this.setLoader(false);
        },
        show_calculator_(e) {
            this.show_calculator = e;
        },
        path_plugins_(e) {
            this.path_plugins = e;
        },
        where_calculator_(e) {
            this.where_calculator = e;
        }
    },
    mounted () {
        this.setLoader(true);
        var promiseConfigs = this.getConfigs();
        promiseConfigs.then((resolve) => {
            this.setLoader(false);
        })
    }
}
</script>

<style lang="css" scoped>
</style>