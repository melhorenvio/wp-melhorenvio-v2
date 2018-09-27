<template>
    <div class="app-pedidos">
        <h1>Meus pedidos</h1>

        <label>Status Melhor Envio</label><br>
        <select v-model="status">
            <option value="all">Todos</option>
            <option value="printed">Impresso</option>
            <option value="paid">Pago</option>
            <option value="pending">Pendente</option>
            <option value="generated">Gerado</option>
        </select><br>

        <label>Status WooCommerce</label><br>
        <select v-model="wpstatus">
            <option value="all">Todos</option>
            <option value="wc-pending">Pendente</option>
            <option value="wc-processing">Processando</option>
            <option value="wc-on-hold">Pendente</option>
            <option value="wc-completed">Completo</option>
            <option value="wc-cancelled">Cancelado</option>
            <option value="wc-refunded">Recusado</option>
            <option value="wc-failed">Falhado</option>
        </select>
        <br>
        <br>
        <table v-if="orders.length > 0" border="1" id="example-1">
            <tr>
                <th>#</th>
                <th>Valor pedido</th>
                <th>Cliente</th>
                <th>Cotação</th>
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
                    <select v-if="!(item.status == 'paid' || item.status == 'printed' || item.status == 'generated')" v-model="item.cotation.choose_method">
                        <option v-if="option.id && option.price" v-for="option in item.cotation" v-bind:value="option.id" :key="option.id">
                            {{ option.name }} (R${{ option.price }}) 
                        </option>
                    </select>
                    <br>
                </td>
                <td>
                    {{item.status}}
                </td>
                <td>
                    <button v-if="!item.status" @click="addCart({id:item.id, choosen:item.cotation.choose_method})">Add cart</button>
                    <button v-if="item.status && item.order_id && item.id" @click="removeCart({id:item.id, order_id:item.order_id})">Remove cart</button>
                    <button v-if="item.status && item.order_id && item.id && item.status == 'pending'" @click="payTicket({id:item.id, order_id:item.order_id})">Pay</button>
                    <button v-if="item.status && item.status == 'paid' && item.order_id" @click="createTicket({id:item.id, order_id:item.order_id})">Create ticket</button>
                    <button v-if="item.status && (item.status == 'generated' || item.status == 'printed' )" @click="printTicket({id:item.id, order_id:item.order_id})">Print ticket</button>
                </td>
            </tr>
        </table>
        <div v-else><p>Nenhum registro encontrado</p></div>
        <button @click="loadMore({status:status, wpstatus:wpstatus})">Carregar mais</button>
    </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'

export default {
    name: 'Pedidos',
    data: () => {
        return {
            status: 'all',
            wpstatus: 'all'
        }
    },
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
    watch: {
        status () {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        },
        wpstatus () {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        }
    },
    mounted () {
        if (Object.keys(this.orders).length === 0) {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        }
    }
}
</script>

<style lang="css" scoped>
</style>