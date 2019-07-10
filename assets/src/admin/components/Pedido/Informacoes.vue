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
                            <li style="width:40%">
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