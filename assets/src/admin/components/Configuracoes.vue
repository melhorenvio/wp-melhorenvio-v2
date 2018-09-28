<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
        <label>Endereços</label><br>

        <div v-for="option in addresses" v-bind:value="option.id" :key="option.id">
            <input type="radio" id="one" value="Um" v-model="address">
            <label for="one">Um</label>
            <br>
            <input type="radio" id="two" value="Dois" v-model="address">
            <label for="two">Dois</label>
            <br>
        </div>
        <span>Escolhido: {{ address }}</span>
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
            'getAddresses'
        ]),
    },
    watch : {
        // address () {
        //     this.setSelectedAddress();
        // }
    },
    mounted () {
        // this.getAddress()
    }
}
</script>

<style lang="css" scoped>
</style>