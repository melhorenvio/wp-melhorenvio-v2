=== Melhor Envio V2 ===
Version: 2.7.7
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Tested up to: 5.6
Requires PHP: 5.6


# Plugin Melhor Envio

Com o Melhor Envio é possível fazer gratuitamente cotações simultâneas com os Correios e diversas transportadoras privadas de forma ágil e eficiente. A plataforma possui contratos com várias empresas de logística para oferecer fretes em condições mais competitivas aos vendedores online.
A tecnologia já ajudou mais de 50 mil lojistas a otimizar a gestão de fretes acessando uma série de vantagens exclusivas sem precisar negociar individualmente com as transportadoras.
Simplifique o envio de mercadorias sem volume mínimo de pedidos e administre o transporte de suas remessas em um só lugar. Livre de mensalidades ou contratos individuais.
Utilize um painel exclusivo para comprar etiquetas de postagem e acompanhar a movimentação das encomendas com um rastreio inteligente. Com o Melhor Envio você pode escolher diferentes modalidades de frete pagando apenas pelas etiquetas geradas no sistema.

### Funcionalidades do Plugin WooCommerce

Com a instalação do plugin do Woocomerce você pode ampliar ainda mais a automação dos fretes de sua loja virtual. Confira os principais benefícios e vantagens personalizadas disponíveis:
- Cotação dos envios com as funcionalidades do Melhor Envio direto na tela do produto.
- Conexão da Loja WooCommerce com a conta do Melhor Envio para buscar automaticamente informações como endereços, lojas e documentos (CNPJ, Inscrição estadual) e saldo em carteira.
- Buscar todos pedidos da Loja WooCommerce do vendedor, com filtros de status da compra e por status da etiqueta de envio.
- Cotar a compra de etiqueta usando os dados da loja e do cliente no painel.
- Enviar etiquetas de postagem para o carrinho de compras do Melhor Envio.
- Comprar etiquetas no painel do Wordpress utilizando saldo do Melhor Envio.
- Gerar, imprimir ou cancelar etiquetas do Melhor Envio pelo painel do Wordpress.
- Adicionar taxas e tempo extra para as etiquetas (exemplo: inserir um custo extra para embalagem, aumentar 2 dias no tempo de entrega).
- Possibilidade de selecionar a Jadlog como agência padrão para geração de etiquetas.

### Instalação

A instalação é super simples, em seu painel wordpress, basta acessar o menu Plugins -> Adicionar novo, em seguida realizar a busca por "Melhor Envio", ao retornar a plugin basta clicar em "Instalar agora". Ou se preferir bastar fazer o downlaod do plugin na página oficial do plugin no portal do Wordpress e mover o arquivo .Zip para o diretório wp-content/plugins. O próximo passo, é acessar todos os plugins pelo menu Plugins -> Plugins instalados, encontrar o plugin "Melhor Envio" e clicar em "Ativar".

O próximo passo para utilizar o plugin é gerar um token na plataforma da Melhor Envio. Para isso, você precisa acessar o <a target="_blank" href=“https://melhorenvio.com.br/painel/gerenciar/tokens“>link</a> e clicar em "Novo token", inserir um nome para o token, selecionar as permissões e clicar em "Salvar". Você deve copiar o token gerado, e colar o mesmo no painel do Wordpress, acessando o menu Melhor Envio -> Token.

Agora que sua conta Melhor Envio está vinculada com nosso plugin, basta selecionar os métodos de envios, acessando o Menu WooCommerce -> Configurações -> Entrega. Agora você precisa escolhar as áreas que deseja enviar seus produtos utilizando a Melhor Envio. Por padrão, existe a opção "Em toda parte", que seria a área geral do Brasil. Basta clicar em "Editar" logo abaixo do nome da área e selecionar os métodos de envio e para finalizar clicar em "Salvar".

Pronto! o plugin do Melhor Envio está funcionando.

Caso ainda tenha duvidas ou deseja um guia com mais detalhe da integração, segue o link para o artigo com o passo a passo para ajudar na integração: <a target="_blank" href=“https://central.melhorenvio.com.br/pt-BR/articles/1919691-manual-de-integracao-plataforma-wordpress-woocommerce“>Link do artigo</a>.

### Pré-requisitos

- PHP v.5.6
- Wordpress 4.0+
- WooCommerce 4.0+

### Compatibilidade

Caso seja utilizado algum outro plugin que altere o Woocommerce não é garantida a sua compatibilidade, como plugins que adicionam ao Woocommerce funcionalidades de marketplace.

## Contribuindo com o Projeto

