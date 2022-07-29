=== Melhor Envio ===
Version: 2.11.29
Tags: frete, fretes, cotação, cotações, correios, envio, jadlog, latam latam cargo, azul, azul cargo express, melhor envio
Requires at least: 4.7
Tested up to: 6.0
Stable tag: 2.11.29
Requires PHP: 7.2+
Requires Wordpress 4.0+
Requires WooCommerce 4.0+
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin para cotação e compra de fretes utilizando a API da Melhor Envio.

== Description ==
Com o Melhor Envio é possível fazer gratuitamente cotações simultâneas com os Correios e diversas transportadoras privadas de forma ágil e eficiente. A plataforma possui contratos com várias empresas de logística para oferecer fretes em condições mais competitivas aos vendedores online.
A tecnologia já ajudou mais de 50 mil lojistas a otimizar a gestão de fretes acessando uma série de vantagens exclusivas sem precisar negociar individualmente com as transportadoras.
Simplifique o envio de mercadorias sem volume mínimo de pedidos e administre o transporte de suas remessas em um só lugar. Livre de mensalidades ou contratos individuais.
Utilize um painel exclusivo para comprar etiquetas de postagem e acompanhar a movimentação das encomendas com um rastreio inteligente. Com o Melhor Envio você pode escolher diferentes modalidades de frete pagando apenas pelas etiquetas geradas no sistema.

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

### Compatibilidade
Caso seja utilizado algum outro plugin que altere o Woocommerce não é garantida a sua compatibilidade, como plugins que adicionam ao Woocommerce funcionalidades de marketplace.

## Contribuindo com o Projeto
Caso queira contribuir com o projeto, o processo para isto é criar um brach separado, implementar o desejado, e encaminhar um pull request para o develop, com descrição da alteração.
<a href="https://github.com/melhorenvio/wp-melhorenvio-v2" target="_blank">Repositório público do Plugin do Melhor Envio</a>

## Suporte
Para entrar em contato com o suporte desse plugin, enviar e-mail para dev@melhorenvio.com

== Installation ==
A instalação do plugin é simples, basta acessar a aba "Plugins > Instalar novo" no seu painel administrativo do wordpress e buscar pelo plugin "Melhor Envio" na barra de busca.

Ou se preferir basta fazer o download do plugin na página oficial do plugin no portal do Wordpress e mover o arquivo .Zip para o diretório wp-content/plugins. O próximo passo, é acessar todos os plugins pelo menu Plugins -> Plugins instalados, encontrar o plugin "Melhor Envio" e clicar em "Ativar".

Você também vai precisar do plugin <a href"https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/" target="_blank">Brazilian Market on WooCommerce</a> para o perfeito funcionamento do plugin do Melhor Envio.

O próximo passo para utilizar o plugin é gerar um token na plataforma da Melhor Envio. Para isso, você precisa acessar o <a target="_blank" href="https://melhorenvio.com.br/painel/gerenciar/tokens">link</a> e clicar em "Novo token", inserir um nome para o token, selecionar as permissões e clicar em "Salvar". Você deve copiar o token gerado, e colar o mesmo no painel do Wordpress, acessando o menu Melhor Envio -> Token.
painel administrativo do wordpress e buscar pelo plugin "Melhor Envio" na barra de busca.
 
Agora que sua conta Melhor Envio está vinculada com nosso plugin, basta selecionar os métodos de envios, acessando o Menu WooCommerce -> Configurações -> Entrega. Agora você precisa escolher as áreas que deseja enviar seus produtos utilizando a Melhor Envio. Por padrão, existe a opção "Em toda parte", que seria a área geral do Brasil. Basta clicar em "Editar" logo abaixo do nome da área e selecionar os métodos de envio e para finalizar clicar em "Salvar".

Não esqueça de cadastrar de forma correta as medidas do produto na aba de entrega de cada produto cadastrado.

Observação: Atenção com as medidas de unidades utilizadas, cuidado se você está utilizando gramas ou quilos, metros ou centímetros, isso vai aplicar no valor da cotação e no bom funcionamento da calculadora de fretes do Melhor Envio.
 
Pronto! o plugin do Melhor Envio está funcionando.

