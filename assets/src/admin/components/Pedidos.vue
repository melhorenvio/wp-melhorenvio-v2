<template>
    <div class="app-pedidos">
        <template>
            <div>
                <div class="grid">
                    <div class="col-12-12">
                        <h1>Meus pedidos</h1>
                    </div>
                    <hr>
                    <br>
                </div>
            </div>
        </template>

        <table border="0" class="table-box">
            <tr>
                <td>
                    <h1>Saldo: <strong>{{getBalance}}</strong></h1>
                </td>
            </tr>
            <tr>
                <td width="50%">
                    <h3>Etiquetas</h3>
                    <select v-model="status">
                        <option value="all">Todas</option>
                        <option value="printed">Impressas</option>
                        <option value="paid">Pagas</option>
                        <option value="pending">Aguardando pagamento</option>
                        <option value="generated">Geradas</option>
                    </select>
                </td>
                <td width="50%">
                    <h3>Pedidos</h3>
                    <select v-model="wpstatus">
                        <option value="all">Todos</option>
                        <option value="wc-pending">Pendentes</option>
                        <option value="wc-processing">Processando</option>
                        <option value="wc-on-hold">Em andamento</option>
                        <option value="wc-completed">Completos</option>
                        <option value="wc-cancelled">Cancelados</option>
                        <option value="wc-refunded">Recusados</option>
                        <option value="wc-failed">Com erro</option>
                    </select>
                </td>
            </tr>
        </table>

        <div class="table-box" v-if="orders.length > 0" :class="{'-inative': !orders.length }">
            <div class="table -woocommerce">
                <ul class="head">
                    <li><span>ID</span></li>
                    <li><span>Valor</span></li>
                    <li><span>Destinatário</span></li>
                    <li><span>Cotação</span></li>
                    <li><span>Documentos</span></li>
                    <li><span>Ações</span></li>
                </ul>

                <ul class="body">
                    <li v-for="(item, index) in orders" :key="index">
                        <ul class="body-list">
                            <li><span><a target="_blank" :href="`/wp-admin/post.php?post=${item.id}&action=edit`"><strong>{{ item.id }}</strong></a></span></li>
                            <li><span>{{ item.total }}</span></li>
                            <li>
                                <span style="font-size: 14px;">
                                    <strong>{{item.to.first_name}} {{item.to.last_name}}</strong> <br>
                                    {{item.to.email}} <br>
                                    {{item.to.phone}} <br>
                                    {{item.to.address_1}} {{item.to.address_2}} <br>
                                    {{item.to.city}} / {{item.to.state}} - {{item.to.postcode}} <br>
                                </span>
                            </li>
                            <li>
                                <template v-if="!item.order_id">
                                    <div  class="me-form">
                                        <div class="formBox">
                                            <label>Métodos de envio</label>
                                            <fieldset class="selectLine">
                                                <div class="inputBox">
                                                    <select v-if="!(item.status == 'paid' || item.status == 'printed' || item.status == 'generated')" v-model="item.cotation.choose_method">
                                                        <option v-if="option.id && option.price" v-for="option in item.cotation" v-bind:value="option.id" :key="option.id">
                                                            {{ option.company.name }} {{ option.name }} (R${{ option.price }}) 
                                                        </option>
                                                    </select>
                                                </div>
                                            </fieldset>
                                        </div>
                                    </div>
                                </template>
                                <template v-else>
                                    <span>{{ item.protocol }}</span>
                                </template>
                            </li>
                            <li>
                                <div class="me-form">
                                    <div class="formBox paddingBox">
                                        <template  v-if="item.cotation.choose_method == 3 || item.cotation.choose_method == 4" >
                                            <fieldset class="checkLine">
                                                <div class="inputBox">
                                                    <input type="checkbox" v-model="item.non_commercial" />
                                                    <label>Enviar com declaração de conteúdo    </label>
                                                </div>
                                            </fieldset>
                                            <br>
                                        </template>
                                        <template  v-if="(item.cotation.choose_method >= 3 && !item.non_commercial) || item.cotation.choose_method > 4">
                                            <fieldset>
                                                <div>
                                                    <label>Nota fiscal</label><br>
                                                    <input type="text" v-model="item.invoice.number" /><br>
                                                    <label>Chave da nota fiscal</label><br>
                                                    <input type="text" v-model="item.invoice.key" /><br>
                                                    <br>
                                                    <button class="btn-border -full-blue" @click="insertInvoice(item)">Salvar</button>
                                                </div>
                                            </fieldset>
                                        </template>
                                    </div>
                                </div>
                            </li>
                            <li class="-center">
                                <a v-if="buttonCartShow(item.cotation.choose_method, item.non_commercial, item.invoice.number, item.invoice.key, item.status)" @click="addCart({id:item.id, choosen:item.cotation.choose_method, non_commercial: item.non_commercial})" href="javascript:;" class="action-button -adicionar" data-tip="Adicionar">
                                    <svg class="ico" version="1.1" id="cart-add" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                        viewBox="0 0 511.999 511.999" style="enable-background:new 0 0 511.999 511.999;" xml:space="preserve">
                                    <g>
                                        <g>
                                            <path d="M214.685,402.828c-24.829,0-45.029,20.2-45.029,45.029c0,24.829,20.2,45.029,45.029,45.029s45.029-20.2,45.029-45.029
                                                C259.713,423.028,239.513,402.828,214.685,402.828z M214.685,467.742c-10.966,0-19.887-8.922-19.887-19.887
                                                c0-10.966,8.922-19.887,19.887-19.887s19.887,8.922,19.887,19.887C234.572,458.822,225.65,467.742,214.685,467.742z"/>
                                        </g>
                                    </g>
                                    <g>
                                        <g>
                                            <path d="M372.63,402.828c-24.829,0-45.029,20.2-45.029,45.029c0,24.829,20.2,45.029,45.029,45.029s45.029-20.2,45.029-45.029
                                                C417.658,423.028,397.458,402.828,372.63,402.828z M372.63,467.742c-10.966,0-19.887-8.922-19.887-19.887
                                                c0-10.966,8.922-19.887,19.887-19.887c10.966,0,19.887,8.922,19.887,19.887C392.517,458.822,383.595,467.742,372.63,467.742z"/>
                                        </g>
                                    </g>
                                    <g>
                                        <g>
                                            <path d="M383.716,165.755H203.567c-6.943,0-12.571,5.628-12.571,12.571c0,6.943,5.629,12.571,12.571,12.571h180.149
                                                c6.943,0,12.571-5.628,12.571-12.571C396.287,171.382,390.659,165.755,383.716,165.755z"/>
                                        </g>
                                    </g>
                                    <g>
                                        <g>
                                            <path d="M373.911,231.035H213.373c-6.943,0-12.571,5.628-12.571,12.571s5.628,12.571,12.571,12.571h160.537
                                                c6.943,0,12.571-5.628,12.571-12.571C386.481,236.664,380.853,231.035,373.911,231.035z"/>
                                        </g>
                                    </g>
                                    <g>
                                        <g>
                                            <path d="M506.341,109.744c-4.794-5.884-11.898-9.258-19.489-9.258H95.278L87.37,62.097c-1.651-8.008-7.113-14.732-14.614-17.989
                                                l-55.177-23.95c-6.37-2.767-13.773,0.156-16.536,6.524c-2.766,6.37,0.157,13.774,6.524,16.537L62.745,67.17l60.826,295.261
                                                c2.396,11.628,12.752,20.068,24.625,20.068h301.166c6.943,0,12.571-5.628,12.571-12.571c0-6.943-5.628-12.571-12.571-12.571
                                                H148.197l-7.399-35.916H451.69c11.872,0,22.229-8.44,24.624-20.068l35.163-170.675
                                                C513.008,123.266,511.136,115.627,506.341,109.744z M451.69,296.301H135.619l-35.161-170.674l386.393,0.001L451.69,296.301z"/>
                                        </g>
                                    </g>
                                    </svg>
                                </a>
                                <a v-if="item.status == 'paid' && item.order_id && item.id" @click="cancelCart({id:item.id, order_id:item.order_id})" class="action-button -excluir" data-tip="Cancelar">
                                    <svg class="ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 383.2 500"><title>Cancelar</title><g id="Camada_2" data-name="Camada 2"><g id="Camada_10" data-name="Camada 10"><path class="cls-1" d="M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"/><path class="cls-1" d="M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/><path class="cls-1" d="M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/><path class="cls-1" d="M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/></g></g></svg>
                                </a>
                                <a v-if="item.status && item.order_id && item.id && item.status == 'pending'" @click="payTicket({id:item.id, order_id:item.order_id})" href="javascript:;" class="action-button -adicionar" data-tip="Pagar">
                                    <svg class="ico" version="1.1" id="pagar" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                        viewBox="0 0 24 24" enable-background="new 0 0 24 24" xml:space="preserve">
                                    <path d="M12,2c5.514,0,10,4.486,10,10s-4.486,10-10,10S2,17.514,2,12S6.486,2,12,2z M12,0C5.373,0,0,5.373,0,12s5.373,12,12,12
                                        s12-5.373,12-12S18.627,0,12,0z M16,14.083c0-2.145-2.232-2.742-3.943-3.546c-1.039-0.54-0.908-1.829,0.581-1.916
                                        c0.826-0.05,1.675,0.195,2.443,0.465l0.362-1.647C14.536,7.163,13.724,7.037,13,7.018V6h-1v1.067
                                        c-1.945,0.267-2.984,1.487-2.984,2.85c0,2.438,2.847,2.81,3.778,3.243c1.27,0.568,1.035,1.75-0.114,2.011
                                        c-0.997,0.226-2.269-0.168-3.225-0.54L9,16.275c0.894,0.462,1.965,0.708,3,0.727V18h1v-1.053C14.657,16.715,16.002,15.801,16,14.083
                                        z"/>
                                    </svg>
                                </a>
                                <a v-if="item.status && item.status == 'paid' && item.order_id" @click="createTicket({id:item.id, order_id:item.order_id})" class="action-button -adicionar" data-tip="Gerar etiqueta">
                                    <svg class="ico" version="1.1" id="imprimir" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                        viewBox="0 0 191.0681 184.5303" enable-background="new 0 0 191.0681 184.5303" xml:space="preserve">
                                    <path id="imprimir-path4" d="M60.1948,0H130.35c5.3073,0,10.1271,2.1659,13.6165,5.6554
                                        c3.4895,3.4894,5.6554,8.3092,5.6554,13.6165v29.3652h21.6803c5.4433,0,10.3867,2.2215,13.9654,5.8006
                                        c3.579,3.579,5.8005,8.5223,5.8005,13.9657v62.1068c0,5.4434-2.2215,10.3867-5.8005,13.9655
                                        c-3.5787,3.579-8.5221,5.8005-13.9654,5.8005h-20.1121v17.763c0,4.5425-1.8533,8.6672-4.8385,11.6527
                                        c-2.9854,2.9854-7.1101,4.8384-11.6529,4.8384H55.0601c-4.5428,0-8.6674-1.8533-11.6529-4.8384
                                        c-2.9852-2.9855-4.8385-7.1102-4.8385-11.6527v-17.763H19.766c-5.4434,0-10.3867-2.2215-13.9655-5.8005
                                        C2.2215,140.8969,0,135.9536,0,130.5102V68.4034C0,62.96,2.2215,58.0167,5.8005,54.4377c3.5788-3.5791,8.5221-5.8006,13.9655-5.8006
                                        h21.1569V19.2719c0-5.3073,2.166-10.1271,5.6554-13.6165C50.0675,2.1659,54.8872,0,60.1948,0z M158.8788,72.9145
                                        c4.4407,0,8.0407,3.6292,8.0407,8.1062c0,4.4767-3.6,8.1062-8.0407,8.1062c-4.4408,0-8.0408-3.6295-8.0408-8.1062
                                        C150.838,76.5437,154.438,72.9145,158.8788,72.9145z M69.6444,160.0934c-2.3743,0-4.299-2.2124-4.299-4.9416
                                        c0-2.7289,1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2125,4.299,4.9414c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z
                                        M69.6444,141.9199c-2.3743,0-4.299-2.2124-4.299-4.9416s1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2122,4.299,4.9414
                                        c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M136.3657,150.2762v-27.8807c0-0.4507-0.1899-0.866-0.4955-1.1716
                                        c-0.3055-0.3056-0.7208-0.4952-1.1715-0.4952H55.0601c-0.4507,0-0.8659,0.1896-1.1715,0.4952
                                        c-0.3056,0.3056-0.4952,0.7209-0.4952,1.1716v27.8807v17.763c0,0.4504,0.1896,0.8657,0.4952,1.1713
                                        c0.3056,0.3056,0.7208,0.4955,1.1715,0.4955h79.6386c0.4507,0,0.866-0.1899,1.1715-0.4955
                                        c0.3056-0.3056,0.4955-0.7209,0.4955-1.1713V150.2762L136.3657,150.2762z M149.6219,63.4618H40.9229H19.766
                                        c-1.351,0-2.5849,0.5581-3.4841,1.4573c-0.8991,0.8991-1.4573,2.133-1.4573,3.4843v62.1068c0,1.351,0.5582,2.5849,1.4573,3.4841
                                        c0.8992,0.8991,2.1331,1.4573,3.4841,1.4573h18.8027v-13.0561c0-4.5428,1.8531-8.6673,4.8385-11.653
                                        c2.9855-2.9851,7.1101-4.8384,11.6529-4.8384h79.6386c4.5428,0,8.6675,1.8533,11.6529,4.8384
                                        c2.9855,2.9857,4.8385,7.1102,4.8385,11.653v13.0561h20.1121c1.351,0,2.5849-0.5582,3.484-1.4573
                                        c0.8992-0.8992,1.4573-2.1331,1.4573-3.4841V68.4035c0-1.3513-0.5581-2.5852-1.4573-3.4843
                                        c-0.8991-0.8992-2.133-1.4573-3.484-1.4573L149.6219,63.4618L149.6219,63.4618z M130.35,14.8246H60.1948
                                        c-1.2155,0-2.3258,0.5026-3.1354,1.3122c-0.8093,0.8096-1.3121,1.9199-1.3121,3.1351v29.3652h79.05V19.2719
                                        c0-1.2152-0.5026-2.3255-1.3121-3.1351C132.6759,15.3272,131.5653,14.8246,130.35,14.8246z"/>
                                    <path id="imprimir-path6" d="M158.8787,72.8156c2.2475,0,4.2825,0.9187,5.7555,2.4036
                                        c1.4729,1.4849,2.3841,3.5362,2.3841,5.8014s-0.9112,4.3165-2.3841,5.8015c-1.473,1.4849-3.508,2.4035-5.7555,2.4035
                                        s-4.2826-0.9186-5.7555-2.4035c-1.473-1.485-2.3841-3.5363-2.3841-5.8015c0-2.2652,0.9111-4.3165,2.3841-5.8014
                                        C154.5961,73.7343,156.6312,72.8156,158.8787,72.8156z M164.4944,75.3581c-1.437-1.4486-3.4225-2.3448-5.6157-2.3448
                                        c-2.1933,0-4.1788,0.8962-5.6158,2.3448c-1.4372,1.4489-2.3261,3.451-2.3261,5.6625c0,2.2116,0.8889,4.2137,2.3261,5.6625
                                        c1.437,1.4487,3.4225,2.3449,5.6158,2.3449c2.1932,0,4.1787-0.8962,5.6157-2.3449c1.4372-1.4488,2.3262-3.4509,2.3262-5.6625
                                        C166.8206,78.8091,165.9316,76.807,164.4944,75.3581z"/>
                                    </svg>
                                </a>
                                <a v-if="item.status && (item.status == 'generated' || item.status == 'printed' )" @click="printTicket({id:item.id, order_id:item.order_id})" class="action-button -adicionar" data-tip="Imprimir etiqueta">
                                    <svg class="ico" version="1.1" id="imprimirok" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                        viewBox="0 0 228.2998 219.331" enable-background="new 0 0 228.2998 219.331" xml:space="preserve">
                                    <path id="imprimirok-path4" d="M60.1948,34.8006H130.35c5.3073,0,10.1271,2.1659,13.6165,5.6554
                                        c3.4895,3.4894,5.6554,8.3092,5.6554,13.6165v29.3652h21.6803c5.4433,0,10.3867,2.2215,13.9654,5.8006
                                        c3.579,3.579,5.8005,8.5223,5.8005,13.9657v62.1068c0,5.4434-2.2215,10.3867-5.8005,13.9655
                                        c-3.5787,3.579-8.5221,5.8005-13.9654,5.8005h-20.1121v17.763c0,4.5425-1.8533,8.6672-4.8385,11.6527
                                        c-2.9854,2.9854-7.1101,4.8384-11.6529,4.8384H55.0601c-4.5428,0-8.6674-1.8533-11.6529-4.8384
                                        c-2.9852-2.9855-4.8385-7.1102-4.8385-11.6527v-17.763H19.766c-5.4434,0-10.3867-2.2215-13.9655-5.8005
                                        C2.2215,175.6975,0,170.7542,0,165.3108V103.204c0-5.4434,2.2215-10.3867,5.8005-13.9657
                                        c3.5788-3.5791,8.5221-5.8006,13.9655-5.8006h21.1569V54.0725c0-5.3073,2.166-10.1271,5.6554-13.6165
                                        C50.0675,36.9665,54.8872,34.8006,60.1948,34.8006z M158.8788,107.7151c4.4407,0,8.0407,3.6292,8.0407,8.1062
                                        c0,4.4767-3.6,8.1062-8.0407,8.1062c-4.4408,0-8.0408-3.6295-8.0408-8.1062C150.838,111.3443,154.438,107.7151,158.8788,107.7151z
                                        M69.6444,194.894c-2.3743,0-4.299-2.2124-4.299-4.9416c0-2.7289,1.9247-4.9414,4.299-4.9414h50.7291
                                        c2.3743,0,4.299,2.2125,4.299,4.9414c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M69.6444,176.7205
                                        c-2.3743,0-4.299-2.2124-4.299-4.9416s1.9247-4.9414,4.299-4.9414h50.7291c2.3743,0,4.299,2.2122,4.299,4.9414
                                        c0,2.7292-1.9247,4.9416-4.299,4.9416H69.6444z M136.3657,185.0768v-27.8807c0-0.4507-0.1899-0.866-0.4955-1.1716
                                        c-0.3055-0.3056-0.7208-0.4952-1.1715-0.4952H55.0601c-0.4507,0-0.8659,0.1896-1.1715,0.4952
                                        c-0.3056,0.3056-0.4952,0.7209-0.4952,1.1716v27.8807v17.763c0,0.4504,0.1896,0.8657,0.4952,1.1713
                                        c0.3056,0.3056,0.7208,0.4955,1.1715,0.4955h79.6386c0.4507,0,0.866-0.1899,1.1715-0.4955
                                        c0.3056-0.3056,0.4955-0.7209,0.4955-1.1713V185.0768L136.3657,185.0768z M149.6219,98.2624H40.9229H19.766
                                        c-1.351,0-2.5849,0.5581-3.4841,1.4573c-0.8991,0.8991-1.4573,2.133-1.4573,3.4843v62.1068c0,1.351,0.5582,2.5849,1.4573,3.4841
                                        c0.8992,0.8991,2.1331,1.4573,3.4841,1.4573h18.8027v-13.0561c0-4.5428,1.8531-8.6673,4.8385-11.653
                                        c2.9855-2.9851,7.1101-4.8384,11.6529-4.8384h79.6386c4.5428,0,8.6675,1.8533,11.6529,4.8384
                                        c2.9855,2.9857,4.8385,7.1102,4.8385,11.653v13.0561h20.1121c1.351,0,2.5849-0.5582,3.484-1.4573
                                        c0.8992-0.8992,1.4573-2.1331,1.4573-3.4841v-62.1068c0-1.3513-0.5581-2.5852-1.4573-3.4843
                                        c-0.8991-0.8992-2.133-1.4573-3.484-1.4573L149.6219,98.2624L149.6219,98.2624z M130.35,49.6252H60.1948
                                        c-1.2155,0-2.3258,0.5026-3.1354,1.3122c-0.8093,0.8096-1.3121,1.9199-1.3121,3.1351v29.3652h79.05V54.0725
                                        c0-1.2152-0.5026-2.3255-1.3121-3.1351C132.6759,50.1278,131.5653,49.6252,130.35,49.6252z"/>
                                    <path id="imprimirok-path6" d="M158.8787,107.6162c2.2475,0,4.2825,0.9187,5.7555,2.4036
                                        c1.4729,1.4849,2.3841,3.5362,2.3841,5.8014s-0.9112,4.3165-2.3841,5.8015c-1.473,1.4849-3.508,2.4035-5.7555,2.4035
                                        s-4.2826-0.9186-5.7555-2.4035c-1.473-1.485-2.3841-3.5363-2.3841-5.8015c0-2.2652,0.9111-4.3165,2.3841-5.8014
                                        C154.5961,108.5349,156.6312,107.6162,158.8787,107.6162z M164.4944,110.1587c-1.437-1.4486-3.4225-2.3448-5.6157-2.3448
                                        c-2.1933,0-4.1788,0.8962-5.6158,2.3448c-1.4372,1.4489-2.3261,3.451-2.3261,5.6625c0,2.2116,0.8889,4.2137,2.3261,5.6625
                                        c1.437,1.4487,3.4225,2.3449,5.6158,2.3449c2.1932,0,4.1787-0.8962,5.6157-2.3449c1.4372-1.4488,2.3262-3.4509,2.3262-5.6625
                                        C166.8206,113.6097,165.9316,111.6076,164.4944,110.1587z"/>
                                    <path id="imprimirok-path8" fill="#2BC866" d="M228.2998,42.8513c0,23.6661-19.1852,42.8513-42.8513,42.8513l0,0
                                        c-23.6661,0-42.8513-19.1852-42.8513-42.8513S161.7824,0,185.4485,0S228.2998,19.1852,228.2998,42.8513z"/>
                                    <g id="imprimirok-layer1000">
                                        <path id="imprimirok-path11" fill="#FFFFFF" d="M175.6407,63.0407c0.4235,0.4236,0.9982,0.6616,1.5973,0.6616
                                            c0.5992,0,1.1738-0.2381,1.5972-0.6616l30.7956-30.7956c0.4238-0.4236,0.6617-0.9981,0.6617-1.5972
                                            c0-0.5993-0.2379-1.1738-0.6617-1.5974l-6.3891-6.389c-0.882-0.882-2.3123-0.8822-3.1946,0l-22.8085,22.8088l-6.3894-6.3894
                                            c-0.4236-0.4236-0.9982-0.6617-1.5973-0.6617c-0.5991,0-1.1735,0.2381-1.5972,0.6617l-6.3892,6.3891
                                            c-0.882,0.8822-0.882,2.3124,0,3.1946L175.6407,63.0407L175.6407,63.0407z"/>
                                        <g id="imprimirok-layer1001">
                                        </g>
                                        <g id="imprimirok-layer1002">
                                        </g>
                                        <g id="imprimirok-layer1003">
                                        </g>
                                        <g id="imprimirok-layer1004">
                                        </g>
                                        <g id="imprimirok-layer1005">
                                        </g>
                                        <g id="imprimirok-layer1006">
                                        </g>
                                        <g id="imprimirok-layer1007">
                                        </g>
                                        <g id="imprimirok-layer1008">
                                        </g>
                                        <g id="imprimirok-layer1009">
                                        </g>
                                        <g id="imprimirok-layer1010">
                                        </g>
                                        <g id="imprimirok-layer1011">
                                        </g>
                                        <g id="imprimirok-layer1012">
                                        </g>
                                        <g id="imprimirok-layer1013">
                                        </g>
                                        <g id="imprimirok-layer1014">
                                        </g>
                                        <g id="imprimirok-layer1015">
                                        </g>
                                    </g>
                                    </svg>
                                </a>
                                <a v-if="item.status && item.order_id && item.id && item.status != 'paid'" @click="removeCart({id:item.id, order_id:item.order_id})" href="javascript:;" class="action-button -excluir" data-tip="Cancelar">
                                    <svg class="ico" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 383.2 500"><title>Cancelar</title><g id="Camada_2" data-name="Camada 2"><g id="Camada_10" data-name="Camada 10"><path class="cls-1" d="M304.95,62.21H267.32v-.62c0-20.76-8.31-37.36-24-48C230,4.57,212.08,0,190,0s-40,4.57-53.31,13.57c-15.72,10.65-24,27.26-24,48v.62H78.25C43.15,62.21,0,106.59,0,142.7a9.41,9.41,0,0,0,9.41,9.41H15V490.59A9.41,9.41,0,0,0,24.42,500H358.54a9.41,9.41,0,0,0,9.41-9.41V462.17a9.41,9.41,0,0,0-18.83,0v19H33.83V152.12H349.12v263a9.41,9.41,0,0,0,18.83,0v-263h5.84a9.41,9.41,0,0,0,9.41-9.41C383.2,106.59,340.05,62.21,304.95,62.21Zm-173.46-.62c0-19.51,10.15-42.77,58.51-42.77s58.51,23.26,58.51,42.77v.62h-117ZM20.24,133.29c2.79-10,9.57-21.14,19-31C51.89,89.18,66.82,81,78.25,81H304.95c11.43,0,26.36,8.15,39,21.26,9.48,9.86,16.26,21,19,31Z"/><path class="cls-1" d="M98.57,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/><path class="cls-1" d="M182.13,217.67V415.1a9.41,9.41,0,1,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/><path class="cls-1" d="M265.69,217.67V415.1a9.41,9.41,0,0,0,18.83,0V217.67a9.41,9.41,0,1,0-18.83,0Z"/></g></g></svg>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
        <div v-else><p>Nenhum registro encontrado</p></div>
        <button v-show="show_more" class="btn-border -full-green" @click="loadMore({status:status, wpstatus:wpstatus})">Carregar mais</button>

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
        </div>

    </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'

