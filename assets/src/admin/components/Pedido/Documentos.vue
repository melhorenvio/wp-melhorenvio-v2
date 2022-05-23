<template>
  <div class="me-form">
    <template v-if="item.status == null">
      <div class="formBox paddingBox">
        <template
          v-if="
            item.quotation.choose_method == services.JADLOG_PACKAGE ||
            item.quotation.choose_method == services.JADLOG_COM ||
            item.quotation.choose_method == services.LATAM ||
            item.quotation.choose_method == services.LATAM_JUNTOS ||
            item.quotation.choose_method == services.AZUL_AMANHA ||
            item.quotation.choose_method == services.AZUL_ECOMMERCE
          "
        >
          <fieldset class="checkLine">
            <div class="inputBox">
              <input type="checkbox" v-model="item.non_commercial" />
              <label>Enviar com declaração de conteúdo</label>
            </div>
          </fieldset>
          <br />
        </template>
        <template
          v-if="
            ((item.quotation.choose_method == services.JADLOG_PACKAGE ||
              item.quotation.choose_method == services.JADLOG_COM ||
              item.quotation.choose_method == services.LATAM ||
              item.quotation.choose_method == services.LATAM_JUNTOS) &&
              !item.non_commercial) ||
            item.quotation.choose_method == services.VIA_BRASIL_AEREO ||
            item.quotation.choose_method == services.VIA_BRASIL_RODOVIARIO
          "
        >
          <fieldset>
            <div>
              <label>Nota fiscal</label>
              <br />
              <input type="text" v-model="item.invoice.number" />
              <br />
              <label>Chave da nota fiscal</label>
              <br />
              <input type="text" v-model="item.invoice.key" />
              <br />
              <br />
              <button
                class="btn-border -full-blue"
                @click="insertInvoice(item)"
              >
                Salvar
              </button>
            </div>
          </fieldset>
        </template>
      </div>
    </template>

    <template v-else>
      <p>
        <b>
          <span v-if="item.status === status.STATUS_GENERATED"
            >Pronta para imprimir</span
          >
          <span v-if="item.status === status.STATUS_PAID"
            >Pronta para imprimir</span
          >
          <span v-if="item.status === status.STATUS_RELEASED"
            >Pronta para imprimir</span
          >
          <span v-if="item.status === status.STATUS_POSTED"
            >Etiqueta postada</span
          >
          <span v-if="item.status === status.STATUS_DELIVERED">Entregue</span>
        </b>
      </p>
    </template>
  </div>
</template>
<script>
import { mapActions } from "vuex";
import statusMelhorEnvio from "../../utils/status";
import shippingServices from "../../utils/shipping-services";
export default {
  data: () => {
    return {
      services: shippingServices,
      status: statusMelhorEnvio,
    };
  },
  props: {
    item: {
      type: Object,
      default: () => ({}),
    },
  },
  methods: {
    ...mapActions("orders", ["insertInvoice"]),
  },
};
</script>