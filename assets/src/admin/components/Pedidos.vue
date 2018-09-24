<template>
    <div class="app-pedidos">
        <h1>Meus pedidos</h1>
        <table border="1" id="example-1">
            <tr>
                <th>#</th>
                <th>Valor pedido</th>
                <th>Cliente</th>
                <th>Cotação</th>
                <th>Ordem ID (Melhor Envio)</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
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
                    <select v-model="item.cotation.choose_method">
                        <option v-if="option.id && option.price" v-for="option in item.cotation" v-bind:value="option.id" :key="option.id">
                            {{ option.name }} (R${{ option.price }}) 
                        </option>
                    </select>
                    <br>
                </td>
                <td>
                    {{item.order_id}}
                </td>
                <td>
                    {{item.status}}
                </td>
                <td>
                    <button v-if="!item.status" @click="addCart({id:item.id, choosen:item.cotation.choose_method})">Add cart</button>
                    <button v-if="item.order_id && item.id" @click="removeCart({id:item.id, order_id:item.order_id})">Remove cart</button>
                    <button v-if="item.order_id && item.id && item.status != 'paid' && item.status != 'generated' && item.status != 'printed'" @click="payTicket({id:item.id, order_id:item.order_id})">Pay</button>
                    <button v-if="item.status && item.status == 'paid' && item.order_id" @click="createTicket({id:item.id, order_id:item.order_id})">Create ticket</button>
                    <button v-if="item.status && (item.status == 'generated' || item.status == 'printed' )" @click="printTicket({id:item.id, order_id:item.order_id})">Print ticket</button>
                </td>
            </tr>
        </table>

        <button @click="loadMore()">Carregar mais</button>
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
            'loadMore',
            'addCart',
            'removeCart',
            'payTicket',
            'createTicket',
            'printTicket'
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