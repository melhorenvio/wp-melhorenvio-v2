<template>
    <div class="app-pedidos">
        <h1>Meus pedidos</h1>
    </div>
</template>

<script>
export default {
    name: 'Pedidos',
    data () {
        return {
            test: {}
        }
    },
    methods: {
        retrieveOrders () {
            let data = {
                action: 'test'
            }

            this.$http.get(`${ajaxurl}`, {
                params: data
            }).then((response) => {
                this.test = response.data
            })
        }
    },
    mounted () {
        this.retrieveOrders()
    }
}
</script>

<style lang="css" scoped>
</style>