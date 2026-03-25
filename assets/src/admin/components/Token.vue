<template>
  <div class="wpme_config_page app-token">
    <div class="wpme_config_panel wpme_token_page">
      <header class="wpme_token_header">
        <h1>Meu Token</h1>
        <p class="wpme_token_lead">
          Cole o token gerado no painel do Melhor Envio.
        </p>
      </header>

      <section
        class="wpme_token_section wpme_token_section--first"
        aria-labelledby="token-values-heading"
      >
        <div class="wpme_token_heading_row">
          <h2 id="token-values-heading" class="wpme_token_section_title">
            {{ environment === 'sandbox' ? 'Sandbox' : 'Produção' }}
          </h2>
          <a
            class="wpme_token_generate_link"
            :href="tokenPanelUrl"
            target="_blank"
            rel="noreferrer noopener"
          >
            Gere seu token de {{ environment === 'sandbox' ? 'sandbox' : 'produção' }}
            <svg
              class="wpme_token_generate_icon"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
              focusable="false"
            >
              <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
              <polyline points="15 3 21 3 21 9" />
              <line x1="10" y1="14" x2="21" y2="3" />
            </svg>
          </a>
        </div>
        <div class="wpme_token_fields wpme_token_fields--single">
          <div v-if="environment !== 'sandbox'" class="wpme_token_field">
            <textarea
              id="token-production-input"
              class="wpme_token_textarea"
              data-cy="token-production"
              v-model="token"
              rows="10"
              spellcheck="false"
              autocomplete="off"
              aria-labelledby="token-values-heading"
            />
          </div>

          <div v-else class="wpme_token_field">
            <textarea
              id="token-sandbox-input"
              class="wpme_token_textarea"
              data-cy="token-sandbox"
              v-model="token_sandbox"
              rows="10"
              spellcheck="false"
              autocomplete="off"
              aria-labelledby="token-values-heading"
            />
          </div>
        </div>
      </section>

      <section class="wpme_token_section" aria-labelledby="token-env-heading">
        <h2 id="token-env-heading" class="wpme_token_section_title">Ambiente</h2>
        <div class="wpme_token_choice">
          <label class="wpme_token_label">
            <input
              data-cy="environment-token"
              type="checkbox"
              v-model="environment"
              true-value="sandbox"
              false-value="production"
            />
            <span class="wpme_token_label_text">Utilizar ambiente Sandbox</span>
          </label>
          <p class="wpme_token_help">
            Produção é o padrão. Ao marcar Sandbox, apenas o token de testes é exibido
            e usado para cotações sem cobrança real.
          </p>
        </div>
      </section>

      <footer class="wpme_token_footer">
        <button
          type="button"
          class="btn-border -full-blue -big wpme_config_page__save"
          @click="saveToken()"
        >
          Salvar
        </button>
      </footer>
    </div>

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
import axios from "axios";

export default {
  name: "Token",
  data() {
    return {
      token: "",
      token_sandbox: "",
      environment: "production",
      show_loader: true,
    };
  },
  methods: {
    getToken() {
      this.$http
        .get(
          `${ajaxurl}?action=get_token&_wpnonce=${wpApiSettingsMelhorEnvio.nonce_tokens}`
        )
        .then((response) => {
          this.token = response.data.token;
          this.token_sandbox = response.data.token_sandbox
            ? response.data.token_sandbox
            : "";
          this.environment = response.data.token_environment
            ? response.data.token_environment
            : "production";
          this.show_loader = false;
        });
    },
    saveToken() {
      let bodyFormData = new FormData();
      bodyFormData.append("token", this.token);
      bodyFormData.append("token_sandbox", this.token_sandbox);
      bodyFormData.append("environment", this.environment);
      bodyFormData.append("_wpnonce", wpApiSettingsMelhorEnvio.nonce_tokens);
      if (
        (this.token && this.token.length > 0) ||
        (this.token_sandbox && this.token_sandbox.length > 0)
      ) {
        axios({
          url: `${ajaxurl}?action=save_token`,
          data: bodyFormData,
          method: "POST",
        })
          .then(() => {
            this.$router.push({ name: "Configuracoes" }).catch((err) => {
              if (err && err.name !== "NavigationDuplicated") {
                console.error(err);
              }
            });
          })
          .catch((err) => console.log(err));
      }
    },
  },
  mounted() {
    this.getToken();
  },
  computed: {
    tokenPanelUrl() {
      return this.environment === "sandbox"
        ? "https://sandbox.melhorenvio.com.br/painel/gerenciar/tokens"
        : "https://melhorenvio.com.br/painel/gerenciar/tokens";
    },
  },
};
</script>

