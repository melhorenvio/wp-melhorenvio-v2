<template>
    <div>
        <template v-if="item.cotation.melhorenvio == false">
            <br>
            <small>Cliente não utilizou Melhor Envio</small>
        </template>

        <template v-if="item.status == null && item.cotation.length == 0">
            <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/loader.gif" />
        </template>

        <template v-if="item.cotation != false && item.status == null">
            <div  class="me-form">
                <div class="formBox">
                    <template v-if="item.cotation[item.cotation.choose_method]">
                        <label>Pacote</label>
                        <p v-for="pack in item.cotation[item.cotation.choose_method].packages">
                            {{ pack.dimensions.height }}cm A x 
                            {{ pack.dimensions.width }}cm L x 
                            {{ pack.dimensions.length }}cm C - 
                            {{ pack.weight }}Kg
                        </p>
                    </template>
                    
                    <template v-if="item.cotation && item.cotation[item.cotation.choose_method]">
                        <fieldset  class="selectLine">
                            <div class="inputBox">
                                <select v-if="!(item.status == 'paid' || item.status == 'printed' || item.status == 'generated')" v-model="item.cotation.choose_method">
                                    <option v-if="option.id && option.price" v-for="option in item.cotation" v-bind:value="option.id" :key="option.id">
                                        {{ option.company.name }} {{ option.name }} (R${{ option.price }}) 
                                    </option>
                                </select>
                            </div>
                        </fieldset>
                    </template>
                </div>
            </div>

        </template>

        <template v-if="item.cotation && item.cotation[item.cotation.choose_method]">
            <p>Companhia: <b>{{ item.cotation[item.cotation.choose_method].company.name }}</b></p>
            <p>Serviço: <b>{{ item.cotation[item.cotation.choose_method].name }}</b></p>
            <p>Valor: <b>R${{ item.cotation[item.cotation.choose_method].price }}</b></p>
            <p v-if="item.tracking != null">Rastreio: <a :href="item.link_tracking" target="_blank">{{item.tracking}}</a></p>
        </template>

        <template v-if="item.cotation.free_shipping">
            <p>*Cliente utilizou cupom de frete grátis</p>
        </template>
    </div>
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