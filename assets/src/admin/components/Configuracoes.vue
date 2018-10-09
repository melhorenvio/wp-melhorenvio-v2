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

        <div class="table-box">
            <label>Agências Jadlog</label><br>
            <select name="agencies" id="agencies" v-model="agency">
                <option value="">Selecione...</option>
                <option v-for="option in agencies" :value="option.id" :key="option.id">{{ option.name }}</option>
            </select>
        </div>

        <div class="wpme_config">
            <h2>Escolha a sua loja</h2>
            <div class="wpme_flex">
                <ul class="wpme_address">
                    <li v-for="option in stores" v-bind:value="option.id" :key="option.id">
                        <label for="41352">
                            <div class="wpme_address-top">
                                <input type="radio" :id="option.id" :value="option.id" v-model="stores" >
                                <h2>{{option.name}}</h2>
                            </div>
                            <div class="wpme_address-body">
                                <ul>
                                    <li>CNPJ {{ `${option.document}` }}</li>
                                    <li>Registro estadual {{ `${option.state_register}` }}</li>
                                    <li>E-mail {{ `${option.email} ` }}</li>
                                </ul>
                            </div>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
        <button class="btn-border -blue" @click="updateConfig">salvar</button>
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
            show_loader: true
        }
    },
    computed: {
        ...mapGetters('configuration', {
            addresses: 'getAddress',
            stores: 'getStores',
            agencies: 'getAgencies'
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
        },
        showAgencies (data) {
            this.agency = ''

            this.getAgencies(data)
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