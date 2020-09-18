=== Melhor Envio V2 ===
Version: 2.10.0
Author: Melhor Envio
Author URI: melhorenvio.com.br
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: baseplugin
Tested up to: 5.6
Requires PHP: 5.6

== Description ==
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

### Pré-requisitos
- PHP v.5.6
- Wordpress 4.0+
- WooCommerce 4.0+

### Compatibilidade
Caso seja utilizado algum outro plugin que altere o Woocommerce não é garantida a sua compatibilidade, como plugins que adicionam ao Woocommerce funcionalidades de marketplace.

## Contribuindo com o Projeto
Caso queira contribuir com o projeto, o processo para isto é criar um brach separado, implementar o desejado, e encaminhar um pull request para o develop, com descrição da alteração.
<a href="https://github.com/melhorenvio/wp-melhorenvio-v2" target="_blank">Repositório público do Plugin do Melhor Envio</a>

## Suporte
Para entrar em contato com o suporte desse plugin, enviar e-mail para dev@melhorenvio.com

== Installation ==
A instalação do plugin é simples, basta acessar a aba "Plugins > Instalar novo" no seu painel administrativo do wordpress e buscar pelo plugin "Melhor Envio" na barra de busca.
<img src="https://wordpress-screenshots.s3.us-east-2.amazonaws.com/Instalar-plugins-%E2%80%B9-My-Blog-%E2%80%94-WordPress.png" alt="Buscando o plugin do Melhor Envio" />

Ou se preferir basta fazer o download do plugin na página oficial do plugin no portal do Wordpress e mover o arquivo .Zip para o diretório wp-content/plugins. O próximo passo, é acessar todos os plugins pelo menu Plugins -> Plugins instalados, encontrar o plugin "Melhor Envio" e clicar em "Ativar".

Você também vai precisar do plugin <a href"https://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/" target="_blank">Brazilian Market on WooCommerce</a> para o perfeito funcionamento do plugin do Melhor Envio.

O próximo passo para utilizar o plugin é gerar um token na plataforma da Melhor Envio. Para isso, você precisa acessar o <a target="_blank" href="https://melhorenvio.com.br/painel/gerenciar/tokens">link</a> e clicar em "Novo token", inserir um nome para o token, selecionar as permissões e clicar em "Salvar". Você deve copiar o token gerado, e colar o mesmo no painel do Wordpress, acessando o menu Melhor Envio -> Token.
painel administrativo do wordpress e buscar pelo plugin "Melhor Envio" na barra de busca.
<img src="https://wordpress-screenshots.s3.us-east-2.amazonaws.com/Melhor-Envio.png" alt="Gerando token no Melhor Envio" />
 
Agora que sua conta Melhor Envio está vinculada com nosso plugin, basta selecionar os métodos de envios, acessando o Menu WooCommerce -> Configurações -> Entrega. Agora você precisa escolher as áreas que deseja enviar seus produtos utilizando a Melhor Envio. Por padrão, existe a opção "Em toda parte", que seria a área geral do Brasil. Basta clicar em "Editar" logo abaixo do nome da área e selecionar os métodos de envio e para finalizar clicar em "Salvar".

<img src="https://wordpress-screenshots.s3.us-east-2.amazonaws.com/Configura%C3%A7%C3%B5es-do-WooCommerce-%E2%80%B9-My-Blog-%E2%80%94-WordPress+(2).png" alt="Áreas de entregas" />

Não esqueça de cadastrar de forma correta as medidas do produto na aba de entrega de cada produto cadastrado.
<img src="https://wordpress-screenshots.s3.us-east-2.amazonaws.com/Editar-produto-%E2%80%B9-My-Blog-%E2%80%94-WordPress.png" alt="Configuração de entrega de cada produto" />

Observação: Atenção com as medidas de unidades utilizadas, cuidado se você está utilizando gramas ou quilos, metros ou centímetros, isso vai aplicar no valor da cotação e no bom funcionamento da calculadora de fretes do Melhor Envio.
 
Pronto! o plugin do Melhor Envio está funcionando.

== Changelog ==
- Correção da exibição de frete grátis e taxa fixa.
- Ajuste cálculo de produto com variação na tela do produto.
- Adicionando percentual extra sobre o valor do frete.
- Adicionando função para cancelar etiquetas.
- Opção de ativar/desativar valor segurado
- Ajuste para salvar dados do vendedor nos options do Wordpress.
- Correções de warnings