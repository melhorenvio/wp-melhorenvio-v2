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
</style>

<template>
  <div class="wpme_config_page">
    <div class="boxBanner">
      <img
        src="@images/banner-admin.jpeg"
      />
    </div>

    <template>
      <div class="wpme_config_panel wpme_config_panel--page-header">
        <div class="grid">
          <div class="col-12-12">
            <h1>Configurações gerais</h1>
          </div>
          <div class="col-12-12" v-show="error_message">
            <p class="error-message">{{ error_message }}</p>
          </div>
        </div>
      </div>
    </template>

    <template v-if="originData.length > 0">
      <div class="wpme_config_panel">
        <h2>Seleciona a origem dos envios</h2>
        <div class="wpme_config">
        <div class="wpme_flex">
          <ul class="wpme_address wpme_origin_cards">
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
                        latitude: option.address.latitude || '',
                        longitude: option.address.longitude || '',
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
      </div>
    </template>

    <template>
      <div class="wpme_config_row">
      <div class="wpme_config_panel wpme_config_panel--split">
        <h2>Informações da etiqueta</h2>
        <p>
          As informações abaixo serão exibidas na etiqueta impressa do Melhor
          Envio
        </p>
        <div class="wpme_config">
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
                :mask="['XX.XXX.XXX/XXXX-##', '##.###.###/####-##']"
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
      </div>

      <div class="wpme_config_panel wpme_config_panel--split">
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
      </div>
      </div>
    </template>

    <div class="wpme_config_panel">
      <h2>Seleção de agências</h2>

    <div class="wpme_config">
      <h2>Jadlog</h2>
      <p v-if="agencies.length > 0">
        Escolha a agência Jadlog de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência Jadlog encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agencies.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
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

    <div class="wpme_config">
      <h2>Jadlog Centralizado</h2>
      <p v-if="agenciesJadlogCentralized.length > 0">
        Escolha a agência Jadlog centralizado de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência Jadlog Centralizado encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesJadlogCentralized.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
            <template>
              <select
                name="agency_jadlog_centralized"
                id="agency_jadlog_centralized"
                v-model="agency_jadlog_centralized"
                data-cy="input-agency-jadlog-centralized"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesJadlogCentralized"
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
      <h2>Loggi</h2>
      <p v-if="agenciesLoggi.length > 0">
        Escolha a agência Loggi de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência Loggi encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesLoggi.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
            <template>
              <select
                name="agency_loggi"
                id="agency_loggi"
                v-model="agency_loggi"
                data-cy="input-agency-loggi"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesLoggi"
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
      <h2>JeT</h2>
      <p v-if="agenciesJeT.length > 0">
        Escolha a agência JeT de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência JeT encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesJeT.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
            <template>
              <select
                  name="agency_jet"
                  id="agency_jet"
                  v-model="agency_jet"
                  data-cy="input-agency-jet"
              >
                <option value>Selecione...</option>
                <option
                    v-for="option in agenciesJeT"
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
    v-show="token_environment == 'production'"
    class="wpme_config"
    >
      <h2>Total Express</h2>
      <p v-if="agenciesTotalExpress.length > 0">
        Escolha a agência Total Express de sua preferência para realizar o envio dos
        seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência Total Express encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesTotalExpress.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
            <template>
              <select
                name="agency_totalexpress"
                id="agency_totalexpress"
                v-model="agency_totalexpress"
                data-cy="input-agency-totalexpress"
              >
                <option value>Selecione...</option>
                <option
                  v-for="option in agenciesTotalExpress"
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
      v-show="token_environment == 'production'"
      class="wpme_config"
    >
      <h2>Azul Cargo Express</h2>
      <p v-if="agenciesAzul.length > 0">
        Escolha a agência Azul Cargo Express de sua preferência para realizar o
        envio dos seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma agência Azul Cargo Express encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesAzul.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
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
      v-show="token_environment == 'production'"
      class="wpme_config"
    >
      <h2>LATAM Cargo</h2>
      <p v-if="agenciesLatam.length > 0">
        Escolha a unidade Latam Cargo de sua preferência para realizar o envio
        dos seus produtos.
      </p>
      <p v-else class="description">
        Nenhuma unidade LATAM Cargo encontrada para esta origem. Confira o endereço
        de origem ou se existe uma agência para esta origem.
      </p>
      <div class="wpme_flex" v-show="agenciesLatam.length > 0">
        <ul class="wpme_address">
          <li class="wpme_agency_select_row">
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
    </div>

    <div class="wpme_config_panel">
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

    <div class="wpme_config_row">
      <div class="wpme_config_panel wpme_config_panel--split">
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
        <div class="wpme_config_panel__subsection">
          <h3>Shortcode para exibir a calculadora</h3>
          <p>
            <b>[calculadora_melhor_envio product_id="product_id"]</b>
          </p>
          <p>
            É necessário informar o ID do produto para o shortcode funcionar de
            forma adequada
          </p>
        </div>
      </div>

      <div class="wpme_config_panel wpme_config_panel--split">
        <h2>Diretório dos plugins</h2>
        <p>
          Em algumas instâncias do wordpress, o caminho do diretório de plugins
          pode ser direferente, ocorrendo falhas no plugin, sendo necessário
          definir o caminho manualmente no campo abaixo. Tome cuidado ao realizar
          essa ação.
        </p>
        <div class="wpme_flex wpme_config_panel__path-plugins">
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
              <textarea
                v-show="show_path"
                v-model="path_plugins"
                data-cy="input-path"
                rows="3"
                class="input wpme_config_panel__path-input"
                placeholder="/home/htdocs/html/wp-content/plugins"
              />
              <br />
              <br />
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="wpme_config_page__save-wrap">
      <button
        type="button"
        class="btn-border -full-blue -big wpme_config_page__save"
        @click="updateConfig"
      >
        salvar
      </button>
    </div>

    <transition name="wpme-modal-fade">
      <div
        v-show="show_modal"
        class="wpme_modal_overlay"
        role="dialog"
        aria-modal="true"
        aria-labelledby="wpme-success-title"
        @click.self="close"
      >
        <div class="wpme_modal_card" @click.stop>
          <div class="wpme_modal_icon wpme_modal_icon--success" aria-hidden="true">
            <svg
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
          </div>
          <h2 id="wpme-success-title" class="wpme_modal_title">
            Configurações salvas
          </h2>
          <p class="wpme_modal_text">
            Suas alterações foram aplicadas com sucesso.
          </p>
          <div class="wpme_modal_actions">
            <button
              type="button"
              class="btn-border -full-blue -big wpme_modal_btn"
              @click="close"
            >
              Entendi
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="wpme-modal-fade">
      <div v-show="show_load" class="wpme_modal_overlay" aria-busy="true" aria-live="polite">
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
      agency_jadlog_centralized: null,
      agency_loggi: null,
      agency_azul: null,
      agency_latam: null,
      agency_jet: null,
      agency_totalexpress: null,
      show_modal: false,
      custom_calculator: false,
      show_calculator: false,
      show_all_agencies_jadlog: false,
      show_all_agencies_azul: false,
      show_all_agencies_jadlog_centralized: false,
      show_all_agencies_loggi: false,
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
      agencyJadlogCentralizedSelected_: "getAgencyJadlogCentralizedSelected",
      agencyLoggiSelected_: "getAgencyLoggiSelected",
      agencyAzulSelected_: "getAgencyAzulSelected",
      agencyLatamSelected_: "getAgencyLatamSelected",
      agencyJeTSelected_: "getAgencyJeTSelected",
      agencyTotalExpressSelected_: "getAgencyTotalExpressSelected",
      agencies: "getAgencies",
      agenciesJadlogCentralized: "getAgenciesJadlogCentralized",
      agenciesCorreiosCentralized: "getAgenciesCorreiosCentralized",
      agenciesLoggi: "getAgenciesLoggi",
      agenciesAzul: "getAgenciesAzul",
      agenciesLatam: "getAgenciesLatam",
      agenciesJeT: "getAgenciesJeT",
      agenciesTotalExpress: "getAgenciesTotalExpress",
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
      "setAgenciesCorreiosCentralized",
      "setAgenciesJadlogCentralized",
      "setAgenciesLoggi",
      "setAgenciesLatam",
      "setAgenciesJeT",
      "setAgenciesTotalExpress",
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
      data["agency_correios_centralized"] = this.agency_correios_centralized;
      data["agency_jadlog_centralized"] = this.agency_jadlog_centralized;
      data["agency_loggi"] = this.agency_loggi;
      data["agency_latam"] = this.agency_latam;
      data["agency_jet"] = this.agency_jet;
      data["agency_totalexpress"] = this.agency_totalexpress;
      data["show_calculator"] = this.show_calculator;
      data["show_all_agencies_jadlog"] = this.show_all_agencies_jadlog;
      data["where_calculator"] = this.where_calculator;
      data["path_plugins"] =
        typeof this.path_plugins === "string"
          ? this.path_plugins.replace(/\r\n|\r|\n/g, "").trim()
          : this.path_plugins;
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
      this.showCorreiosCentralizedAgencies(data);
      this.showJadlogCentralizedAgencies(data);
      this.showLoggiAgencies(data);
      this.showALatamAgencies(data);
      this.showJeTAgencies(data);
      this.showTotalExpressAgencies(data);
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
    createAjaxUrl(companyId, data, serviceId = "") {
      const { city, state, latitude, longitude } = data;
      return `${ajaxurl}?action=get_agencies&company=${companyId}&city=${city}&state=${state}&latitude=${latitude}&longitude=${longitude}&serviceId=${serviceId}&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_configs}`;
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
    showLoggiAgencies(data) {
      this.setLoader(true);
      this.agency_loggi = "";
      var responseAgenciesLoggi = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(14, data, 31))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesLoggi = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesLoggi([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesLoggi(responseAgenciesLoggi);
        this.setLoader(false);
      });
    },
    showCorreiosCentralizedAgencies(data) {
      this.setLoader(true);
      this.agency_correios_centralized = "";
      var responseAgenciesCorreiosCentralized = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(1, data, 28))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesCorreiosCentralized = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesCorreiosCentralized([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesCorreiosCentralized(responseAgenciesCorreiosCentralized);
        this.setLoader(false);
      });
    },
    showJadlogCentralizedAgencies(data) {
      this.setLoader(true);
      this.agency_jadlog_centralized = "";
      var responseAgenciesJadlogCentralized = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(2, data, 27))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesJadlogCentralized = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesJadlogCentralized([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesJadlogCentralized(responseAgenciesJadlogCentralized);
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
    showJeTAgencies(data) {
      this.setLoader(true);
      this.agency_jet = "";
      var responseAgenciesJeT = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(15, data))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesJeT = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesJeT([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesJeT(responseAgenciesJeT);
        this.setLoader(false);
      });
    },
    showTotalExpressAgencies(data) {
      const companyId =
        typeof wpApiSettingsMelhorEnvio !== "undefined" &&
        wpApiSettingsMelhorEnvio.company_total_express != null
          ? wpApiSettingsMelhorEnvio.company_total_express
          : 8;
      const serviceId =
        typeof wpApiSettingsMelhorEnvio !== "undefined" &&
        wpApiSettingsMelhorEnvio.service_total_express_standard != null
          ? wpApiSettingsMelhorEnvio.service_total_express_standard
          : 35;
      this.setLoader(true);
      this.agency_totalexpress = "";
      var responseAgenciesTotalExpress = [];
      var promiseAgencies = new Promise((resolve, _reject) => {
        this.$http
          .post(this.createAjaxUrl(companyId, data, serviceId))
          .then(function (response) {
            if (response && response.status === 200) {
              responseAgenciesTotalExpress = response.data;
              resolve(true);
            }
          })
          .catch((error) => {
            this.setAgenciesTotalExpress([]);
          })
          .finally(() => {
            this.setLoader(false);
          });
      });

      promiseAgencies.then((resolve) => {
        this.setAgenciesTotalExpress(responseAgenciesTotalExpress);
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
    goToTokenIfNeeded() {
      if (this.$route.name !== "Token") {
        this.$router.push({ name: "Token" }).catch(() => {});
      }
    },
    getToken() {
      this.$http.get(verifyToken()).then((response) => {
        if (!response.data.exists_token) {
          this.goToTokenIfNeeded();
        }
        this.validateToken();
      });
    },
    validateToken() {
      this.$http.get(getToken()).then((response) => {
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
    agenciesCorreiosCentralized() {
      this.setLoader(true);
      if (this.agenciesCorreiosCentralized.length > 0) {
        this.agenciesCorreiosCentralized.filter((item) => {
          if (item.selected) {
            this.agency_correios_centralized = item.id;
          }
        });
      }
      this.setLoader(false);
    },
    agenciesJadlogCentralized() {
      this.setLoader(true);
      if (this.agenciesJadlogCentralized.length > 0) {
        this.agenciesJadlogCentralized.filter((item) => {
          if (item.selected) {
            this.agency_jadlog_centralized = item.id;
          }
        });
      }
      this.setLoader(false);
    },
    agenciesLoggi() {
      this.setLoader(true);
      if (this.agenciesLoggi.length > 0) {
        this.agenciesLoggi.filter((item) => {
          if (item.selected) {
            this.agency_loggi = item.id;
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
    agenciesJeT() {
      this.setLoader(true);
      if (this.agenciesJeT.length > 0) {
        this.agenciesJeT.filter((item) => {
          if (item.selected) {
            this.agency_jet = item.id;
          }
        });
      }
      this.setLoader(false);
    },
    agenciesTotalExpress() {
      this.setLoader(true);
      if (this.agenciesTotalExpress.length > 0) {
        this.agenciesTotalExpress.filter((item) => {
          if (item.selected) {
            this.agency_totalexpress = item.id;
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
    agencyCorreiosCentralizedSelected_(e) {
      this.agency_correios_centralized = e;
    },
    agencyJadlogCentralizedSelected_(e) {
      this.agency_jadlog_centralized = e;
    },
    agencyLoggiSelected_(e) {
      this.agency_loggi = e;
    },
    agencyLatamSelected_(e) {
      this.agency_latam = e;
    },
    agencyJeTSelected_(e) {
      this.agency_jet = e;
    },
    agencyTotalExpressSelected_(e) {
      this.agency_totalexpress = e;
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
    this._onModalSuccessEscape = (e) => {
      if (e.key === "Escape" && this.show_modal) {
        this.close();
      }
    };
    document.addEventListener("keydown", this._onModalSuccessEscape);
  },
  beforeDestroy() {
    if (this._onModalSuccessEscape) {
      document.removeEventListener("keydown", this._onModalSuccessEscape);
    }
  },
};
</script>

<style lang="css" scoped>
.wpme_config_page {
  clear: both;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans,
    Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: #1d2327;
}

.wpme_config_page__save-wrap {
  display: flex;
  justify-content: flex-end;
  width: 100%;
  margin: 16px 0 8px;
  clear: both;
}

.wpme_config_page__save-wrap .wpme_config_page__save.btn-border {
  font-family: inherit;
  font-size: 15px;
  letter-spacing: 0.06em;
}

.wpme_config_page__save-wrap .btn-border.-full-blue:hover {
  background-color: #043d7a;
  color: #fff;
  border-color: #043d7a;
}

.wpme_config_panel {
  clear: both;
  margin: 0 0 24px;
  padding: 20px 24px 24px;
  border: 1px solid #c8d0dc;
  border-radius: 6px;
  background: linear-gradient(180deg, #f4f7fb 0%, #ffffff 56px);
  box-shadow: 0 1px 4px rgba(5, 80, 160, 0.07);
}

.wpme_config_panel--narrow {
  max-width: 50%;
  width: 100%;
}

.wpme_config_row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
  align-items: stretch;
  margin: 0 0 24px;
  width: 100%;
  box-sizing: border-box;
}

.wpme_config_row .wpme_config_panel--split {
  min-width: 0;
  max-width: none;
  margin-bottom: 0;
}

@media (max-width: 782px) {
  .wpme_config_panel--narrow {
    max-width: 100%;
  }

  .wpme_config_row {
    grid-template-columns: 1fr;
  }
}

.wpme_config_panel--page-header {
  margin-top: 0;
}

.wpme_config_panel--page-header h1 {
  margin: 0 0 8px;
  padding-bottom: 12px;
  font-size: 1.35rem;
  font-weight: 400;
  color: #0550a0;
}

.wpme_config_panel > h2 {
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #dde3ec;
  font-size: 1.15rem;
  font-weight: 400;
  color: #0550a0;
}

.wpme_config_panel > hr {
  border: none;
  border-top: 1px solid #e5e9f0;
  margin: 18px 0;
}

.wpme_config_panel__subsection {
  margin-top: 20px;
  padding-top: 18px;
  border-top: 1px solid #e5e9f0;
}

.wpme_config_panel__subsection h3 {
  margin: 0 0 12px;
  font-size: 1rem;
  font-weight: 600;
  color: #464646;
}

.wpme_origin_cards {
  float: none;
  flex: 1 1 100%;
  min-width: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 14px;
  width: 100%;
  list-style: none;
}

.wpme_origin_cards > li {
  float: none;
  display: flex;
  flex-direction: column;
  min-width: 0;
  width: auto;
  max-width: none;
  margin: 0;
  box-sizing: border-box;
}

.wpme_origin_cards > li > label {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-height: 100%;
  cursor: pointer;
}

.wpme_origin_cards .wpme_address-body {
  flex: 1;
}

.wpme_config_panel__path-plugins {
  overflow-x: hidden;
  min-width: 0;
}

.wpme_config_panel__path-plugins .wpme_address {
  width: 100%;
  max-width: 100%;
}

.wpme_config_panel__path-plugins .wpme_address > li {
  max-width: none;
  width: 100%;
  min-width: 0;
  box-sizing: border-box;
}

.wpme_config_panel__path-input {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  min-height: 72px;
  height: auto;
  line-height: 1.4;
  resize: vertical;
  overflow-wrap: anywhere;
  word-break: break-word;
  white-space: pre-wrap;
}

.wpme_agency_select_row {
  display: block !important;
  width: 100% !important;
  max-width: 560px !important;
  min-width: 0 !important;
  box-sizing: border-box;
}

.wpme_agency_select_row select {
  display: block;
  width: 100%;
  min-width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

</style>
