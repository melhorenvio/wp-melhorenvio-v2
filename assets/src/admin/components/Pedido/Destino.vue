<template>
    <span style="font-size: 14px;">
        {{to.name}}<br>
        {{to.email}} <br>
        {{to.phone}} <br>
        {{to.address}}, {{to.number}} <br>
        {{to.city}}/{{to.state_abbr}}
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