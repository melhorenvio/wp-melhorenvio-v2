<template>
  <div class="app-token">
    <h1>Meu Token</h1>
    <span>Insira o token gerado no Melhor Envio</span>
    <br />
    <textarea
      data-cy="token-production"
      rows="20"
      cols="100"
      v-model="token"
      placeholder="Token"
    ></textarea>
    <br />
    <p>
      <input
        data-cy="environment-token"
        type="checkbox"
        v-model="environment"
        true-value="sandbox"
        false-value="production"
      />
      Utilizar ambiente Sandbox
    </p>

    <textarea
      v-if="environment == 'sandbox'"
      rows="20"
      cols="100"
      v-model="token_sandbox"
      placeholder="Token Sandbox"
      data-cy="token-sandbox"
    ></textarea>
    <br />
    <br />
    <button @click="saveToken()" class="btn-border -full-green">Salvar</button>

    <p>
      Para gerar seu token, acesse o
      <a
        target="_blank"
        rel="noreferrer noopener"
        href="https://melhorenvio.com.br/painel/gerenciar/tokens"
        >link</a
      >
    </p>
    <p v-if="environment == 'sandbox'">
      Para gerar seu token em sandbox, acesse o
      <a
        target="_blank"
        rel="noreferrer noopener"
        href="https://sandbox.melhorenvio.com.br/painel/gerenciar/tokens"
        >link</a
      >
    </p>

    <div class="me-modal" v-show="show_loader">
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
import axios from "axios";
import Router from "vue-router";

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
            : "";
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
          .then((response) => {
            var router = new Router();
            router.push("/configuracoes");
            router.go();
          })
          .catch((err) => console.log(err));
      }
    },
  },
  mounted() {
    this.getToken();
  },
};
</script>

<style lang="css" scoped>
</style>