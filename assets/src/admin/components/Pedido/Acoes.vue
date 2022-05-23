<template>
  <div class="container">
    <a
      class="action-button container__link"
      v-if="buttonCart(item)"
      data-cy="input-add-cart"
      data-tip="Adicionar o pedido no carrinho de compras"
      @click="
        sendCartSimple({
          id: item.id,
          service_id: item.quotation.choose_method,
          non_commercial: item.non_commercial,
        })
      "
    >
      <svg
        xmlns="http://www.w3.org/2000/svg"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        viewBox="0 0 512 512"
        width="25"
        height="25"
      >
        <linearGradient
          id="a"
          gradientUnits="userSpaceOnUse"
          x1="174.667"
          x2="174.667"
          y1="30"
          y2="438.078"
        >
          <stop offset="0" stop-color="#00efd1" />
          <stop offset="1" stop-color="#00acea" />
        </linearGradient>
        <linearGradient
          id="b"
          x1="372.786"
          x2="372.786"
          xlink:href="#a"
          y1="30"
          y2="438.078"
        />
        <linearGradient
          id="c"
          x1="256"
          x2="256"
          xlink:href="#a"
          y1="30"
          y2="438.078"
        />
        <path
          d="m174.667 380.772a46.5 46.5 0 1 0 46.5 46.5 46.549 46.549 0 0 0 -46.5-46.5zm0 72.992a26.5 26.5 0 1 1 26.5-26.5 26.526 26.526 0 0 1 -26.5 26.5z"
          fill="url(#a)"
        />
        <path
          d="m372.786 380.772a46.5 46.5 0 1 0 46.5 46.5 46.549 46.549 0 0 0 -46.5-46.5zm0 72.992a26.5 26.5 0 1 1 26.5-26.5 26.526 26.526 0 0 1 -26.5 26.5z"
          fill="url(#b)"
        />
        <path
          d="m470.433 103.407-340.081-5.136-9.329-28.271a46.542 46.542 0 0 0 -44.164-32h-35.14a10 10 0 1 0 0 20h35.14a26.578 26.578 0 0 1 25.179 18.289l11.781 35.611 54.359 164.28-4.9 11.865a46.293 46.293 0 0 0 42.984 63.955h203.019a10 10 0 0 0 0-20h-203.019a26.312 26.312 0 0 1 -24.49-36.384l3.844-9.272 219.733-22.5a57 57 0 0 0 49.58-43.376l25.078-104.738a10 10 0 0 0 -9.574-12.323zm-34.955 112.415a36.988 36.988 0 0 1 -32.169 28.144l-217.365 22.274-48.936-147.866 320.641 4.843z"
          fill="url(#c)"
        />
      </svg>
    </a>
    </br>
    <p v-if="needShowValidationDocument(item)" class="warning-document">O documento do remetente e/ou destinatário é obrigatório</p>

    <a
      v-if="buttonBuy(item)"
      @click="
        beforeAddCart({
          id: item.id,
          service_id: item.service_id,
          non_commercial: item.non_commercial,
        })
      "
      href="javascript:;"
      data-cy="input-buy-button"
      class="action-button -adicionar container__link"
      data-tip="Comprar"
    >
      <svg
        class="ico"
        version="1.1"
        id="pagar"
        xmlns="http://www.w3.org/2000/svg"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        x="0px"
        y="0px"
        viewBox="0 0 24 24"
        enable-background="new 0 0 24 24"
        xml:space="preserve"
      >
        <path
          d="M12,2c5.514,0,10,4.486,10,10s-4.486,10-10,10S2,17.514,2,12S6.486,2,12,2z M12,0C5.373,0,0,5.373,0,12s5.373,12,12,12
                s12-5.373,12-12S18.627,0,12,0z M16,14.083c0-2.145-2.232-2.742-3.943-3.546c-1.039-0.54-0.908-1.829,0.581-1.916
                c0.826-0.05,1.675,0.195,2.443,0.465l0.362-1.647C14.536,7.163,13.724,7.037,13,7.018V6h-1v1.067
                c-1.945,0.267-2.984,1.487-2.984,2.85c0,2.438,2.847,2.81,3.778,3.243c1.27,0.568,1.035,1.75-0.114,2.011
                c-0.997,0.226-2.269-0.168-3.225-0.54L9,16.275c0.894,0.462,1.965,0.708,3,0.727V18h1v-1.053C14.657,16.715,16.002,15.801,16,14.083
                z"
        />
      </svg>
    </a>

    <a
      v-if="
        item.status &&
        (item.status == 'released' ||
          item.status == 'posted' ||
          item.status == 'paid' ||
          item.status == 'generated' ||
          item.status == 'printed')
      "
      @click="createTicket({ id: item.id, order_id: item.order_id })"
      class="action-button -adicionar container__link"
      data-cy="input-print-button"
      data-tip="Imprimir etiqueta"
    >
      <svg
        class="ico"
        version="1.1"
        id="imprimirok"
        xmlns="http://www.w3.org/2000/svg"
        xmlns:xlink="http://www.w3.org/1999/xlink"
        x="0px"
        y="0px"
        viewBox="0 0 228.2998 219.331"
        enable-background="new 0 0 228.2998 219.331"
        xml:space="preserve"
      >
        <path
          id="imprimirok-path4"
          d="M60.1948,34.8006H130.35c5.3073,0,10.1271,2.1659,13.6165,5.6554
                c3.4895,3.4894,5.6554,8.3092,5.6554,13.6165v29.3652h21.6803c5.4433,0,10.3867,2.2215,13.9654,5.8006
                c3.579,3.579,5.8005,8.5223,5.8005,13.9657v62.1068c0,5.4434-2.2215,10.3867-5.8005,13.9655
                c-3.5787,3.579-8.5221,5.8005-13.9654,5.8005h-20.1121v17.763c0,4.5425-1.8533,8.6672-4.8385,11.6527
                c-2.9854,2.9854-7.1101,4.8384-11.6529,4.8384H55.0601c-4.5428,0-8.6674-1.8533-11.6529-4.8384
                c-2.9852-2.9855-4.8385-7.1102-4.8385-11.6527v-17.763H19.766c-5.4434,0-10.3867-2.2215-13.9655-5.8005
                C2.2215,175.6975,0,170.7542,0,165.3108V103.204c0-5.4434,2.2215-10.3867,5.8005-13.9657
                c3.5788-3.5791,8.5221-5.8006,13.9655-5.8006h21.1569V54.0725c0-5.3073,2.166-10.1271,5.6554-13.6165
                C50.0675,36.9665,54.8872,34.8006,60.1948,34.8006z M158.8788,107.7151c4.4407,0,8.0407,3.6292,8.0407,8.1062
                c0,4.4767-3.6,8.1062-8.0407,8.1062c-4.4408,0-8.0408-3.6295-8.0408-8.1062C150.838,111.3443,154.438,107.7151,158.8788,107.7151z
                M69.6444,194.894c-2.3743,0-4.299-2.2124-4.299-4.9416c0-2.7289,1.9247-4.9414,4.299-4.9414h50.7291
                c2.3743,0,4.299,2.2125,4.299,4.9414c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M69.6444,176.7205
                c-2.3743,0-4.299-2.2124-4.299-4.9416s1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2122,4.299,4.9414
                c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M136.3657,185.0768v-27.8807c0-0.4507-0.1899-0.866-0.4955-1.1716
                c-0.3055-0.3056-0.7208-0.4952-1.1715-0.4952H55.0601c-0.4507,0-0.8659,0.1896-1.1715,0.4952
                c-0.3056,0.3056-0.4952,0.7209-0.4952,1.1716v27.8807v17.763c0,0.4504,0.1896,0.8657,0.4952,1.1713
                c0.3056,0.3056,0.7208,0.4955,1.1715,0.4955h79.6386c0.4507,0,0.866-0.1899,1.1715-0.4955
                c0.3056-0.3056,0.4955-0.7209,0.4955-1.1713V185.0768L136.3657,185.0768z M149.6219,98.2624H40.9229H19.766
                c-1.351,0-2.5849,0.5581-3.4841,1.4573c-0.8991,0.8991-1.4573,2.133-1.4573,3.4843v62.1068c0,1.351,0.5582,2.5849,1.4573,3.4841
                c0.8992,0.8991,2.1331,1.4573,3.4841,1.4573h18.8027v-13.0561c0-4.5428,1.8531-8.6673,4.8385-11.653
                c2.9855-2.9851,7.1101-4.8384,11.6529-4.8384h79.6386c4.5428,0,8.6675,1.8533,11.6529,4.8384
                c2.9855,2.9857,4.8385,7.1102,4.8385,11.653v13.0561h20.1121c1.351,0,2.5849-0.5582,3.484-1.4573
                c0.8992-0.8992,1.4573-2.1331,1.4573-3.4841v-62.1068c0-1.3513-0.5581-2.5852-1.4573-3.4843
                c-0.8991-0.8992-2.133-1.4573-3.484-1.4573L149.6219,98.2624L149.6219,98.2624z M130.35,49.6252H60.1948
                c-1.2155,0-2.3258,0.5026-3.1354,1.3122c-0.8093,0.8096-1.3121,1.9199-1.3121,3.1351v29.3652h79.05V54.0725
                c0-1.2152-0.5026-2.3255-1.3121-3.1351C132.6759,50.1278,131.5653,49.6252,130.35,49.6252z"
        />
        <path
          id="imprimirok-path6"
          d="M158.8787,107.6162c2.2475,0,4.2825,0.9187,5.7555,2.4036
                c1.4729,1.4849,2.3841,3.5362,2.3841,5.8014s-0.9112,4.3165-2.3841,5.8015c-1.473,1.4849-3.508,2.4035-5.7555,2.4035
                s-4.2826-0.9186-5.7555-2.4035c-1.473-1.485-2.3841-3.5363-2.3841-5.8015c0-2.2652,0.9111-4.3165,2.3841-5.8014
                C154.5961,108.5349,156.6312,107.6162,158.8787,107.6162z M164.4944,110.1587c-1.437-1.4486-3.4225-2.3448-5.6157-2.3448
                c-2.1933,0-4.1788,0.8962-5.6158,2.3448c-1.4372,1.4489-2.3261,3.451-2.3261,5.6625c0,2.2116,0.8889,4.2137,2.3261,5.6625
                c1.437,1.4487,3.4225,2.3449,5.6158,2.3449c2.1932,0,4.1787-0.8962,5.6157-2.3449c1.4372-1.4488,2.3262-3.4509,2.3262-5.6625
                C166.8206,113.6097,165.9316,111.6076,164.4944,110.1587z"
        />
        <path
          id="imprimirok-path8"
          fill="#2BC866"
          d="M228.2998,42.8513c0,23.6661-19.1852,42.8513-42.8513,42.8513l0,0
                c-23.6661,0-42.8513-19.1852-42.8513-42.8513S161.7824,0,185.4485,0S228.2998,19.1852,228.2998,42.8513z"
        />
        <g id="imprimirok-layer1000">
          <path
            id="imprimirok-path11"
            fill="#FFFFFF"
            d="M175.6407,63.0407c0.4235,0.4236,0.9982,0.6616,1.5973,0.6616
                    c0.5992,0,1.1738-0.2381,1.5972-0.6616l30.7956-30.7956c0.4238-0.4236,0.6617-0.9981,0.6617-1.5972
                    c0-0.5993-0.2379-1.1738-0.6617-1.5974l-6.3891-6.389c-0.882-0.882-2.3123-0.8822-3.1946,0l-22.8085,22.8088l-6.3894-6.3894
                    c-0.4236-0.4236-0.9982-0.6617-1.5973-0.6617c-0.5991,0-1.1735,0.2381-1.5972,0.6617l-6.3892,6.3891
                    c-0.882,0.8822-0.882,2.3124,0,3.1946L175.6407,63.0407L175.6407,63.0407z"
          />
          <g id="imprimirok-layer1001" />
          <g id="imprimirok-layer1002" />
          <g id="imprimirok-layer1003" />
          <g id="imprimirok-layer1004" />
          <g id="imprimirok-layer1005" />
          <g id="imprimirok-layer1006" />
          <g id="imprimirok-layer1007" />
          <g id="imprimirok-layer1008" />
          <g id="imprimirok-layer1009" />
          <g id="imprimirok-layer1010" />
          <g id="imprimirok-layer1011" />
          <g id="imprimirok-layer1012" />
          <g id="imprimirok-layer1013" />
          <g id="imprimirok-layer1014" />
          <g id="imprimirok-layer1015" />
        </g>
      </svg>
    </a>

    <a
      @click="cancelOrder({ post_id: item.id, order_id: item.order_id })"
      v-if="item.status == 'released'"
      href="javascript:;"
      class="action-button -excluir container__link"
      data-cy="input-cancel-button"
      data-tip="Cancelar pedido"
    >
      <svg
        class="ico"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 383.2 500"
      >
        <title>Cancelar</title>
        <g id="Camada_2" data-name="Camada 2">
          <g id="Camada_10" data-name="Camada 10">
            <path
              class="cls-1"
              d="M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"
            />
            <path
              class="cls-1"
              d="M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
            <path
              class="cls-1"
              d="M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
            <path
              class="cls-1"
              d="M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
          </g>
        </g>
      </svg>
    </a>

    <a
      v-if="item.status && item.order_id && item.id && item.status == 'pending'"
      @click="removeCart({ id: item.id, order_id: item.order_id })"
      href="javascript:;"
      class="action-button -excluir container__link"
      data-cy="input-remove-button"
      data-tip="Remover do Carrinho de compras"
    >
      <svg
        class="ico"
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 383.2 500"
      >
        <title>Cancelar</title>
        <g id="Camada_2" data-name="Camada 2">
          <g id="Camada_10" data-name="Camada 10">
            <path
              class="cls-1"
              d="M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"
            />
            <path
              class="cls-1"
              d="M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
            <path
              class="cls-1"
              d="M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
            <path
              class="cls-1"
              d="M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"
            />
          </g>
        </g>
      </svg>
    </a>
  </div>
