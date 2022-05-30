<style lang="css">
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
      <img
        src="@images/banner-admin.jpeg"
      />
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

    <template v-if="originData.length > 0">
      <div class="wpme_config">
        <h2>Seleciona a origem dos envios</h2>
        <div class="wpme_flex">
          <ul class="wpme_address">
            <li
              v-for="option in originData"
              v-bind:value="option.id"
              :key="option.id">
              <label :for="option.id">
                <div class="wpme_address-top">
                  <input
                    type="radio"
                    :id="option.id"
                    name="input_address"
                    :value="option.address.id"
                    v-model="origin"
                    data-cy="address-input"
                    @click="
                      refreshAgencies({
                        city: option.address.city,
                        state: option.address.state,
                      }),
                        setOrigin(option.id)
                    "
                  />
                  <h2>{{ option.address.label }}</h2>
                </div>
                <div class="wpme_address-body">
                  <ul>
                    <li v-if="option.document">
                      <b>CPF:</b>
                      {{ `${option.document}` }}
                    </li>
                    <li v-if="option.company_document">
                      <b>CNPJ:</b>
                      {{ `${option.company_document}` }}
                    </li>
                    <li v-if="option.state_register">
                      <b>Registro estadual:</b>
                      {{ `${option.state_register}` }}
                    </li>
                    <li v-if="option.economic_activity_code">
                      <b>CNAE:</b>
                      {{ `${option.economic_activity_code}` }}
                    </li>
                    <li>
                      <b>Endereço:</b>
                      {{
                        `${option.address.address}, ${option.address.number}`
                      }}
                    </li>
                    <li>
                      {{
                        `${option.address.district} - ${option.address.city}/${option.address.state}`
                      }}
                    </li>
                    <li v-if="option.address.complement">
                      {{ `${option.address.complement}` }}
                    </li>
                    <li>
                      <b>CEP:</b>
                      {{ `${option.address.postal_code}` }}
                    </li>
                  </ul>
                </div>
              </label>
            </li>
          </ul>
        </div>
      </div>
      <hr />
    </template>

    <template>
      <div class="wpme_config" style="width: 50%">
        <h2>Informações da etiqueta</h2>
        <p>
          As informações abaixo serão exibidas na etiqueta impressa do Melhor
          Envio
        </p>
        <div class="wpme_flex">
          <ul class="wpme_address">
            <li>
              <span>Nome</span></br>
              <input v-model="label.name" data-cy="input-name" type="text" />
              <br />
              <br />

              <span>E-mail</span></br>
              <input v-model="label.email" data-cy="input-email" type="text" />
              <br />
              <br />

              <span>Telefone</span></br>
              <the-mask
                v-model="label.phone"
                :mask="['(##) ####-####', '(##) #####-####']"
              />
              <br />
              <br />

              <span>CPF</span></br>
              <the-mask v-model="label.document" :mask="['###.###.###-##']" />
              <br />
              <br />

              <span>CNPJ</span></br>
              <the-mask
                v-model="label.company_document"
                :mask="['##.###.###/####-##']"
              />
              <br />
              <br />

              <span>Inscrição estadual</span></br>
              <input
                v-model="label.state_register"
                data-cy="input-state_register"
                type="text"
              />
              <br />
              <br />

              <span>CNAE</span></br>
              <input
                v-model="label.economic_activity_code"
                data-cy="input-economic_activity_code"
                type="text"
              />
              <br />
              <br />

              <input
                v-if="label.address"
                v-model="label.address"
                type="hidden"
              />
             <input
                v-if="label.complement"
                v-model="label.complement"
                type="hidden"
              />
              <input
                v-if="label.complement"
                v-model="label.complement"
                type="hidden"
              />
              <input v-if="label.number" v-model="label.number" type="hidden" />
              <input
                v-if="label.district"
                v-model="label.district"
                type="hidden"
              />
              <input v-if="label.city" v-model="label.city" type="hidden" />
              <input v-if="label.state" v-model="label.state" type="hidden" />
              <input
                v-if="label.country_id"
                v-model="label.country_id"
                type="hidden"
              />
              <input
                v-if="label.postal_code"
                v-model="label.postal_code"
                type="hidden"
              />
            </li>
          </ul>
        </div>
      </div>
      <hr />
    </template>

    <div class="wpme_config" v-show="agencies.length > 0">
      <h2>Jadlog</h2>
      <p>
        Escolha a agência Jadlog de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <template>
              <select
                name="agencies"
                id="agencies"
                v-model="agency"
                data-cy="input-agency-jadlog"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agencies"
                  :value="option.id"
                  :key="option.id"
                  :selected="option.selected"
                >
                  <strong>{{ option.name }}</strong>
                </option>
              </select>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div
      v-show="token_environment == 'production' && agenciesAzul.length > 0"
      class="wpme_config"
    >
      <h2>Azul Cargo Express</h2>
      <p>
        Escolha a agência Azul Cargo Express de sua preferência para realizar o
        envio dos seus produtos.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <template>
              <select
                name="agenciesAzul"
                id="agenciesAzul"
                v-model="agency_azul"
                data-cy="input-agency-azul"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesAzul"
                  :value="option.id"
                  :key="option.id"
                  :selected="option.selected"
                >
                  <strong>{{ option.name }}</strong>
                </option>
              </select>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div
      v-show="token_environment == 'production' && agenciesLatam.length > 0"
      class="wpme_config"
    >
      <h2>LATAM Cargo</h2>
      <p>
        Escolha a unidade Latam Cargo de sua preferência para realizar o envio
        dos seus produtos.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <template>
              <select
                name="agenciesLatam"
                id="agenciesLatam"
                v-model="agency_latam"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesLatam"
                  :value="option.id"
                  :key="option.id"
                  :selected="option.selected"
                >
                  <strong>{{ option.name }}</strong>
                </option>
              </select>
            </template>
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div class="wpme_config">
      <h2>Opções para cotação</h2>
      <p>
        As opções abaixo são serviços adicionais oferecido junto com a entrega,
        taxas extras serão adicionados no calculo de entrega por cada opção
        selecionada.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <input
              type="checkbox"
              value="Personalizar"
              data-cy="receipt"
              v-model="options_calculator && options_calculator.receipt"
            />
            Aviso de recebimento
          </li>
          <li>
            <input
              type="checkbox"
              value="Personalizar"
              data-cy="own_hand"
              v-model="options_calculator && options_calculator.own_hand"
            />
            Mão própria
          </li>
          <li>
            <input
              type="checkbox"
              value="Personalizar"
              data-cy="insurance_value"
              v-model="options_calculator && options_calculator.insurance_value"
            />
            Assegurar sempre
          </li>
        </ul>
      </div>
    </div>
    <hr />

    <div class="wpme_config">
      <h2>Embalagem padrão</h2>
      <p>
        Configure uma embalagem padrão para quando o seu produto não possuir alguma das dimensões ou peso.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
              <span>Largura</span></br>
              <input v-model="dimension.width" data-cy="input-width-default" type="number" />
              <br />
              <br />

              <span>Altura</span></br>
              <input v-model="dimension.height" data-cy="input-heigt-default" type="number" />
              <br />
              <br />

              <span>Comprimento</span></br>
              <input v-model="dimension.length" data-cy="input-length-default" type="number" />
              <br />
              <br />

              <span>Peso</span></br>
              <input v-model="dimension.weight" data-cy="input-weight-default" type="number" />
              <br />
              <br />
          </li>
        </ul>
      </div>
      <hr />
    </div>

    <div class="wpme_config">
      <h2>Calculadora</h2>
      <p>
        Ao habilitar essa opção, será exibida a calculadora de fretes com
        cotações do Melhor Envio na tela do produto.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <label for="41352">
              <div class="wpme_address-top" style="border-bottom: none">
                <label for="two">exibir a calculadora na tela do produto</label>
              </div>
              <select
                data-cy="input-where-calculator"
                name="agencies"
                id="agencies"
                v-model="where_calculator"
              >
                <option
                  v-for="option in keysWhereCalculatorCollect"
                  :value="option"
                  :key="option"
                >
                  <strong>{{ where_calculator_collect[option] }}</strong>
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
      <p>
        É necessário informar o ID do produto para o shortcode funcionar de
        forma adequada
      </p>
    </div>
    <hr />

    <div class="wpme_config" style="width: 50%">
      <h2>Diretório dos plugins</h2>
      <p>
        Em algumas instâncias do wordpress, o caminho do diretório de plugins
        pode ser direferente, ocorrendo falhas no plugin, sendo necessário
        definir o caminho manualmente no campo abaixo. Tome cuidado ao realizar
        essa ação.
      </p>
      <div class="wpme_flex">
        <ul class="wpme_address">
          <li>
            <input
              type="checkbox"
              value="Personalizar"
              v-model="show_path"
              data-cy="input-show-path"
            />
            <span>Estou ciente dos riscos</span>
            <br />
            <br />
            <input
              v-show="show_path"
              v-model="path_plugins"
              data-cy="input-path"
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
            <button type="button" @click="close" class="btn-border -full-blue">
              Fechar
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div class="me-modal" v-show="show_load">
      <svg
        style="float: left; margin-top: 10%; margin-left: 50%"
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
import { TheMask } from "vue-the-mask";
import { where_calculator_collect } from "admin/utils/where-calculator_collect";
import {
  verifyToken,
  getToken,
  isDateTokenExpired,
} from "admin/utils/token-utils";
import deleteSession from "admin/utils/delete-session";

