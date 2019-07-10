<template>
    <span style="font-size: 14px;">
        <strong>{{to.first_name}} {{to.last_name}}</strong> <br>
        {{to.email}} <br>
        {{to.phone}} <br>
    </span>
</template>
<script>
    export default {
        props: {
            to: {
                type: Object,
                default: () => ({}),
            }
        },
        mounted () { 
        }
    }
</script>