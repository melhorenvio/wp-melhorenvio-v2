### Changelog
### [2.9.20]
- Remoção da obrigatoriedade de agências para Via Brasil e Jadlog

### [2.9.19]
- Correção do erro fatal da versão 2.9.17

### [2.9.17]
- Adição de alerta em casos de token inválidos ou expirados

### [2.9.16]
- Correção para o problema de lentidão ao inserir um produto no carrinho de compras

### [2.9.14]
- Ajustes validações payload para API do Melhor Envio
- Ajuste no uso de valor segurado nas cotações
- Adição de uso de CNAE para transportadoras privadas
- Validações para CEP

### [2.9.11]
- Validação de cep de destino para cotação

### [2.9.10]
- Correção do aviso de erro de session_start()
- Correção do problema ocorrido em algumas lojas ao inserir produtos no carrinho de compras

### [2.9.9]
- Criando helper para iniciar session
- Correções de warning de PHP
- Correção do problema de não exibr botões de compras da etiqueta na listagem de pedidos
- Correção no processo de finalização de compras de produtos virtuais
- Ajuste para usar valores com centavos nas taxas extras de envios
- Ajuste no aviso que o usuário não selecionou um método do Melhor Envio mesmo selecioando um método do Melhor Envio
- Correção do problema de link de rastreio na aba de pedidos dos clientes
- Ajuste erro 500 na listagem de pedidos
- Removendo listagem duplicada de rastreio

### [2.9.7]
- Ajuste na finalização de pedidos de produtos virtuais e remoção de pedidos virtuais na listagem de pedidos do plugin. 

### [2.9.6]
- Ajustes no método de serviço selecionado pelo comprador.
- Função para utilizar sempre a maior taxa, prazo ou percentual extra com base nas classes de entregas dos produtos inseridos no carrinho de compras.
- Listagem de plugins incompatíveis.
-  Ajuste no redirecionamento após salvar o token do Melhor Envio.
- Adicionando serviço LATAM CArgo Juntos
- Adicioando seletor de unidades LATAM Cargos no painel administrativo

### [2.9.5]
- Encerando session após escrita.
- Correção de warning de encerrar session antes de realizar requests HTTP.
- Adicionando serviços de Azul Cargo.
- Correção dados da etiqueta referente aos dados de entrega e faturamento.
- Correção de problema na exibição de rastreio na tela de meus pedidos do usuário final.
- Inserindo mensagem de observação de frete grátis caso necessário no shortcode da calculadora.

### [2.9.4]
- Correção do problema de loop infinito na aba de configurações.

### [2.9.3]
- Correção de caracteres quebrados no endereço de destino na etiqueta Melhor Envio.

### [2.9.2]
- Criação de função de usuário para suporte do Melhor Envio (Acesso restrito).
- Removendo requisiçoes desnecessárias para rota shipping/services na API do Melhor Envio.
- Enviando a versão do plugin do Melhor Envio no cabeçalho nas requisições para a API do melhor envio.
- Serviço de notificação de erros no plugin.
- Exibindo valor segurado na listagem de pedidos.

### [2.9.1]
- Correção error fatal ao enviar produto para o carrinho de compras

### [2.9.0]
- Correção da exibição de frete grátis e taxa fixa.
- Ajuste cálculo de produto com variação na tela do produto.
- Adicionando percentual extra sobre o valor do frete.
- Adicionando função para cancelar etiquetas.
- Opção de ativar/desativar valor segurado
- Ajuste para salvar dados do vendedor nos options do Wordpress.
- Correções de warnings

### [2.8.1] - 2020-08
- Retorno do botão de enviar pedido para o carrinho de compras do Melhor Envio.
- Correção de problemas com valores diferentes entre plugin e Melhor Envio.
- Correções de pequenos problemas em versões antigas do PHP.
- Ordenação de cotação pelo preço.
- Ajuste nos dados do remetente na etiqueta, exibindo dados e endereço da loja selecionada.
- Solução do problema de lentidão da calculadora do produto.

