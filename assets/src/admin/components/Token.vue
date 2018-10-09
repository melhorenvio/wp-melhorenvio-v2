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
            <svg style="margin-top:20%; margin-left:40%" width='240px' height='240px' xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-default"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(0 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-1s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(30 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.9166666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(60 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.8333333333333334s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(90 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.75s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(120 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.6666666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(150 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.5833333333333334s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(180 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.5s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(210 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.4166666666666667s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(240 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.3333333333333333s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(270 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.25s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(300 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.16666666666666666s' repeatCount='indefinite'/></rect><rect  x='46.5' y='40' width='7' height='20' rx='5' ry='5' fill='rgba(66.19297413793103%,74.43474121027721%,78%,0.51)' transform='rotate(330 50 50) translate(0 -30)'>  <animate attributeName='opacity' from='1' to='0' dur='1s' begin='-0.08333333333333333s' repeatCount='indefinite'/></rect></svg>
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