<template>
    <div class="app-settings">
        <button type="button" @click="retrieveOrders">Click Me!</button>

        {{ test }}
    </div>
</template>

<script>
export default {
    name: 'Settings',
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