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

.input {
  width: 100%;
  height: 35px;
  padding: 5px 10px;
  border: 1px solid #b0b0b0;
}
.group-input {
  border: 1px solid #b0b0b0;
  width: 100%;
  font-size: 15px;
}
.group-input input {
  display: inline-block;
  border: none;
  width: 90%;
  max-width: 455px;
  font-size: 15px;
}
.group-input p {
  display: inline-block;
  margin: 0 auto;
  padding: 7px 10px;
  background-color: #f0f0f0;
  width: 5.5%;
  text-align: center;
}
.store-box {
  display: inline-flex !important;
  min-height: 100px;
  max-width: 25% !important;
}
.store-box h3 {
  display: inline-flex;
  padding: 0 0 0 8px;
  margin: 0 0 15px;
  text-align: left;
  font-size: 1.1em;
  max-width: 85%;
  font-weight: 300;
}
.box-buttons {
  min-height: 40px;
}
.wpme_address .wpme_address-top {
  min-height: 45px !important;
}
.wpme_address-top .title-methods {
  text-align: left;
  font-size: 1.1em;
  font-weight: 300;
  margin: 0;
}
.error-message {
  width: 98%;
  padding: 10px 0 10px 2%;
  color: #fff;
  font-weight: 600;
}
</style>