== Changelog ==
= 2.11.29 =
* Correção do bug não enviar o CEP de origem na calculadora da tela de produto (Erro apenas em alguns temas)
* Correção na validação de nome e telefone para transportadoras

= 2.11.28 =
* Adicioando fluxo automatizado de deploy

= 2.11.25 =
* Correção para o problema de não salvar as configurações em algumas lojas.

= 2.11.24 =
* Correção para o problema de não exibir a calculadora em algumas lojas.

= 2.11.23 =
* Correção para não retornar itens indisponíveis da cotação no painel administrativo.

= 2.11.22 =
* Correção de requests com custom nonce.

= 2.11.21 =
* Ajustes de segurança.

= 2.11.13 =
* Ajuste na busca de dados dos pedidos.

= 2.11.12 =
* Ajuste para comprar pedidos de clientes com CNPJ
* Ajuste na listagem de endereços de lojas no painel administrativo.

= 2.11.11 =
* Correções de seguranças.

= 2.11.9 =
* Ajuste para resolver o problema de alteração de endereços.

= 2.11.8 =
* Ajuste para permitir desconto nos métodos de envio.
* Correção de vulnerabilidade no redirect para tela de configurações

= 2.11.7 =
* Correção do problema de inserção de pediddos no carrinho de compras.

= 2.11.6 =
* Correção do problema de lentidão ao inserir um produto no carrinho de compras.
* Correção de filtro de busca de agências por estado.
* Pequenas correções na estrutura do plugin.

= 2.11.5 =
* Correção para permitir apenas token de sandbox nas configurações.
* Melhorias no desempenho.
* Ajustes no método de adição de percentual extra sobre o valor final da cotação.

= 2.11.4 =
* Correção de erro crítico ao ativar o plugin em algumas lojas.

= 2.11.3 =
* Ajustes para melhorar o desempenho na busca de dados do vendedor.
* Ajustes na opção de remover a calculadora do Melhor Envio da tela do produto.

= 2.11.1 =
* Ajustes no método de busca de agências, melhorando o desempenho da busca.

= 2.11.0 =
* Criação da configuração para embalagem padrão para casos de produtos sem dimensões cadastradas

= 2.10.1 =
* Ajuste para não exibir lojas sem endereços cadastrados
* Ajuste para exibir o nome da loja na etiqueta

= 2.10.0 =
* Reformulação da forma de selecionar origem da etiquetas com edição dos dados da etiqueta

= 2.9.24 = 
* Ajuste para aceitar endereço com número 0 (zero)
* Ajuste para poder editar informações do pedido (woocommerce)

= 2.9.23 =
* Adiciona o serviço Rodoviário de Buslog 

= 2.9.22 =
* Correções para o plugin se adaptar ao plugin WooCommerce Bundle Products 

= 2.9.21 =
* Remoção da obrigatoriedade de agências para Via Brasil e Jadlog.

= 2.9.20 =
* Correção do erro de produtos sem medidas

= 2.9.19 =
*  Correção do erro fatal da versão 2.9.17

= 2.9.17 =
* Adição de alerta em casos de token inválidos ou expirados

= 2.9.16 =
* Correção para o problema de lentidão ao inserir um produto no carrinho de compras

= 2.9.14 =
* Ajustes validações payload para API do Melhor Envio
* Ajuste no uso de valor segurado nas cotações
* Adição de uso de CNAE para transportadoras privadas
* Validações para CEP

= 2.9.11 =
* Validação de cep de destino para cotação

= 2.9.10 =
* Correção do aviso de erro de session_start()
* Correção do problema ocorrido em algumas lojas ao inserir produtos no carrinho de compras

= 2.9.9 =
* Criando helper para iniciar session
* Correções de warning de PHP
* Correção do problema de não exibr botões de compras da etiqueta na listagem de pedidos
* Correção no processo de finalização de compras de produtos virtuais
* Ajuste para usar valores com centavos nas taxas extras de envios
* Ajuste no aviso que o usuário não selecionou um método do Melhor Envio mesmo selecioando um método do Melhor Envio
* Correção do problema de link de rastreio na aba de pedidos dos clientes
* Ajuste erro 500 na listagem de pedidos
* Removendo listagem duplicada de rastreio