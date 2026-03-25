<style>
.boxBanner {
  float: left;
  width: 100%;
  margin-bottom: 1%;
}

.boxBanner img {
  float: left;
  width: 100%;
}

.lineGray:nth-child(odd) {
  background: #e1e1e1;
}

.text-center {
  text-align: center;
}

.styleTableMoreInfo span {
  font-size: 16px;
}

.styleTableMoreInfo td {
  padding: 1%;
}

.error-message {
  width: 100%;
  box-sizing: border-box;
  margin: 0;
  padding: 12px 14px;
  color: #fff;
  font-weight: 600;
  font-size: 14px;
  line-height: 1.45;
  background: #d63638;
  border-radius: 4px;
  border-left: 4px solid #8b0000;
}

.scrollBox {
  overflow: auto;
  height: auto;
}
</style>

<template>
  <div class="app-pedidos">
    <div class="boxBanner">
      <img src="@images/banner-admin.jpeg" />
    </div>
    <template>
      <div>
        <div class="grid">
          <div class="col-12-12">
            <h1>Meus pedidos</h1>
          </div>
          <hr />
          <div class="col-12-12" v-show="error_message">
            <p class="error-message">{{ error_message }}</p>
          </div>
          <br />
        </div>
      </div>
    </template>

    <table border="0" class="table-box">
      <tr>
        <td>
          <h4>
            <b>Usuário:</b>
            {{ name }}
          </h4>
          <h4>
            <b>Ambiente:</b>
            {{ environment }}
          </h4>
          <h4>
            <b>Envios:</b>
            {{ limitEnabled }}/{{ limit }}
          </h4>
          <h4>
            <b>Saldo:</b>
            {{ getBalance }}
          </h4>
        </td>
      </tr>
      <tr>
        <td width="50%">
          <h3>Etiquetas</h3>
          <select v-model="status">
            <option value="all">Todas</option>
            <option value="pending">Pendente</option>
            <option value="released">Liberada</option>
            <option value="posted">Postado</option>
            <option value="delivered">Entregue</option>
            <option value="canceled">Cancelado</option>
            <option value="undelivered">Não Entregue</option>
          </select>
        </td>
        <td width="50%">
          <h3>Pedidos</h3>
          <select v-model="wpstatus">
            <option value="all">Todos</option>
            <option
              v-for="(statusName, statusKey) in statusWooCommerce"
              :key="statusKey"
              v-bind:value="statusKey"
            >
              {{ statusName }}
            </option>
          </select>
        </td>
      </tr>
    </table>

    <div
      class="table-box"
      v-if="orders.length > 0"
      :class="{ '-inative': !orders.length }"
    >
      <div class="table -woocommerce">
        <ul class="head">
          <li>
            <span>ID</span>
          </li>
          <li>
            <span>Destinatário</span>
          </li>
          <li>
            <span>Produtos</span>
          </li>
          <li>
            <span>Cotação</span>
          </li>
          <li>
            <span>Etiqueta</span>
          </li>
          <li>
            <span>Ações</span>
          </li>
        </ul>

        <ul class="body">
          <li
            v-for="(item, index) in ordersWithValidationProducts"
            :key="index"
            class="lineGray"
            style="padding: 1%"
          >
            <ul class="body-list">
              <li>
                <Id :id="item.id" :link="item.link"></Id>
              </li>
              <li>
                <Destino :to="item.to"></Destino>
              </li>
              <li>
                <template v-if="item.products">
                  <div class="scrollBox">
                    <p v-for="product in item.products">
                      {{ product.quantity }}x
                      <ProductLink :id="product.id" :name="product.name" />
                    </p>
                  </div>
                </template>
              </li>
              <li>
                <Cotacao :item="item"></Cotacao>
                <template v-if="item.protocol && item.status != null">
                  <p>
                    Protocolo:
                    <b>{{ item.protocol }}</b>
                  </p>
                  <p v-if="item.tracking != null">
                    Rastreio:
                    <ProductLink
                      :definedLink="item.link_tracking"
                      :name="item.tracking"
                    />
                  </p>
                </template>
              </li>
              <li>
                <Documentos :item="item"></Documentos>
              </li>
              <li class="-center">
                <template v-if="item.existInvalidProduct">
                  <p style="color: red; font-size: larger">Esse pedido possui produtos inválidos!</p>
                </template>
                <template v-else>
                  <Acoes :item="item"></Acoes>
                </template>
              </li>
            </ul>
            <template v-if="toggleInfo == item.id">
              <informacoes
                v-if="quotationVolume(item)"
                :item="item"
                :volume="quotationVolume(item)"
                :products="item.products"
              ></informacoes>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <div v-else>
      <p>Nenhum registro encontrado</p>
    </div>
    <button
      v-show="show_more"
      class="btn-border -full-green"
      @click="loadMore({ status: status, wpstatus: wpstatus })"
    >
      Carregar mais
    </button>

    <transition name="wpme-modal-fade">
      <div
        v-show="show_modal || show_modal2"
        class="wpme_modal_overlay"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="
          modalIsSuccess
            ? 'wpme-pedidos-modal-title-success'
            : 'wpme-pedidos-modal-title-alert'
        "
      >
        <div class="wpme_modal_card wpme_modal_card--wide" @click.stop>
          <div
            class="wpme_modal_icon"
            :class="
              modalIsSuccess ? 'wpme_modal_icon--success' : 'wpme_modal_icon--alert'
            "
            aria-hidden="true"
          >
            <svg
              v-if="modalIsSuccess"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M8 12l2.5 2.5 5-5" />
            </svg>
            <svg
              v-else
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
              <line x1="12" y1="9" x2="12" y2="13" />
              <line x1="12" y1="17" x2="12.01" y2="17" />
            </svg>
          </div>
          <h2
            :id="
              modalIsSuccess
                ? 'wpme-pedidos-modal-title-success'
                : 'wpme-pedidos-modal-title-alert'
            "
            class="wpme_modal_title"
            :class="{ 'wpme_modal_title--alert': !modalIsSuccess }"
          >
            {{ modalIsSuccess ? "Sucesso" : "Atenção" }}
          </h2>
          <div class="wpme_modal_body">
            <p
              v-for="(msg, idx) in msgModalPrimaryList"
              :key="'m-' + idx"
              class="wpme_modal_message"
            >
              {{ msg }}
            </p>
            <p
              v-for="(msg, idx) in msg_modal2"
              :key="'m2-' + idx"
              class="wpme_modal_message"
            >
              {{ msg }}
            </p>
          </div>
          <div v-if="btnClose" class="wpme_modal_actions">
            <button
              type="button"
              class="btn-border -full-blue -big wpme_modal_btn"
              @click="close"
            >
              Fechar
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="wpme-modal-fade">
      <div v-show="show_loader" class="wpme_modal_overlay" aria-busy="true" aria-live="polite">
        <div class="wpme_modal_card wpme_modal_card--loading">
          <div class="wpme_modal_spinner">
            <svg
              width="88"
              height="88"
              viewBox="0 0 44 44"
              xmlns="http://www.w3.org/2000/svg"
              stroke="#0550a0"
            >
              <g fill="none" fill-rule="evenodd" stroke-width="2">
                <circle cx="22" cy="22" r="1">
                  <animate
                    attributeName="r"
                    begin="0s"
                    dur="1.8s"
                    values="1; 20"
                    calcMode="spline"
                    keyTimes="0; 1"
                    keySplines="0.165, 0.84, 0.44, 1"
                    repeatCount="indefinite"
                  />
                  <animate
                    attributeName="stroke-opacity"
                    begin="0s"
                    dur="1.8s"
                    values="1; 0"
                    calcMode="spline"
                    keyTimes="0; 1"
                    keySplines="0.3, 0.61, 0.355, 1"
                    repeatCount="indefinite"
                  />
                </circle>
                <circle cx="22" cy="22" r="1">
                  <animate
                    attributeName="r"
                    begin="-0.9s"
                    dur="1.8s"
                    values="1; 20"
                    calcMode="spline"
                    keyTimes="0; 1"
                    keySplines="0.165, 0.84, 0.44, 1"
                    repeatCount="indefinite"
                  />
                  <animate
                    attributeName="stroke-opacity"
                    begin="-0.9s"
                    dur="1.8s"
                    values="1; 0"
                    calcMode="spline"
                    keyTimes="0; 1"
                    keySplines="0.3, 0.61, 0.355, 1"
                    repeatCount="indefinite"
                  />
                </circle>
              </g>
            </svg>
          </div>
          <p class="wpme_modal_loading_text">Carregando…</p>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";
