<template>
    <div>
        <template v-if="item.cotation.melhorenvio == false">
            <br>
            <small>Cliente não utilizou Melhor Envio</small>
        </template>

        <template v-if="item.cotation != false && item.status == 'pending'">
            <div  class="me-form">
                <div class="formBox">
                    <template v-if="item.packages && item.packages[item.cotation.choose_method] && item.cotation &&  item.cotation[item.cotation.choose_method]">
                        <label>Pacote</label>
                        <p>
                            {{ item.packages[item.cotation.choose_method].altura }}cm A x 
                            {{ item.packages[item.cotation.choose_method].largura }}cm L x 
                            {{ item.packages[item.cotation.choose_method].comprimento }}cm C - 
                            {{ item.packages[item.cotation.choose_method].peso }}Kg
                        </p>
                    </template>
                    
                    <label>Métodos de envio</label> 
                    <template v-if="item.cotation[item.cotation.choose_method]">
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

            <div class="errosShadow" style="display:none;">
                <template v-if="item.errors">
                    <div  v-for="(errors, e) in item.errors" :key="e">
                        <div  v-for="(error, ee) in errors" :key="ee">
                            <p v-if="item.cotation.choose_method == e" style="color:red;"> {{error.message}}</p>
                        </div>
                    </div>
                </template> 
            </div>

        </template>

        <template v-else>
            <p>
                {{ item.cotation[item.cotation.choose_method].company.name }}
                {{ item.cotation[item.cotation.choose_method].name }}
                R${{ item.cotation[item.cotation.choose_method].price }}
            </p>
        </template>

        <template v-if="item.protocol && item.status != null">
            <p>
                {{ item.protocol }}
            </p>
        </template>

        <template v-if="item.cotation.free_shipping">
            <p>*Cliente utilizou cupom de frete grátis</p>
        </template>

        <template v-if="item.cotation.diff.length != 0 && item.cotation.diff[item.cotation.choose_method] && item.cotation.diff[item.cotation.choose_method].first">
            <p>*O valor foi atualizado, valor pago em {{item.cotation.diff[item.cotation.choose_method].date}} R${{item.cotation.diff[item.cotation.choose_method].first}}</p>
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