<template>
    <div class="app-pedidos">
        <h1>Meus pedidos</h1>

        <button @click="loadMore()">Carregar mais</button>

        <table border="1" id="example-1">
            <tr v-for="(item, index) in orders" :key="index">
                <td>{{ item.id }}</td>
                <td>{{ item.total }}</td>
                <td>
                    <p><b>{{item.to.first_name}} {{item.to.last_name}}</b></p>
                    <p>{{item.to.email}}</p>
                    <p>{{item.to.phone}}</p>
                    <p>{{item.to.address_1}} {{item.to.address_2}}</p>
                    <p>{{item.to.city}} / {{item.to.state}} - {{item.to.postcode}}</p>
                </td>
                <td>
                    <button>Add cart</button>
                    <button>Remove cart</button>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'

export default {
    name: 'Pedidos',
    computed: {
        ...mapGetters('orders', {
            orders: 'getOrders'
        })
    },
    methods: {
        ...mapActions('orders', [
            'retrieveMany',
            'loadMore'
        ])
    },
    mounted () {
        if (Object.keys(this.orders).length === 0) {
            this.retrieveMany()
        }
    }
}
</script>

<style lang="css" scoped>
</style>