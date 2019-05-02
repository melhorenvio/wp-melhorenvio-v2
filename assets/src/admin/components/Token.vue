<template>
    <div class="app-token">
        <h1>Meu Token</h1>
        <span>Insira o token gerado no Melhor Envio</span>
        <br>
        <textarea rows="20" cols="100" v-model="token" placeholder="Token"></textarea>
        <br>
        <br>
        <button @click="saveToken()" class="btn-border -full-green">Salvar</button>

        <div class="me-modal" v-show="show_loader">
            <svg style="float:left; margin-top:10%; margin-left:50%;" class="ico" width="88" height="88" viewBox="0 0 44 44" xmlns="http://www.w3.org/2000/svg" stroke="#3598dc">
                    <g fill="none" fill-rule="evenodd" stroke-width="2">
                    <circle cx="22" cy="22" r="1">
                        <animate attributeName="r"
                        begin="0s" dur="1.8s"
                        values="1; 20"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.165, 0.84, 0.44, 1"
                        repeatCount="indefinite" />
                        <animate attributeName="stroke-opacity"
                        begin="0s" dur="1.8s"
                        values="1; 0"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.3, 0.61, 0.355, 1"
                        repeatCount="indefinite" />
                    </circle>
                    <circle cx="22" cy="22" r="1">
                        <animate attributeName="r"
                        begin="-0.9s" dur="1.8s"
                        values="1; 20"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.165, 0.84, 0.44, 1"
                        repeatCount="indefinite" />
                        <animate attributeName="stroke-opacity"
                        begin="-0.9s" dur="1.8s"
                        values="1; 0"
                        calcMode="spline"
                        keyTimes="0; 1"
                        keySplines="0.3, 0.61, 0.355, 1"
                        repeatCount="indefinite" />
                    </circle>
                    </g>
                </svg>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
export default {
    name: 'Token',
    data () {
        return {
            token: '',
            show_loader: true
        }
    },
    methods: {
        getToken () {
            this.$http.get(`${ajaxurl}?action=get_token`).then((response) => {
                this.token = response.data.token;
                this.show_loader = false;
            })
        },
        saveToken () {
            let bodyFormData = new FormData();
            bodyFormData.set('token', this.token);
            let data = {token: this.token};
            if (this.token && this.token.length > 0) {
                axios({
                    url: `${ajaxurl}?action=save_token`,
                    data: bodyFormData,
                    method: "POST",
                }).then( response => {
                    this.$router.push('Configuracoes') 
                }).catch(err => console.log(err));
            }
        }
    },
    mounted () {
        this.getToken()
    }
}
</script>

<style lang="css" scoped>
</style>