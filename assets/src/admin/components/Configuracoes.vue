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
                            <option v-for="option in agencies" :value="option.id" :key="option.id">{{ option.company_name }} ({{option.name}})</option>
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

        <div class="me-modal" v-show="show_load">
            <svg style="margin-top:20%; margin-left:40%" width='240px' height='240px' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-default"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(0 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-1s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(30 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.9166666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(60 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.8333333333333334s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(90 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.75s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(120 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.6666666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(150 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.5833333333333334s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(180 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.5s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(210 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.4166666666666667s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(240 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.3333333333333333s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(270 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.25s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(300 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.16666666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(330 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.08333333333333333s' repeatCount='indefinite'/></rect></svg>
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
            alert('Dados atualizados');
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