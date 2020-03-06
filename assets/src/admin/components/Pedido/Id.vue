<template>
    <span>
        <a target="_blank" :href="item.link"><strong>{{ item.id }}</strong></a>
    </span>
</template>
<script>
    export default {
        props: {
            item: {
                type: Object,
                default: () => ({}),
            }
        },
        mounted () { 
        }
    }
</script>