<template>
  <div>
    <div class="boxBanner">
      <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/banner-admin.png" />
    </div>

    <template>
      <div>
        <div class="grid">
          <div class="col-12-12">
            <h1>Configurações gerais</h1>
          </div>
          <hr />
          <div class="col-12-12" v-show="error_message">
            <p class="error-message">{{ error_message }}</p>
          </div>
          <br />
        </div>
      </div>
    </template>

    <div class="wpme_config">
      <h2>Endereço</h2>
      <p>Escolha o endereço para cálculo de frete, esse endereço será utlizado para realizar as cotações.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li v-for="option in addresses" v-bind:value="option.id" :key="option.id">
            <label :for="option.id">
              <div class="wpme_address-top">
                <input
                  type="radio"
                  :id="option.id"
                  :value="option.id"
                  v-model="address"
                  @click="showJadlogAgencies({city: option.city, state: option.state})"
                />
                <h2>{{option.label}}</h2>
              </div>
              <div class="wpme_address-body">
                <ul>
                  <li>
                    <b>Endereço:</b>
                    {{ `${option.address}, ${option.number}` }}
                  </li>
                  <li>{{ `${option.district} - ${option.city}/${option.state}` }}</li>
                  <li v-if="option.complement">{{ `${option.complement}` }}</li>
                  <li>
                    <b>CEP:</b>
                    {{ `${option.postal_code}` }}
                  </li>
                </ul>
              </div>
            </label>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div class="wpme_config">
      <h2>Jadlog</h2>
      <p>Escolha a agência Jadlog de sua preferência para realizar o envio dos seus produtos.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <div class="wpme_address-top" style="border-bottom: none;">
              <input
                type="checkbox"
                class="show-all-agencies"
                id="show-all-agencies"
                v-model="show_all_agencies_jadlog"
                @change="showJadlogAgenciesState()"
              />
              <label for="show-all-agencies">Desejo visualizar todas as agencias do meu estado</label>
            </div>
            <br />
            <template>
              <select name="agencies" id="agencies" v-model="agency">
                <option value>Selecione...</option>
                <option
                  v-for="option in agencies"
                  :value="option.id"
                  :key="option.id"
                  :selected="option.selected"
                >
                  <strong>{{option.name}}</strong>
                </option>
              </select>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div v-show="token_environment == 'production'" class="wpme_config">
      <h2>Azul Cargo Express</h2>
      <p>Escolha a agência Azul Cargo Express de sua preferência para realizar o envio dos seus produtos.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <template>
              <select name="agenciesAzul" id="agenciesAzul" v-model="agency_azul">
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesAzul"
                  :value="option.id"
                  :key="option.id"
                  :selected="option.selected"
                >
                  <strong>{{option.name}}</strong>
                </option>
              </select>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <template v-if="stores.length > 0">
      <div class="wpme_config">
        <h2>Loja</h2>
        <p>Escolha qual a sua loja padrão dentre as suas lojas cadastradas no Melhor Envio. A etiqueta será gerada com base nas informações da loja selecionada.</p>
        <small>Esse endereço será exibido na etiqueta do Melhor Envio.</small> </br></br>
        <div class="wpme_flex">
          <ul class="wpme_address">
            <li
              v-for="option in stores"
              v-bind:value="option.id"
              :key="option.id"
              class="store-box"
            >
              <label :for="option.id">
                <div class="wpme_address-top">
                  <input @click="showJadlogAgencies({
                        city: option.address.city.city, 
                        state: option.address.city.state.state_abbr
                        })" 
                    type="radio" :id="option.id" :value="option.id" v-model="store" />
                  <h3>{{option.name}}</h3>
                </div>
                <div class="wpme_address-body">
                  <ul>
                    <li v-if="option.document">
                      <b>CNPJ:</b>
                      {{ `${option.document}` }}
                    </li>
                    <li v-if="option.state_register">
                      <b>Inscrição estadual:</b>
                      {{ `${option.state_register}` }}
                    </li>
                    <li v-if="option.email">
                      <b>E-mail:</b>
                      {{ `${option.email} ` }}
                    </li>
                    <template v-if="option.address">
                        <li v-if="option.address.label">
                            <b>Identificação:</b>
                            {{ `${option.address.label} ` }}
                        </li>
                        <li v-if="option.address.address && option.address.number">
                            <b>Endereço:</b>
                            {{ `${option.address.address}, ${option.address.number} ` }}
                        </li>
                        <li v-if="option.address.city && option.address.city.city && option.address.city.state.state_abbr">
                            {{ `${option.address.city.city}/${option.address.city.state.state_abbr} ` }}
                        </li>
                        <li v-if="option.address.postal_code">
                            {{ `CEP: ${option.address.postal_code}` }}
                        </li>
                    </template>
                  </ul>
                </div>
              </label>
            </li>
          </ul>
        </div>
      </div>
      <hr />
    </template>

    <div class="wpme_config">
      <h2>Opções para cotação</h2>
      <p>As opções abaixo são serviços adicionais oferecido junto com a entrega, taxas extras serão adicionados no calculo de entrega por cada opção selecionada.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <input type="checkbox" value="Personalizar" data-cy="receipt" v-model="options_calculator.receipt" />
            Aviso de recebimento
          </li>
          <li>
            <input type="checkbox" value="Personalizar" data-cy="own_hand" v-model="options_calculator.own_hand" />
            Mão própria
          </li>
          <li>
            <input type="checkbox" value="Personalizar" data-cy="insurance_value" v-model="options_calculator.insurance_value" />
            Assegurar sempre 
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div class="wpme_config">
      <h2>Calculadora</h2>
      <p>Ao habilitar essa opção, será exibida a calculadora de fretes com cotações do Melhor Envio na tela do produto.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <label for="41352">
              <div class="wpme_address-top" style="border-bottom: none;">
                <input type="checkbox" value="exibir" v-model="show_calculator" />
                <label for="two">exibir a calculadora na tela do produto</label>
              </div>
              <br />

              <select
                v-show="show_calculator"
                name="agencies"
                id="agencies"
                v-model="where_calculator"
              >
                <option
                  v-for="option in where_calculator_collect"
                  :value="option.id"
                  :key="option.id"
                >
                  <strong>{{option.name}}</strong>
                </option>
              </select>
            </label>
          </li>
        </ul>
      </div>
      <hr />
      <h2></h2>
      <h3>Shortcode para exibir a calculadora</h3>
      <p>
        <b>[calculadora_melhor_envio product_id="product_id"]</b>
      </p>
      <p>É necessário informar o ID do produto para o shortcode funcionar de forma adequada</p>
    </div>
    <hr />

    <div class="wpme_config" style="width:50%;">
      <h2>Diretório dos plugins</h2>
      <p>Em algumas instâncias do wordpress, o caminho do diretório de plugins pode ser direferente, ocorrendo falhas no plugin, sendo necessário definir o caminho manualmente no campo abaixo. Tome cuidado ao realizar essa ação.</p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <input type="checkbox" value="Personalizar" v-model="show_path" />
            <span>Estou ciente dos riscos</span>
            <br />
            <br />
            <input
              v-show="show_path"
              v-model="path_plugins"
              type="text"
              placeholder="/home/htdocs/html/wp-content/plugins"
            />
            <br />
            <br />
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <button class="btn-border -blue" @click="updateConfig">salvar</button>

    <transition name="fade">
      <div class="me-modal" v-show="show_modal">
        <div>
          <p class="title">Sucesso!</p>
          <div class="content">
            <p class="txt">dados atualizados com sucesso!</p>
          </div>
          <div class="buttons -center">
            <button type="button" @click="close" class="btn-border -full-blue">Fechar</button>
          </div>
        </div>
      </div>
    </transition>

    <div class="me-modal" v-show="show_load">
      <svg
        style="float:left; margin-top:10%; margin-left:50%;"
        class="ico"
        width="88"
        height="88"
        viewBox="0 0 44 44"
        xmlns="http://www.w3.org/2000/svg"
        stroke="#3598dc"
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
  </div>
