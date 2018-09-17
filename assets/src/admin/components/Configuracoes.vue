<template>
    <div class="app-configuracoes">
        <h1>Minhas configurações</h1>
    </div>
</template>

<script>
export default {
    name: 'Configuracoes',
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