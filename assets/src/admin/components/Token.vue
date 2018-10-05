<template>
    <div class="app-token">
        <h1>Meu Token</h1>
        <span>Insira o token gerado na Melhor Envio</span>
        <br>
        <textarea rows="20" cols="100" v-model="token" placeholder="Token"></textarea>
        <br>
        <br>
        <button @click="saveToken()" class="btn-border -full-green">Salvar</button>

        <div class="me-modal" v-show="show_loader">
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