</template>

<script>
import { mapGetters, mapActions } from "vuex";
import { Money } from "v-money";
export default {
  name: "Configuracoes",
  components: { Money },
  data() {
    return {
      error_message: null,
      canUpdate: true,
      address: null,
      store: null,
      agency: null,
      agency_azul: null,
      show_modal: false,
      custom_calculator: false,
      show_calculator: false,
      show_all_agencies_jadlog: false,
      show_all_agencies_azul: false,
      options_calculator: {
        receipt: false,
        own_hand: true,
        insurance_value: true,
      },
      path_plugins: "",
      show_path: false,
      codeshiping: [],
      money: {
        decimal: ",",
        thousands: ".",
        precision: 2,
        masked: false,
      },
      percent: {
        decimal: ",",
        thousands: ".",
        precision: 0,
        masked: false,
      },
      where_calculator: "woocommerce_after_add_to_cart_form",
      where_calculator_collect: [
        {
          id: "woocommerce_before_single_product",
          name: "Antes do titulo do produto (Depende do tema do projeto)",
        },
        {
          id: "woocommerce_after_single_product",
          name: "Depois do titulo do produto",
        },
        {
          id: "woocommerce_single_product_summary",
          name: "Antes da descrição do produto",
        },
        {
          id: "woocommerce_before_add_to_cart_form",
          name: "Antes do fórmulario de comprar",
        },
        {
          id: "woocommerce_before_variations_form",
          name: "Antes das opçoes do produto",
        },
        {
          id: "woocommerce_before_add_to_cart_button",
          name: "Antes do botão de comprar",
        },
        {
          id: "woocommerce_before_single_variation",
          name: "Antes do campo de variações",
        },
        {
          id: "woocommerce_single_variation",
          name: "Antes das variações",
        },
        {
          id: "woocommerce_after_add_to_cart_form",
          name: "Depois do botão de comprar",
        },
        {
          id: "woocommerce_product_meta_start",
          name: "Antes das informações do produto",
        },
        {
          id: "woocommerce_share",
          name: "Depois dos botões de compartilhamento",
        },
      ],
    };
  },
  computed: {
    ...mapGetters("configuration", {
      addresses: "getAddress",
      stores: "getStores",
      agencySelected_: "getAgencySelected",
      agencyAzulSelected_: "getAgencyAzulSelected",
      agencies: "getAgencies",
      agenciesAzul: "getAgenciesAzul",
      allAgencies: "getAllAgencies",
      style_calculator: "getStyleCalculator",
      methods_shipments: "getMethodsShipments",
      show_load: "showLoad",
      path_plugins_: "getPathPlugins",
      where_calculator_: "getWhereCalculator",
      show_calculator_: "getShowCalculator",
      show_all_agencies_jadlog_: "getShowAllJadlogAgencies",
      show_all_agencies_azul_: "getShowAllAzulAgencies",
      options_calculator_: "getOptionsCalculator",
      token_environment: "getEnvironment",
      configs: "getConfigs",
    }),
  },
  methods: {
    ...mapActions("configuration", [
      "getConfigs",
      "setLoader",
      "setAgenciesAzul",
      "saveAll",
      "getEnvironment",
    ]),
    requiredInput(element) {
      if (element.length == 0 || element.length > 100) {
        this.canUpdate = false;
      } else {
        this.canUpdate = true;
      }
    },
    closeShowModalEditMethod() {
      this.getServicesCodesstatus();
    },
    updateConfig() {
      this.setLoader(true);
      let data = new Array();
      data["address"] = this.address;
      data["store"] = this.store;
      data["agency"] = this.agency;
      data["agency_azul"] = this.agency_azul;
      data["show_calculator"] = this.show_calculator;
      data["show_all_agencies_jadlog"] = this.show_all_agencies_jadlog;
      data["show_all_agencies_azul"] = this.show_all_agencies_azul;
      data["where_calculator"] = this.where_calculator;
      data["path_plugins"] = this.path_plugins;
      data["options_calculator"] = this.options_calculator;

      let respSave = this.saveAll(data);

      respSave
        .then((resolve) => {
          this.setLoader(false);
          this.clearSession();
          this.show_modal = true;
        })
        .catch(function (erro) {
          this.setLoader(false);
        });
    },
    showJadlogAgencies(data) {
      this.setLoader(true);
      this.agency = "";
      var responseAgencies = [];
      var promiseAgencies = new Promise((resolve, reject) => {
        this.$http
          .post(
            `${ajaxurl}?action=get_agency_jadlog&city=${data.city}&state=${data.state}`
          )
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgencies = response.data.agencies;
              resolve(true);
            }
          })
          .catch((error) => {
            alert(error.response.data.message);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgencies(responseAgencies);
        this.setLoader(false);
      });
    },
    showAzulAgencies(data) {
      this.setLoader(true);
      this.agency_azul = "";
      var responseAgencies = [];
      var promiseAgencies = new Promise((resolve, reject) => {
        this.$http
          .post(
            `${ajaxurl}?action=get_agency_azul&city=${data.city}&state=${data.state}`
          )
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgencies = response.data.agencies;
              resolve(true);
            }
          })
          .catch((error) => {
            alert(error.response.data.message);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesAzul(responseAgencies);
        this.setLoader(false);
      });
    },
    close() {
      this.show_modal = false;
    },
    clearSession() {
      return new Promise((resolve, reject) => {
        this.$http
          .get(`${ajaxurl}?action=delete_melhor_envio_session`)
          .then((response) => {
            resolve(true);
          });
      });
    },
    formatNumber(value) {
      let val = (value / 1).toFixed(2).replace(".", ",");
      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    formatPercent(value) {
      let val = value / 1;
      return val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    },
    showTimeWithDay(value) {
      let val = value == 1 ? value + " dia" : value + " dias";
      return val;
    },
    getToken() {
      this.$http.get(`${ajaxurl}?action=verify_token`).then((response) => {
        if (!response.data.exists_token) {
          this.$router.push("Token");
        }

        this.validateToken();
      });
    },
    validateToken() {
      this.$http.get(`${ajaxurl}?action=get_token`).then((response) => {
        if (response.data.token) {
          var token = response.data.token;

          // JWT Token Decode
          var base64Url = token.split(".")[1];
          var base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
          var tokenDecoded = decodeURIComponent(
            atob(base64)
              .split("")
              .map(function (c) {
                return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
              })
              .join("")
          );

          var tokenFinal = JSON.parse(tokenDecoded);
          var dateExp = new Date(parseInt(tokenFinal.exp) * 1000);
          var currentTime = new Date();

          if (dateExp < currentTime) {
            this.error_message =
              "Seu Token Melhor Envio expirou, cadastre um novo token para o plugin voltar a funcionar perfeitamente";
          } else {
            this.error_message = "";
          }
        } else {
          this.$router.push("Token");
        }
      });
    },
    showJadlogAgenciesState() {
      this.setLoader(true);
      this.agency = "";
      this.$http
        .post(`${ajaxurl}?action=get_agency_jadlog&my-state=true`)
        .then((response) => {
          this.setAgencies(response.data.agencies);
        })
        .catch((error) => {
          alert(error.response.data.message);
        })
        .finally(() => {
          this.setLoader(false);
        });
    },
  },
  watch: {
    addresses() {
      if (this.addresses.length > 0) {
        this.addresses.filter((item) => {
          if (item.selected) {
            this.address = item.id;
          }
        });
      }
    },
    stores() {
      if (this.stores.length > 0) {
        this.stores.filter((item) => {
          if (item.selected) {
            this.store = item.id;
          }
        });
      }
    },
    agencies() {
      this.setLoader(true);
      if (this.agencies.length > 0) {
        this.agencies.filter((item) => {
          if (item.selected) {
            this.agency = item.id;
          }
        });
      }
      this.setLoader(false);
    },
    agenciesAzul() {
      this.setLoader(true);
      if (this.agenciesAzul.length > 0) {
        this.agenciesAzul.filter((item) => {
          if (item.selected) {
            this.agency_azul = item.id;
          }
        });
      }
      this.setLoader(false);
    },
    agencySelected_(e) {
      this.agency = e;
    },
    agencyAzulSelected_(e) {
      this.agency_azul = e;
    },
    show_calculator_(e) {
      this.show_calculator = e;
    },
    show_all_agencies_jadlog_(e) {
      this.show_all_agencies_jadlog = e;
    },
    path_plugins_(e) {
      this.path_plugins = e;
    },
    where_calculator_(e) {
      this.where_calculator = e;
    },
    options_calculator_(e) {
      this.options_calculator = e;
    },
  },
  mounted() {
    this.getToken();
    this.setLoader(true);
    var promiseConfigs = this.getConfigs();
    promiseConfigs.then((resolve) => {
      this.setLoader(false);
    });
  },
};
</script>

<style lang="css" scoped>
</style>