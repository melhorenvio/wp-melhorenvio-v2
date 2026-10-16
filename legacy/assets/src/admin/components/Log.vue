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
</style>

<template>
    <div class="app-pedidos">
        <template>
            <div>
                <div class="grid">
                    <div class="col-12-12">
                        <h1>Histórico do pedido - {{ this.$route.params.id }}</h1>
                    </div>
                    <hr>
                    <br>
                </div>
            </div>
        </template>

        <div v-if="logs">
            <ul v-for="(item, index) in logs" :key="index">
                <li><b>Data:</b> {{item.date}}</li>
                <li>
                    <b>Tipo:</b> 
                    <span v-if="item.type == 'make_quotation'">Cotação</span>
                    <span v-if="item.type == 'send_order'">Carrinho</span>
                    <span v-if="item.type == 'error_quotation'">Erro na cotação</span>
                </li>
                <li>
                    <template v-if="item.type == 'make_quotation'">
                        <ul v-for="(product, id) in item.body.products" :key="id">
                            <li>
                                <b>Produto: </b>
                                {{product.quantity}}x - {{product.name}} </br>
                                <b>Valor: </b> R${{product.insurance_value}} </br>
                                <b>Medidas: </b> {{product.height}}cm A x {{product.width}}cm L x {{product.length}}cm C - {{product.weight}} kg
                            </li>
                        </ul>
                        <ul>
                            <li v-if="item.body.from">
                                <b>Origem: </b>{{item.body.from.postal_code}}
                            </li>
                            <li v-if="item.body.to">
                                <b>Destino: </b>{{item.body.to.postal_code}}
                            </li>
                        </ul>
                        <ul v-for="(result, id) in item.response" :key="id">
                            <template v-if="result.price">
                                <li><b>Serviço: </b>{{result.name}}({{result.id}}) (R${{result.price}})</li>
                                <ul v-for="(volume, idVol) in result.volumes" :key="idVol">
                                    <li v-if="result">
                                        <b>Volume ({{idVol + 1}}): </b> {{idVol + 1}} {{volume.height}}cm A x {{volume.width}}cm L x {{volume.length}}cm C - {{volume.weight}} kg
                                    </li>
                                </ul>
                            </template>
                        </ul>
                    </template>
                </li>

                <template v-if="item.type == 'send_order'">
                    <ul>
                        <li v-if="item.body.from.postal_code">
                            <b>Origem: </b>{{item.body.from.postal_code}}
                        </li>
                        <li v-if="item.body.to">
                            <b>Destino: </b>{{item.body.to.postal_code}}
                        </li>
                    </ul>
                    <ul>
                        <li><b>ID: </b>{{item.response.id}}</li>
                        <li><b>Protocolo: </b>{{item.response.protocol}}</li>
                        <li><b>Serviço: </b>{{item.response.service_id}}</li>
                        <li><b>Preço: </b>R${{item.response.price}}</li>
                        <li><b>Valor segurado: </b>R${{item.response.insurance_value}}</li>
                        <li>
                            <b>Volume retornado: </b> {{item.response.height}}cm A x {{item.response.width}}cm L x {{item.response.length}}cm C - {{item.response.weight}} kg
                        </li>
                    </ul>
                </template>

                <template v-if="item.type == 'error_quotation'">
                    <ul v-for="(product, id) in item.body.products" :key="id">
                        <li v-if="product.volumes[0]">
                            <b>Produto: </b>
                            {{product.quantity}}x - {{product.name}} </br>
                            <b>Valor: </b> R${{product.insurance}} </br>
                            <b>Medidas: </b> {{product.volumes[0].height}}cm A x {{product.volumes[0].width}}cm L x {{product.volumes[0].length}}cm C - {{product.volumes[0].weight}} kg
                        </li>
                    </ul>
                    <ul>
                        <li v-if="item.body.from.postal_code">
                            <b>Origem: </b>{{item.body.from.postal_code}}
                        </li>
                        <li v-if="item.body.to">
                            <b>Destino: </b>{{item.body.to.postal_code}}
                        </li>
                    </ul>
                    <ul v-for="(result, id) in item.response" :key="id">
                        <template v-if="result.price">
                            <li><b>Serviço: </b>{{result.name}}({{result.id}}) (R${{result.price}})</li>
                            <ul v-for="(volume, idVol) in result.volumes" :key="idVol">
                                <li v-if="result">
                                    <b>Volume ({{idVol + 1}}): </b> {{idVol + 1}} {{volume.height}}cm A x {{volume.width}}cm L x {{volume.length}}cm C - {{volume.weight}} kg
                                </li>
                            </ul>
                        </template>
                    </ul>
                    <ul>
                        <li><b>ID: </b>{{item.response.service}}</li>
                        <li><b>Protocolo: </b>{{item.response.error.message}}</li>
                    </ul>
                </template>
                
                <hr>
            </ul>
        </div>

    </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex";

export default {
  name: "Logs",
  computed: {
    /**
     * log => state.log // traz os logs salvos em state
     */
    ...mapGetters("log", ["logs"]),
  },
  methods: {
    /**
     * Map actions retrieveLogs()
     * Realiza um http pra api, recupera os logs.
     * commit('retrieveLogs', response.data) < mutation de mesmo nome.
     * state.logs = data < na mutation
     */
    ...mapActions("log", ["retrieveLogs"]),
  },
  created() {
    /**
     * Call this.retrieveLogs()
     * carrega todos os logs ao criar componente.
     * talvez um botão para atualizar?
     */
    let id = this.$route.params.id;

    if (!id) {
      this.$router.push("/pedidos");
    }
    this.retrieveLogs(id);
  },
};
</script>

<style lang="css" scoped>
</style>