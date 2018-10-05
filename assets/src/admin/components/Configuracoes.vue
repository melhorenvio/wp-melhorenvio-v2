<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
        <label>Meus endereços</label><br>
        <div v-for="option in addresses" v-bind:value="option.id" :key="option.id" >
            <input type="radio" :id="option.id" :value="option.id" v-model="address">
            <label :for="option.id"><b>{{option.label}}</b> ({{option.address}} {{option.number}}, {{option.district}} - {{option.city}}/{{option.state}} )</label>
            <br>
            <!-- <select v-model="agency">
                <option v-for="jadlog in option.jadlog" v-bind:value="jadlog.id" :key="jadlog.id">{{jadlog.name}}</option>
            </select> -->        
        </div>
        <br><br>
    
        <label>Minhas lojas</label><br>
        <div v-for="option in stores" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="store">
            <label :for="option.id"><b>{{option.name}}</b> (Documento: {{option.document}} - Registro estadual: {{option.state_register}})</label>
            <br>
        </div>
        <br><br>


        <label>Agências Jadlog</label><br>
        <div v-for="option in agencies" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="agency">
            <label :for="option.id"><b>{{option.name}}</b></label>
            <br>
        </div>
        <br><br>

        <button class="btn-border -blue" @click="updateConfig">salvar</button>

        <div class="me-modal" v-show="show_loader">
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
        updateConfig() {
            this.setSelectedAddress(this.address)
            this.setSelectedStore(this.store)
            this.setSelectedAgency(this.agency)
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
        },
        address (e) {
            console.log(e);
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