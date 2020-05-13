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

    .lineGray:nth-child(odd){
        background: #e1e1e1;
    }

    .text-center{
        text-align: center;
    }

    .styleTableMoreInfo span {
        font-size: 16px;
    }

    .styleTableMoreInfo td {
        padding: 1%;
    }

    .error-message {
        width: 98%;
        padding: 10px 0 10px 2%;
        background-color: #d6442a;
        color: #fff;
        font-weight: 600;
    }
</style>

<template>
    <div class="app-pedidos">

        <div class="boxBanner">
            <img src="https://s3.amazonaws.com/wordpress-v2-assets/img/banner-admin.png" />
        </div>

        <template>
            <div>
                <div class="grid">
                    <div class="col-12-12">
                        <h1>Meus pedidos</h1>
                    </div>
                    <hr>
                    <div class="col-12-12" v-show="error_message">
                        <p class="error-message">{{ error_message }}</p>
                    </div>
                    <br>
                </div>
            </div>
        </template>

        <table border="0" class="table-box">
            <tr>
                <td>
                    <h4><b>Saldo:</b> {{getBalance}}</h4>
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
                        <option v-for="(statusName, statusKey) in statusWooCommerce" :key="statusKey" v-bind:value="statusKey">
                            {{ statusName }}
                        </option>
                    </select>
                </td>
            </tr>
        </table>

        <div class="table-box" v-if="orders.length > 0" :class="{'-inative': !orders.length }">
            <div class="table -woocommerce">
                <ul class="head">
                    <li>
                        <span>
                            <!--<input type="checkbox" @click="selectAll" />-->
                        </span>
                    </li>
                    <li><span>ID</span></li>
                    <li><span>Destinatário</span></li>
                    <li><span>Cotação</span></li>
                    <li><span>Documentos</span></li>
                    <li><span>Etiqueta</span></li>
                    <li><span>Ações</span></li>
                </ul>

                <ul class="body">
                    <li  v-for="(item, index) in orders" :key="index" class="lineGray" style="padding:1%">
                        <ul class="body-list">
                            <li>
                                <input type="checkbox" ref="orderCheck" :disabled="!(item.status == 'posted' || item.status =='released')" :value="item.id" v-model="ordersSelecteds">
                            </li>
                            <li>
                                <Id :item="item"></Id>
                                <span style="font-size:12px; cursor:pointer"><a @click="handleToggleInfo(item.id)">Ver detalhes</a></span>
                            </li>

                            <li>
                                <Destino :to="item.to"></Destino>
                            </li>
                            <li>
                                <Cotacao :item="item"></Cotacao>
                            </li>
                            <li>
                                <Documentos :item="item"></Documentos>
                            </li>
                            <li class="text-center">
                                <span style="font-size: 14px;">
                                    <strong>{{item.status_texto}}</strong>
                                </span>
                            </li>
                            <li class="-center">
                                <Acoes :item="item"></Acoes>
                            </li>
                        </ul>
                        <template v-if="toggleInfo == item.id">
                            <informacoes
                                :volume="item.cotation[item.cotation.choose_method].packages[0]"
                                :products="item.products">
                            </informacoes>
                        </template>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else><p>Nenhum registro encontrado</p></div>
        <button v-show="show_more" class="btn-border -full-green" @click="loadMore({status:status, wpstatus:wpstatus})">Carregar mais</button>

        <button v-show="show_more" class="btn-border -full-blue" @click="printMultiples(ordersSelecteds)">Imprimir selecionados</button>

        <transition name="fade">
            <div class="me-modal" v-show="show_modal">
                <div>
                    <p class="title">Atenção</p>
                    <div class="content">
                        <p class="txt">{{msg_modal}}</p>
                    </div>
                    <div class="buttons -center">
                        <button type="button" @click="close" class="btn-border -full-blue">Fechar</button>
                    </div>
                </div>
            </div>
        </transition>

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
import { mapActions, mapGetters } from 'vuex'
import Id from './Pedido/Id.vue'
import Destino from './Pedido/Destino.vue'
import Cotacao from './Pedido/Cotacao.vue'
import Documentos from './Pedido/Documentos.vue'
import Acoes from './Pedido/Acoes.vue'
import Informacoes from './Pedido/Informacoes.vue'

export default {
    name: 'Pedidos',
    data: () => {
        return {
            status: 'all',
            wpstatus: 'all',
            line: 0,
            toggleInfo: null,
            error_message: null,
            ordersSelecteds: [],
            allSelected: false,
        }
    },
    components: {
        Id,
        Cotacao,
        Destino,
        Documentos,
        Acoes,
        Informacoes
    },
    computed: {
        ...mapGetters('orders', {
            orders: 'getOrders',
            show_loader: 'toggleLoader',
            msg_modal: 'setMsgModal',
            show_modal: 'showModal',
            show_more: 'showMore',
            statusWooCommerce: 'statusWooCommerce'
        }),
        ...mapGetters('balance', ['getBalance'])
    },
    methods: {
        ...mapActions('orders', [
            'retrieveMany',
            'loadMore',
            'closeModal',
            'getStatusWooCommerce',
            'printMultiples'
        ]),
        ...mapActions('balance', ['setBalance']),
        close() {
            this.closeModal()
        },
        handleToggleInfo(id) {
            this.toggleInfo = this.toggleInfo != id ? id : null
        },
        getToken() {
            this.$http.get(`${ajaxurl}?action=verify_token`).then( (response) => {
                if (!response.data.exists_token) {
                    this.$router.push('Token')
                }

                this.validateToken();
            })
        },
        selectAll: function() {
            //TODO
            for (var key in this.$refs.orderCheck) {
                this.$refs.orderCheck[key].checked;
            }
        },
        validateToken() {
            this.$http.get(`${ajaxurl}?action=get_token`).then((response) => {
                if (response.data.token) {
                    var token = response.data.token;

                    // JWT Token Decode
                    var base64Url = token.split('.')[1];
                    var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
                    var tokenDecoded = decodeURIComponent(atob(base64).split('').map(function(c) {
                        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                    }).join(''));

                    var tokenFinal  = JSON.parse(tokenDecoded);
                    var dateExp     = new Date(parseInt(tokenFinal.exp) * 1000);
                    var currentTime = new Date();

                    if (dateExp < currentTime) {
                        this.error_message = 'Seu Token Melhor Envio expirou, cadastre um novo token para o plugin voltar a funcionar perfeitamente';
                    } else {
                        this.error_message = '';
                    }
                } else {
                    this.$router.push('Token');
                }
            })
        }
    },
    watch: {
        status () {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        },
        wpstatus () {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        }
    },
    mounted () {
        this.getToken();
        if (Object.keys(this.orders).length === 0) {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        }
        this.setBalance()
        this.getStatusWooCommerce()
    }
}
</script>

<style lang="css" scoped>
</style>