## [2.4.7] - 2019-08-26
## Fixed
- Adicionado novo serviço: Correios Mini

## [2.4.6] - 2019-05-14
## Fixed
- Ajuste no css dos input na tela do produto

## [2.4.5] - 2019-05-08
## Fixed
- Ajuste no metodo get_product do woocommerce
- Ajuste para não exibir prazo de entrega junto ao nome do metodo na calculadora do produto

## [2.4.4] - 2019-05-08
## Fixed
- Ajuste no layout

## [2.4.2] - 2019-04-23
## Fixed
- Correção erro 500

## [2.4.0] - 2019-04-22
## Fixed
- Correção erro 500

## [2.3.9] - 2019-04-18
## Fixed
- Melhorias no desempenho do plugin
- utilização de cupom de frete grátis
- Solução de bugs

## [2.3.8] - 2019-04-01
## Added
- Adicionado a opção de definir o path do folder dos plugins (Solução do erro do ABSPATH de alguns usuários)

## [2.3.7] - 2019-03-21
## Fixed
- Customização do layout da calculadora

## [2.3.6] - 2019-03-20
## Added
- Declaração de conteúdo latam Cargo

## [2.3.5] - 2019-03-14
## Fixed
- Erro ao instalar o plugin

## [2.3.3] - 2019-03-12
## Fixed
- Erro ABSPATH

## [2.3.2] - 2019-03-08
## Fixed
- Erro chamada JS cotação na tela do produto

## [2.3.1] - 2019-03-08
## Fixed
- Erro conversão peso na calculadora na tela do produto


## [2.3.0] - 2019-02-22
## Added
- Abertura da transportadora Latam Cargo para algumas areas

## [2.2.17] - 2019-02-21
## Added
- Placeholder input cep tela do produto
- Add valor segurado na rota de analisar a dados da cotação

## [2.2.16] - 2019-01-29
### Fixed
- Ajuste retorno mensagem saldo insuficiente
- Ajuste no problema de não retornar cotação na listagem de pedidos

## [2.2.15] - 2019-01-17
### Fixed
- Ajuste para o erro 500 na ativação do plugin para alguns clientes

## [2.2.14] - 2019-01-14
### Fixed
- Ajuste para o plugin funcionar em multisite
- Upload de assets/img na S3 amazon 

## [2.2.13] - 2019-01-11
### Fixed
- Ajuste para algumas lojas que não retornavam metodos de envio devido erro na primeira autenticação
- Rota para analisar status do Plugin

## [2.2.12] - 2019-01-03
### Fixed
- Ajuste o botão de recalcular frete no painel administrativo

## [2.2.11] - 2018-12-27
### Added
- função para adicionar percentual extra na cotação para o cliente

### Fixed
- Ajuste problema com peso na inserção da etiqueta no carrinho de compras

## [2.2.10] - 2018-12-20
### Fixed
- Ajuste status personalizados do Woocommerce 

## [2.2.9] - 2018-12-20
### Added
- testes versçoes mais atuais do Wordpress

## [2.2.8] - 2018-12-19
### Added
- Alteração do nome do método de envio
- Alteração do local da exibicação da calculadora na tela do produto

## [2.2.7] - 2018-12-19
### Added
- Filtro com status personalizados

## [2.2.5] - 2018-12-10
### fixed
- Ajuste na função de recalcular frete

## [2.2.4] - 2018-12-07
### fixed
- Remoção frase "Feito com amor por Melhor Envio" da cotação da página do produto
- Ajustes na listagem de pedidos, alguns pedidos quebraram a aplicação

## [2.2.2] - 2018-12-07
### fixed
- Ajsute para resolver problema de alguns clientes que retornava erro 500 na listagem de pedidos.

## [2.2.1] - 2018-12-05
### fixed
- Validação se o usuário comprou uma etiqueta da Melhor Envio

## [2.2.0] - 2018-12-05
- Nova versão