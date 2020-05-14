<template>
    <div class="me-form">
        <template v-if="item.status == null">
            <div class="formBox paddingBox">
                <template  v-if="item.cotation.choose_method == 3 || item.cotation.choose_method == 4 || item.cotation.choose_method == 10" >
                    <fieldset class="checkLine">
                        <div class="inputBox">
                            <input type="checkbox" v-model="item.non_commercial" />
                            <label>Enviar com declaração de conteúdo    </label>
                        </div>
                    </fieldset>
                    <br>
                </template>
                <template  v-if="((item.cotation.choose_method == 3 || item.cotation.choose_method == 4 || item.cotation.choose_method == 10 )  && !item.non_commercial) || (item.cotation.choose_method == 8 || item.cotation.choose_method == 9)">
                    <fieldset>
                        <div>
                            <label>Nota fiscal</label><br>
                            <input type="text" v-model="item.invoice.number" /><br>
                            <label>Chave da nota fiscal</label><br>
                            <input type="text" v-model="item.invoice.key" /><br>
                            <br>
                            <button class="btn-border -full-blue" @click="insertInvoice(item)">Salvar</button>
                        </div>
                    </fieldset>
                </template>
            </div>
        </template>

        <template v-else>
            <p>
                <b>
                    <span v-if='item.status == "released"'>Pronta para imprimir</span>
                    <span v-if='item.status == "posted"'>Etiqueta postada</span>
                </b>
            </p>
        </template>
    </div>
</template>
<script>
    import { mapActions, mapGetters } from 'vuex'
    export default {
        props: {
            item: {
                type: Object,
                default: () => ({}),
            }
        },
        methods: {
            ...mapActions('orders', [
                'insertInvoice'
            ]),
        },
        mounted () { 
        }
    }
</script>