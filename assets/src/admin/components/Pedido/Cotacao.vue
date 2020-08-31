<style scoped>
.letter-small {
  font-size: 10px;
}
</style>
<template>
  <div>
    <template v-if="item.cotation.melhorenvio == false">
      <br />
      <small>Cliente não utilizou Melhor Envio</small>
    </template>

    <template v-if="item.status == null && item.cotation.length == 0">
      <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/loader.gif" />
    </template>

    <template v-if="item.cotation != false && item.status == null">
      <div class="me-form">
        <div class="formBox">
          <template v-if="item.cotation[item.cotation.choose_method]">
            <label>Pacote</label>
            <p
              class="letter-small"
              v-for="pack in item.cotation[item.cotation.choose_method].packages"
            >
              {{ pack.dimensions.height }}cm A x
              {{ pack.dimensions.width }}cm L x
              {{ pack.dimensions.length }}cm C -
              {{ pack.weight }}Kg
            </p>

            <p>
              <b>Opcionais:</b> </br>
              Aviso: 
                <small v-if="item.cotation[item.cotation.choose_method].additional_services.receipt">Sim</small>
                <small v-else>Não</small>
                </br>
              Mão própria:
                <small v-if="item.cotation[item.cotation.choose_method].additional_services.own_hand">Sim</small>
                <small v-else>Não</small>
                </br>
              Coleta:
                <small v-if="item.cotation[item.cotation.choose_method].additional_services.collect">Sim</small>
                <small v-else>Não</small>
                </br>
            </p>
          </template>

          <template v-if="item.cotation && item.cotation[item.cotation.choose_method]">
            <fieldset class="selectLine">
              <div class="inputBox">
                <select
                  v-if="!(item.status == 'paid' || item.status == 'printed' || item.status == 'generated')"
                  v-model="item.cotation.choose_method"
                >
                  <option
                    v-if="option.id && option.price"
                    v-for="option in item.cotation"
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

    <template v-if="item.cotation && item.cotation[item.cotation.choose_method]">
      <p v-if="item.tracking != null">
        Rastreio:
        <a :href="item.link_tracking" target="_blank">{{item.tracking}}</a>
      </p>
      <p v-if="item.cotation.diff">*cliente não selecionou um método de envio do Melhor Envio.</p>
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
      default: () => ({})
    }
  },
  mounted() {}
};
</script>