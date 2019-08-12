<template>
    <div>
        <ul class="body-list">
            <table class="styleTableMoreInfo">
                <tr>
                    <th><span>Pacote</span></th>
                    <th><span>Produtos</span></th>
                </tr>
                <tr>
                    <td>
                        <p>
                            <b>Dimensões:</b> 
                            {{volume.height}}cm A x 
                            {{volume.width}}cm L x 
                            {{volume.length}}cm C - 
                            {{volume.weight}}Kg
                        </p>
                    </td>
                    <td>
                        <ul class="body-list">
                            <li class="product">
                                <template v-for="prod in products">
                                    <p>
                                        <b>Produto:</b> {{prod.quantity}}X - {{prod.name}} <br>
                                        <b>Valor:</b> R${{prod.total}}
                                    </p>
                                </template>
                            </li>
                        </ul>
                    </td>
    
                </tr>
            </table>
        </ul>
    </div>
</template>
<script>
    export default {
        props: {
            volume: {
                type: Array,
                default: () => ({}),
            },
            products: {
                type: Array,
                default: () => ({}),
            }
        },
        mounted () { 
        }
    }
</script>

<style>
    .table .body-list .product {
        display: grid;
        width: 100% !important;
        grid-template-columns: repeat(5, 1fr);
        grid-gap: .5rem;
    }
</style>