export default {
    name: 'Pedidos',
    data: () => {
        return {
            status: 'all',
            wpstatus: 'all',
        }
    },
    computed: {
        ...mapGetters('orders', {
            orders: 'getOrders',
            show_loader: 'toggleLoader',
            msg_modal: 'setMsgModal',
            show_modal: 'showModal',
            show_more: 'showMore'
        }),
        ...mapGetters('balance', ['getBalance'])
    },
    methods: {
        ...mapActions('orders', [
            'retrieveMany',
            'loadMore',
            'addCart',
            'removeCart',
            'cancelCart',
            'payTicket',
            'createTicket',
            'printTicket',
            'closeModal',
            'insertInvoice'
        ]),
        ...mapActions('balance', ['setBalance']),
        close() {
            this.closeModal()
        },
        buttonCartShow(...args) {
            const [
                choose_method, 
                non_commercial, 
                number, 
                key,
                status
            ] = args

            if (status == 'paid') {
                return false;
            }

            if (status == 'printed') {
                return false;
            }

            if (status == 'generated') {
                return false;
            }

            if (status == 'pending') {
                return false;
            }

            if (choose_method == 1 || choose_method == 2) {
                return true
            }

            if ((choose_method == 3 || choose_method == 4) && non_commercial) {
                return true
            }

            if ((choose_method == 3 || choose_method == 4) && !non_commercial && (number != null && number != '')) {
                return true
            }

            if (choose_method > 3 &&  (number != null && number != '')) {
                return true
            }
            
            return false;
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
        if (Object.keys(this.orders).length === 0) {
            this.retrieveMany({status:this.status, wpstatus:this.wpstatus})
        }
        this.setBalance()
    }
}
</script>

<style lang="css" scoped>
</style>