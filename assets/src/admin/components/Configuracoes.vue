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
            show_modal: false
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
            'setSelectedAgency',
        ]),
        updateConfig () {
            this.setSelectedAddress(this.address)
            this.setSelectedStore(this.store)
            this.setSelectedAgency(this.agency)
            this.show_modal = true
        },
        showAgencies (data) {
            this.agency = ''
            this.getAgencies(data)
        },
        close() {
            this.show_modal = false;
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
    }
}
</script>

<style lang="css" scoped>
</style>