<style lang="css" scoped>
.wpme_config_page {
  clear: both;
  width: 100%;
  max-width: none;
  box-sizing: border-box;
  /* Mesma pilha do admin WP / Configurações (texto de interface) */
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans,
    Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: #1d2327;
}

.wpme_config_panel.wpme_token_page {
  margin: 0 0 24px;
  padding: 0;
  overflow: hidden;
}

.wpme_token_header {
  padding: 24px 28px 20px;
  background: linear-gradient(180deg, #f0f4f9 0%, #ffffff 100%);
  border-bottom: 1px solid #e2e8f0;
}

.wpme_token_header h1 {
  margin: 0 0 8px;
  padding-bottom: 12px;
  border-bottom: 1px solid #dde3ec;
  font-size: 1.35rem;
  font-weight: 400;
  color: #0550a0;
  line-height: 1.3;
}

.wpme_token_lead {
  margin: 0;
  color: #50575e;
  font-size: 14px;
  line-height: 1.55;
}

.wpme_token_section {
  padding: 22px 28px;
  border-top: 1px solid #eef1f5;
}

.wpme_token_section--first {
  border-top: none;
}

.wpme_token_heading_row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px 20px;
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #dde3ec;
}

/* Alinhado a `.wpme_config_panel > h2` em Configuracoes.vue */
.wpme_token_section_title {
  margin: 0 0 20px;
  padding-bottom: 12px;
  border-bottom: 1px solid #dde3ec;
  font-size: 1.15rem;
  font-weight: 400;
  color: #0550a0;
  line-height: 1.3;
}

.wpme_token_heading_row .wpme_token_section_title {
  margin: 0;
  padding-bottom: 0;
  border-bottom: none;
  line-height: 1.3;
}

.wpme_token_choice {
  padding: 14px 16px;
  background: #f6f7f7;
  border: 1px solid #e8ecf0;
  border-radius: 6px;
}

.wpme_token_label {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  font-size: 14px;
  line-height: 1.4;
  margin: 0;
}

.wpme_token_label input[type="checkbox"] {
  margin-top: 2px;
  flex-shrink: 0;
}

.wpme_token_label_text {
  font-weight: 500;
  color: #1d2327;
}

.wpme_token_help {
  margin: 12px 0 0;
  padding: 0 0 0 26px;
  font-size: 13px;
  line-height: 1.5;
  color: #787c82;
}

.wpme_token_fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
  align-items: start;
}

.wpme_token_fields--single {
  grid-template-columns: 1fr;
}

.wpme_token_field {
  min-width: 0;
}

.wpme_token_textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 12px 14px;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
  font-size: 12px;
  line-height: 1.5;
  color: #1d2327;
  border: 1px solid #c3c4c7;
  border-radius: 4px;
  resize: vertical;
  min-height: 180px;
  background: #fff;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
}

.wpme_token_textarea:focus {
  border-color: #0550a0;
  outline: none;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04), 0 0 0 1px #0550a0;
}

.wpme_token_generate_link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #0550a0;
  text-decoration: none;
}

.wpme_token_generate_link:hover {
  color: #043d7a;
  text-decoration: underline;
}

.wpme_token_generate_link:hover .wpme_token_generate_icon {
  opacity: 1;
}

.wpme_token_generate_icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
  opacity: 0.88;
  vertical-align: middle;
}

.wpme_token_footer {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  padding: 18px 28px 22px;
  border-top: 1px solid #eef1f5;
  background: #fafbfc;
}

.wpme_token_footer .wpme_config_page__save.btn-border {
  font-family: inherit;
  font-size: 15px;
  letter-spacing: 0.06em;
}

.wpme_token_footer .btn-border.-full-blue:hover {
  background-color: #043d7a;
  color: #fff;
  border-color: #043d7a;
}

.wpme_config_panel {
  clear: both;
  border: 1px solid #c8d0dc;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
}

@media (max-width: 782px) {
  .wpme_token_fields {
    grid-template-columns: 1fr;
  }

  .wpme_token_header,
  .wpme_token_section,
  .wpme_token_footer {
    padding-left: 18px;
    padding-right: 18px;
  }

  .wpme_token_heading_row {
    flex-direction: column;
    align-items: flex-start;
  }

}
</style>
