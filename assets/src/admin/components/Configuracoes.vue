<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
        <label>Endereços</label><br>

        <div v-for="option in addresses" v-bind:value="option.id" :key="option.id">
            <input type="radio" :id="option.id" :value="option.id" v-model="address">
            <label :for="option.id">{{option.label}}</label>
            <br>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex'
export default {
    name: 'Configuracoes',
    data () {
        return {
            address: null
        }
    },
    computed: {
        ...mapGetters('configuration', {
            addresses: 'getAddress'
        })
    },
    methods: {
        ...mapActions('configuration', [
            'getAddresses',
            'setSelectedAddress'
        ]),
    },
    watch : {
        address (e) {
            this.setSelectedAddress(e);
        }
    },
    mounted () {
        this.getAddresses()
    }
}
</script>

<style lang="css" scoped>
</style>