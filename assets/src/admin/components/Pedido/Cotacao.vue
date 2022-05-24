<style scoped>
.letter-small {
  font-size: 10px;
}
</style>
<template>
  <div>
    <template v-if="item.quotation.melhorenvio == false">
      <br />
      <small>Cliente não utilizou Melhor Envio</small>
    </template>

    <template v-if="item.status == null && item.quotation.length == 0">
      <img src="@images/loader.gif" />
    </template>

    <template v-if="item.quotation != false && item.status == null">
      <div class="me-form">
        <div class="formBox">
          <template v-if="item.quotation[item.quotation.choose_method]">
            <label>Pacote</label>
            <p
              class="letter-small"
              v-for="pack in item.quotation[item.quotation.choose_method].packages"
            >
              {{ pack.dimensions.height }}cm A x
              {{ pack.dimensions.width }}cm L x
              {{ pack.dimensions.length }}cm C -
              {{ pack.weight }}Kg
            </p>

            <p>
              <b>Opcionais:</b> </br>
              Aviso: 
                <small v-if="item.quotation[item.quotation.choose_method].additional_services.receipt">Sim</small>
                <small v-else>Não</small>
                </br>
              Mão própria:
                <small v-if="item.quotation[item.quotation.choose_method].additional_services.own_hand">Sim</small>
                <small v-else>Não</small>
                </br>
              Coleta:
                <small v-if="item.quotation[item.quotation.choose_method].additional_services.collect">Sim</small>
                <small v-else>Não</small>
                </br>
                <template v-if="item.quotation[item.quotation.choose_method].packages[0].insurance_value">
                    Valor segurado:
                    <small>R${{item.quotation[item.quotation.choose_method].packages[0].insurance_value}}</small>
                    </br>
                </template>
            </p>
          </template>

          <template v-if="item.quotation && item.quotation[item.quotation.choose_method]">
            <fieldset class="selectLine">
              <div class="inputBox">
                <select
                  data-cy="input-quotation"
                  v-if="!(item.status == 'paid' || item.status == 'printed' || item.status == 'generated')"
                  v-model="item.quotation.choose_method"
                >
                  <option
                    v-if="option.id && option.price"
                    v-for="option in item.quotation"
                    v-bind:value="option.id"
                    :key="option.id"
                  >{{ option.company.name }} {{ option.name }} (R${{ option.price }})</option>
                </select>
              </div>
            </fieldset>
          </template>
        </div>
      </div>
    </template>

    <template v-if="item.quotation && item.quotation[item.quotation.choose_method]">
      <p v-if="item.quotation.diff == true">*cliente não selecionou um método de envio do Melhor Envio.</p>
    </template>

    <template v-if="item.quotation.free_shipping">
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
    },
  },
};
</script>