</template>

<script>
import { mapActions } from "vuex";
import statusMelhorEnvio from "../../utils/status";
export default {
  props: {
    item: {
      type: Object,
    },
  },
  methods: {
    ...mapActions("orders", [
      "addCart",
      "addCartSimple",
      "initLoader",
      "stopLoader",
      "setMessageModal",
      "removeCart",
      "cancelOrder",
      "payTicket",
      "cancelTicket",
      "createTicket",
      "printTicket",
    ]),
    sendCartSimple: function (data) {
      this.initLoader();
      this.addCartSimple(data)
        .then((response) => {
          const msg = [];
          msg.push(
            `Pedido #${data.id} enviado para o carrinho de compras do Melho Envio com o protocolo ${response.protocol}`
          );
          this.setMessageModal(msg);
        })
        .catch((error) => {
          this.setMessageModal(error.response.data.errors);
        })
        .finally(() => {
          this.stopLoader();
        });
    },
    cancelOrderSimple: function (data) {
      this.initLoader();
      this.cancelCart(data);
    },
    beforeAddCart: function (data) {
      this.initLoader();
      this.addCart(data)
        .then((response) => {
          if (response.success) {
            const msgErr = [];
            msgErr.push("Etiqueta #" + data.id + " comprada com sucesso.");
            this.setMessageModal(msgErr);
            return;
          }
        })
        .catch((error) => {
          this.setMessageModal(error.response.data.errors);
        })
        .finally(() => {
          this.stopLoader();
        });
    },
    buttonCart(item) {
      if (this.needShowValidationDocument(item)) {
        return false;
      }

      if (typeof item.quotation.choose_method === "undefined") {
        return false;
      }
      if (
        item.status == statusMelhorEnvio.STATUS_PENDING ||
        item.status == statusMelhorEnvio.STATUS_RELEASED ||
        item.status == statusMelhorEnvio.STATUS_DELIVERED
      ) {
        return false;
      }
      return true;
    },
    buttonBuy(item) {
      if (!item.service_id) {
        return false;
      }

      if (!item.status) {
        return false;
      }

      if (
        !(
          item.status == statusMelhorEnvio.STATUS_POSTED ||
          item.status == statusMelhorEnvio.STATUS_RELEASED ||
          item.status == statusMelhorEnvio.STATUS_CANCELED ||
          item.status == statusMelhorEnvio.STATUS_DELIVERED
        )
      ) {
        return true;
      }

      return false;
    },
    buttonCancel(item) {
      if (
        item.status == statusMelhorEnvio.STATUS_POSTED ||
        item.status == statusMelhorEnvio.STATUS_GENERATED ||
        item.status == statusMelhorEnvio.STATUS_RELEASED
      ) {
        return true;
      }
      return false;
    },
    needShowValidationDocument(item) {
      return !item.to.document && !item.to.company_document;
    },
  },
};
</script>