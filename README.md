=== Melhor Envio V2 ===
Version: 2.5.14
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Tested up to: 5.0
Requires PHP: 5.0


# Plugin Melhor Envio

Com o Melhor Envio é possível fazer gratuitamente cotações simultâneas com os Correios e diversas transportadoras privadas de forma ágil e eficiente. A plataforma possui contratos com várias empresas de logística para oferecer fretes em condições mais competitivas aos vendedores online. 
A tecnologia já ajudou mais de 50 mil lojistas a otimizar a gestão de fretes acessando uma série de vantagens exclusivas sem precisar negociar individualmente com as transportadoras. 
Simplifique o envio de mercadorias sem volume mínimo de pedidos e administre o transporte de suas remessas em um só lugar. Livre de mensalidades ou contratos individuais.
Utilize um painel exclusivo para comprar etiquetas de postagem e acompanhar a movimentação das encomendas com um rastreio inteligente. Com o Melhor Envio você pode escolher diferentes modalidades de frete pagando apenas pelas etiquetas geradas no sistema. 

### Funcionalidades do Plugin WooCommerce

Com a instalação do plugin do Woocomerce você pode ampliar ainda mais a automação dos fretes de sua loja virtual. Confira os principais benefícios e vantagens personalizadas disponíveis:
- Cotação dos envios com as funcionalidades do Melhor Envio direto na tela do produto.
- Conexão da Loja WooCommerce com a conta do Melhor envio para buscar automaticamente informações como endereços, lojas e documentos (CNPJ, Inscrição estadual) e saldo em carteira.
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

### Pré-requisitos

- PHP v.5.6
- Wordpress 4.0+
- WooCommerce 4.0+

## Contribuindo com o Projeto

Caso queira contribuir com o projeto, o processo para isto é criar um brach separado, implementar o desejado, e encaminhar um pull request para o develop, com descrição da alteração.

## Suporte
Para entrar em contato com o suporte desse plugin, enviar e-mail para dev@melhorenvio.com

## Autores

* **Vinicius Tessmann** - *Melhor Envio* - [viniciustessmann](https://github.com/viniciustessmann)
* **Samuel Desconsi** - *Melhor Envio* - [underzzoo](https://github.com/underzzoo)

## [2.5.13] - 2019-07-01
## Fixed
- Problemas de JS na tela do produto

## [2.5.10] - 2019-06-28
## Fixed
- Problemas de produtos com variações

## [2.5.9] - 2019-06-27
## Fixed
- Diferença valor frete na tela do produto


## [2.5.8] - 2019-06-24
## Fixed
- Erro ao adicionar ao carrinho

## [2.5.4] - 2019-06-
## Added 
- Opção de escolher AR e MP nas configurações
- Melhorias na atualização de fretes quando editado o pedido
- Notificação de errors na listagem de pedidos

## [2.5.3] - 2019-06-13
## Fixed 
- Erro ao cotar sem cep de destino sem estar logado.

## [2.5.1] - 2019-06-12
## Fixed 
- Ajustes de desempenho
- Correção cupom de desconto na tela do produto
- Não exibir calculadora para produto virtual
- Página com histórico de cotação do pedido

## [2.5.0] - 2019-05-30
## Added
- Suporte multisite

## [2.4.10] - 2019-05-28
## Fixed
- Cupom de desconto exibindo frete grátis
## Fixed
- Melhorias no cupom de desconto

## [2.4.8] - 2019-05-15
## Fixed
- Ajuste no acréscimo de tempo extra

## [2.4.6] - 2019-05-14
## Fixed
- Ajuste no css dos input na tela do produto

### Changelog
## 2.4.5
## Fixed
- Ajuste no metodo get_product do woocommerce
- Ajuste para não exibir prazo de entrega junto ao nome do metodo na calculadora do produto

### Changelog
## 2.4.4
- Ajustes no layout

### Changelog
## 2.4.3
- Melhorias no visual da calculdora de frete
- Validação se produto tem as dimensões para realizar o calculo na tela do produto 

### Changelog
## 2.4.2
-  Utilização de métodos de envios padrões do WooCommerce no calculo de frete na tela do produto