import Id from "./Pedido/Id.vue";
import Destino from "./Pedido/Destino.vue";
import Cotacao from "./Pedido/Cotacao.vue";
import Documentos from "./Pedido/Documentos.vue";
import Acoes from "./Pedido/Acoes.vue";
import ProductLink from "./ProductLink.vue";
import Informacoes from "./Pedido/Informacoes.vue";
import {verifyToken, getToken, isDateTokenExpired} from 'admin/utils/token-utils';
import {
  getErrorMessagesFromCatch,
  buildCartSuccessMessage,
} from "admin/utils/api-errors";

export default {
  name: "Pedidos",
  data: () => {
    return {
      status: "all",
      wpstatus: "all",
      line: 0,
      toggleInfo: null,
      error_message: null,
      orderSelecteds: [],
      allSelected: false,
      name: null,
      environment: null,
      limit: 0,
      limitEnabled: 0,
      totalOrders: 0,
      totalCart: 0,
      show_modal2: false,
      msg_modal2: [],
      modal2_tone: "alert",
      btnClose: true,
    };
  },
  components: {
    Id,
    Cotacao,
    Destino,
    Documentos,
    Acoes,
    Informacoes,
    ProductLink,
  },
  computed: {
    ...mapGetters("orders", {
      orders: "getOrders",
      show_loader: "toggleLoader",
      msg_modal: "setMsgModal",
      show_modal: "showModal",
      show_more: "showMore",
      statusWooCommerce: "statusWooCommerce",
      modal_tone: "modalTone",
    }),
    ...mapGetters("balance", ["getBalance"]),
    ordersWithValidationProducts() {
      return this.orders.map((order) => {
        const products = Object.values(order.products);
        order.existInvalidProduct = products.some(product => product.type === 'invalid');

        return order;
      });
    },
    /** msg_modal no Vuex pode ser string ou array */
    msgModalPrimaryList() {
      const m = this.msg_modal;
      if (m == null || m === "") {
        return [];
      }
      return Array.isArray(m) ? m : [m];
    },
    modalIsSuccess() {
      if (this.show_modal) {
        return this.modal_tone === "success";
      }
      if (this.show_modal2) {
        return this.modal2_tone === "success";
      }
      return false;
    },
  },
  methods: {
    ...mapActions("orders", [
      "retrieveMany",
      "loadMore",
      "closeModal",
      "getStatusWooCommerce",
      "printMultiples",
      "updateQuotation",
      "addCart",
      "showErrorAlert",
    ]),
    ...mapActions("balance", ["setBalance"]),
    handleToggleInfo(id) {
      this.toggleInfo = this.toggleInfo != id ? id : null;
    },
    quotationVolume(order) {
      const q = order && order.quotation;
      if (!q || q.choose_method == null) return null;
      const method = q[q.choose_method];
      if (!method || !method.volumes || !method.volumes.length) return null;
      return method.volumes[0];
    },
    goToTokenIfNeeded() {
      if (this.$route.name !== "Token") {
        this.$router.push({ name: "Token" }).catch(() => {});
      }
    },
    getToken() {
      this.$http
        .get(
          verifyToken()
        )
        .then((response) => {
          if (!response.data.exists_token) {
            this.goToTokenIfNeeded();
          }

          this.validateToken();
        });
    },
    selectAll: function () {
      if (!this.$refs.selectAllBox.checked) {
        this.orderSelecteds = [];
        this.orders.filter((order) => {
          this.$refs[order.id][0].checked = false;
        });
        return;
      }
      let selecteds = [];
      this.orders.filter((order) => {
        selecteds.push(order);
        this.$refs[order.id][0].checked = true;
      });
      this.orderSelecteds = selecteds;
    },
    beforePrintMultiples: function () {
      this.msg_modal2.length = 0;
      let selecteds = [];
      let not = [];
      let messagePrint = [];

      this.orders.filter((order) => {
        if (
          this.$refs[order.id][0].checked &&
          (order.status == "posted" ||
            order.status == "released" ||
            order.status == "paid" ||
            order.status == "generated" ||
            order.status == "printed")
        ) {
          selecteds.push(order.id);
        }

        if (order.status == null) {
          this.$refs[order.id][0].checked = false;
          not.push(order.id);
        }
      });

      if (selecteds.length == 0) {
        this.msg_modal2.push("Nenhuma etiqueta disponível para imprimir");
        this.show_modal2 = true;
        return;
      }

      this.orderSelecteds = selecteds;
      this.notCanPrint = not;

      this.msg_modal2.length = 0;
      this.printMultiples({
        orderSelecteds: selecteds,
        message: messagePrint[0],
      });
    },
    alertMessage: function (data) {
      let stringMessage;
      data.filter((item) => {
        this.msg_modal2.push(item);
      });
      this.show_modal2 = true;
    },
    getSelectedOrders() {
      const orders = [];
      this.orders.filter((order) => {
        if (this.$refs[order.id][0].checked && order.status == null) {
          orders.push(order);
        }
      });
      return orders;
    },
    async beforeBuyOrders() {
      this.show_modal2 = true;
      this.modal2_tone = "alert";
      this.btnClose = false;
      const orderSelected = this.getSelectedOrders();

      if (orderSelected.length == 0) {
        this.show_modal2 = false;
        this.msg_modal2.length = 0;
        return;
      }

      let hadError = false;
      for (const idx in orderSelected) {
        const ok = await this.dispatchCart(orderSelected[idx]);
        if (!ok) {
          hadError = true;
        }
      }
      this.modal2_tone = hadError ? "alert" : "success";
      this.btnClose = true;
    },
    countOrdersEnabledToBuy: function () {
      let total = 0;
      this.orders.filter((order) => {
        if (this.$refs[order.id][0].checked && order.status == null) {
          total++;
        }
      });
      return total;
    },

    dispatchCart: function (order) {
      this.msg_modal2.push("Enviando pedido ID" + order.id + ". Aguarde ...");

      return new Promise((resolve, reject) => {
        let data = {
          id: order.id,
          service_id: order.quotation.choose_method,
          choosen: order.quotation.choose_method,
          non_commercial: order.non_commercial,
        };

        setTimeout(() => {
          this.addCart(data)
            .then((response) => {
              buildCartSuccessMessage(order.id, response).forEach((line) => {
                this.msg_modal2.push(line);
              });
              resolve(true);
            })
            .catch((error) => {
              this.msg_modal2.push(
                "Erro ao enviar o pedido ID " + order.id + "."
              );
              this.btnClose = true;
              getErrorMessagesFromCatch(error).forEach((msg) => {
                this.msg_modal2.push("ID " + order.id + ": " + msg);
              });
              this.btnClose = true;
              resolve(false);
            });
        }, 100);
      });
    },
    getMe() {
      this.$http
        .get(`${ajaxurl}?action=me&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_users}`)
        .then((response) => {
          if (response.data.id) {
            this.name = response.data.firstname + " " + response.data.lastname;
            this.environment = response.data.environment;
            this.limit = response.data.limits.shipments;
            this.limitEnabled = response.data.limits.shipments_available;
          }
        });
    },
    close() {
      this.show_modal2 = false;
      this.msg_modal2.length = 0;
      this.modal2_tone = "alert";
      this.closeModal();
    },
    validateToken() {
      this.$http
        .get(
          getToken()
        )
        .then((response) => {
          const env = response.data.token_environment || "production";
          if (env === "sandbox") {
            if (response.data.token_sandbox) {
              this.error_message = "";
            } else {
              this.goToTokenIfNeeded();
            }
            return;
          }
          if (response.data.token) {
            if (isDateTokenExpired(response.data.token)) {
              this.error_message =
                "Seu Token Melhor Envio expirou, cadastre um novo token para o plugin voltar a funcionar perfeitamente";
            } else {
              this.error_message = "";
            }
          } else {
            this.goToTokenIfNeeded();
          }
        });
    },
  },
  watch: {
    status() {
      this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
    },
    wpstatus() {
      this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
    },
  },
  mounted() {
    this.getToken();
    this.getMe();
    if (Object.keys(this.orders).length === 0) {
      this.retrieveMany({ status: this.status, wpstatus: this.wpstatus });
    }
    this.setBalance();
    this.getStatusWooCommerce();
  },
};
</script>

<style lang="css" scoped>
</style>
