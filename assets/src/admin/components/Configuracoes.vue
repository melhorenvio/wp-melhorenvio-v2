<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
        <label>Meus endereços - {{address}}</label><br>
        <div v-for="option in addresses" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="address">
            <label :for="option.id">{{option.label}}</label>
            <br>
        </div>
        <br><br>

        <label>Minhas lojas - {{store}}</label><br>
        <div v-for="option in stores" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="store">
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
        }
    },
    computed: {
        ...mapGetters('configuration', {
            addresses: 'getAddress',
            stores: 'getStores'
        })
    },
    methods: {
        ...mapActions('configuration', [
            'getAddresses',
            'setSelectedAddress',
            'getStores',
            'setSelectedStore'
        ]),
        updateConfig() {
            this.setSelectedAddress(this.address)
            this.setSelectedStore(this.store)
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
        }
    },
    mounted () {
        this.getAddresses()
        this.getStores()
    }
}
</script>

<style lang="css" scoped>
</style>