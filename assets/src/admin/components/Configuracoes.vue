<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
        <label>Meus endereços</label><br>
        <div v-for="option in addresses" v-bind:value="option.id" :key="option.id" >
            <input type="radio" :id="option.id" :value="option.id" v-model="address">
            <label :for="option.id">{{option.label}}</label>
            <br>
        </div>
        <br><br>

        <label>Minhas lojas</label><br>
        <div v-for="option in stores" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="store">
            <label :for="option.id">{{option.name}}</label>
            <br>
        </div>
        <br><br>

        <label>Agência Jadlog para postagem</label><br>
        <div v-for="option in agencies" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="agency">
            <label :for="option.id">{{option.name}}</label>
            <br>
        </div>
        <br><br>

        <button @click="updateConfig">salvar</button>

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
            agency: null
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