Caso queira contribuir com o projeto, o processo para isto é criar um brach separado, implementar o desejado, e encaminhar um pull request para o develop, com descrição da alteração.

## Suporte
Para entrar em contato com o suporte desse plugin, enviar e-mail para dev@melhorenvio.com

## Autores

* **Vinicius Tessmann** - *Melhor Envio* - [viniciustessmann](https://github.com/viniciustessmann)
* **Marcos Brito** - *Melhor Envio* - [MarcosWillian](https://github.com/MarcosWillian)
* **Samuel Desconsi** - *Melhor Envio* - [SamXDesc](https://github.com/SamXDesc)

## CHANGELOG
### [2.8.2]
- Ajuste cálculo de produto com variação na tela do produto.
- Adicionando percentual extra sobre o valor do frete.
- Ajuste para salvar dados do vendedor nos options do Wordpress.

### [2.8.1] - 2020-08
- Retorno do botão de enviar pedido para o carrinho de compras do Melhor Envio.
- Correção de problemas com valores diferentes entre plugin e Melhor Envio.
- Correções de pequenos problemas em versões antigas do PHP.
- Ordenação de cotação pelo preço.
- Ajuste nos dados do remetente na etiqueta, exibindo dados e endereço da loja selecionada.
- Solução do problema de lentidão da calculadora do produto.

### [2.8.0] - 2020-08
- Validação de cotação de correios com volumes.
- Classes de entregas
- Problemas de lentidão
- Correção na atualização de documentos do envio (nota fiscal)
- Solução do problema de "CEP inválido" na tela do produto
- Ordenação de frete pelo menor preço
- Ajuste na exibição de frete grátis.

#### [2.7.8] - 2020-07-17
- Correção no calculo de frete de produto com pesos em gramas.

#### [2.7.7] - 2020-07
- Ajustes Correios Mini

#### [2.7.6] - 2020-07
- Correções de bugs
- Desativação de azul cargo em zonas não autorizadas

#### [2.7.2] - 2020-05
- Possibilidade de utilizar ambiente Sandbox do Melhor Envio
- Novo serviço de envios Azul Cargo (Verificar disponibilidade para a sua região)
- Melhorias no fluxo de compras de etiquetas, compras com 1 click
- Melhorias na verificação da embalagem do pedido
- Opção de selecionar múltiplas etiquetas para comprar ou imprimir
- Exibição de informações do usuário na tela de pedidos (Nome, email, saldo, ambiente e limite de envios)
- Ajuste nas respostas de erros ao comprar etiquetas
- Exibição de link de rastreio do Melhor Rastreio nos pedidos (painel)

#### [2.6.2] - 2020-01
- Adicionado opção de visualizar todas as agencias Jadlog do estado.
- Ajustes na lógica de cotação na pagina do produto.
#### [2.6.1] - 2019-10
- Adicionado validação da data de expiração do Token.
- Ajuste da validação do peso minimo do produto para o serviço PAC Mini.
#### [2.6.0] - 2019-09
- Adição do método de pagamento PAC Mini.
- Ajuste na cotação de produtos quando há restrição de região.
#### [2.5.17] - 2019-08
- Remoção do cache do título da loja.
- Remoção do cache das informações do titular da conta ME.
- Ajustes na lógica de validação da cotação.
#### [2.5.16] - 2019-08
- Ajustes do erro 500 na listagem das lojas.
#### [2.5.15] - 2019-08
- Adição campo de situação na listagem de pedidos
- Adição de label para campos de taxas e valores extras (máscara de valores) para os métodos de envios
- Adição de aviso de plugin sem token do Melhor Envio
- Ajuste para não exibir pedidos apagados
- Ajuste para exibir o valor da etiqueta após ser enviada para o carrinho de compras
- Ajuste na quantidade de produto na cotação da tela do produto
- Ajustes valor de pedido com várias etiquetas
#### [2.5.14] - 2019-07-04
- Solução do problema do loading infinito após salvar as configurações
- Ajustes na cotaçaõ de frete na tela do produto
- Remoção de funções não utiliazadas no core do plugin
#### [2.5.13] - 2019-07-01
- Problemas de JS na tela do produto
#### [2.5.10] - 2019-06-28
- Problemas de produtos com variações
#### [2.5.9] - 2019-06-27
- Diferença valor frete na tela do produto
### [2.5.8] - 2019-06-24
- Erro ao adicionar ao carrinho
#### [2.5.4] - 2019-06-
- Opção de escolher AR e MP nas configurações
- Melhorias na atualização de fretes quando editado o pedido
- Notificação de errors na listagem de pedidos