export default {
  name: "Configuracoes",
  components: { Money, TheMask },
  data() {
    return {
      error_message: null,
      canUpdate: true,
      origin: null,
      agency: null,
      agency_azul: null,
      agency_latam: null,
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
      where_calculator_collect,
    };
  },
  computed: {
    ...mapGetters("configuration", {
      originData: "getOrigin",
      label: "getLabel",
      dimension: "getDimension",
      agencySelected_: "getAgencySelected",
      agencyAzulSelected_: "getAgencyAzulSelected",
      agencyLatamSelected_: "getAgencyLatamSelected",
      agencies: "getAgencies",
      agenciesAzul: "getAgenciesAzul",
      agenciesLatam: "getAgenciesLatam",
      allAgencies: "getAllAgencies",
      style_calculator: "getStyleCalculator",
      methods_shipments: "getMethodsShipments",
      show_load: "showLoad",
      path_plugins_: "getPathPlugins",
      where_calculator_: "getWhereCalculator",
      show_calculator_: "getShowCalculator",
      show_all_agencies_jadlog_: "getShowAllJadlogAgencies",
      options_calculator_: "getOptionsCalculator",
      token_environment: "getEnvironment",
      configs: "getConfigs",
    }),
    keysWhereCalculatorCollect() {
      return Object.keys(this.where_calculator_collect);
    },
  },
  methods: {
    ...mapActions("configuration", [
      "getConfigs",
      "setLoader",
      "setAgenciesAzul",
      "setAgenciesLatam",
      "setAgencies",
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
      data["origin"] = this.origin;
      data["label"] = this.label;
      data["agency"] = this.agency;
      data["agency_azul"] = this.agency_azul;
      data["agency_latam"] = this.agency_latam;
      data["show_calculator"] = this.show_calculator;
      data["show_all_agencies_jadlog"] = this.show_all_agencies_jadlog;
      data["where_calculator"] = this.where_calculator;
      data["path_plugins"] = this.path_plugins;
      data["options_calculator"] = this.options_calculator;
      data["dimension_default"] = this.dimension;

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
    refreshAgencies(data) {
      this.showJadlogAgencies(data);
      this.showAzulAgencies(data);
      this.showALatamAgencies(data);
    },
    setOrigin(id) {
      if (this.originData.length > 0) {
        this.originData.filter((item) => {
          if (item.id == id) {
            this.label.address = item.address.address;
            this.label.complement = item.address.complement;
            this.label.number = item.address.number;
            this.label.district = item.address.district;
            this.label.city = item.address.city;
            this.label.state = item.address.state;
            this.label.country_id = item.address.country_id;
            this.label.postal_code = item.address.postal_code;
            this.label.name = item.name;
            this.label.email = item.email;
            this.label.phone = item.phone;
            this.label.document = item.document;
            this.label.company_document = item.company_document;
            this.label.state_register = item.state_register;
            this.label.economic_activity_code = item.economic_activity_code;
          }
        });
      }
    },
    createAjaxUrl(agencyId, data) {
      const { city, state } = data;
      return `${ajaxurl}?action=get_agencies&company=${agencyId}&city=${city}&state=${state}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_configs}`;
    },
    showJadlogAgencies(data) {
      this.setLoader(true);
      this.agency = "";
      var responseAgencies = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(2, data))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgencies = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            console.log(error);
            responseAgencies = [];
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
      var responseAgenciesAzul = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(9, data))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesAzul = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesAzul([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesAzul(responseAgenciesAzul);
        this.setLoader(false);
      });
    },
    showALatamAgencies(data) {
      this.setLoader(true);
      this.agency_latam = "";
      var responseAgenciesLatam = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(6, data))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesLatam = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesLatam([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesLatam(responseAgenciesLatam);
        this.setLoader(false);
      });
    },
    close() {
      this.show_modal = false;
    },
    clearSession() {
      return new Promise((resolve, _reject) => {
        this.$http.get(deleteSession()).then((_response) => {
          resolve(true);
        });
      });
    },
    showTimeWithDay(value) {
      let val = value == 1 ? value + " dia" : value + " dias";
      return val;
    },
    getToken() {
      this.$http.get(verifyToken()).then((response) => {
        if (!response.data.exists_token) {
          this.$router.push("Token");
        }

        this.validateToken();
      });
    },
    validateToken() {
      this.$http.get(getToken()).then((response) => {
        if (response.data.token) {
          if (isDateTokenExpired(response.data.token)) {
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
  },
  watch: {
    originData() {
      if (this.originData.length > 0) {
        this.originData.filter((item) => {
          if (item.selected) {
            this.origin = item.id;
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
    agenciesLatam() {
      this.setLoader(true);
      if (this.agenciesLatam.length > 0) {
        this.agenciesLatam.filter((item) => {
          if (item.selected) {
            this.agency_latam = item.id;
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
    agencyLatamSelected_(e) {
      this.agency_latam = e;
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