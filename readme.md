=== Melhor Envio ===
Version: 3.0.3
Tags: frete, cotação, logística, envio, melhor envio
Requires at least: 4.7
Tested up to: 7.0
Stable tag: 3.0.3
Requires PHP: 7.4
Requires Wordpress 4.7+
Requires WooCommerce 4.0+
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Cotação, compra de etiquetas e gestão de fretes direto no seu WooCommerce, com toda a gestão centralizada num painel integrado.

== Description ==
O Melhor Envio conecta sua loja WooCommerce à maior plataforma de fretes do Brasil, com cotações simultâneas dos Correios e de diversas transportadoras privadas de forma ágil e gratuita. A plataforma possui contratos com várias empresas de logística para oferecer condições mais competitivas aos vendedores online, sem mensalidades ou contratos individuais.

A partir da versão 3.0, o plugin passa a funcionar através de um painel centralizado, embutido direto no admin do WordPress, que substitui a configuração manual por um vínculo automático entre sua loja e sua conta Melhor Envio. Não é mais necessário gerar e colar token manualmente — basta acessar o menu Melhor Envio dentro do WooCommerce e concluir a conexão em poucos cliques.

A tecnologia já ajudou mais de 50 mil lojistas a otimizar a gestão de fretes, acessando uma série de vantagens exclusivas sem precisar negociar individualmente com as transportadoras.

### Funcionalidades da Melhor Envio
- Conexão automática entre a loja WooCommerce e a conta Melhor Envio, sem necessidade de gerar e colar token manualmente.
- Cotação de fretes em tempo real no carrinho e na tela do produto, com todas as transportadoras disponíveis na sua conta Melhor Envio.
- Gestão centralizada de pedidos, etiquetas e rastreios num único painel integrado ao WordPress.
- Compra e emissão de etiquetas de postagem utilizando o saldo da conta Melhor Envio.
- Regras de frete inteligentes e configurações de cotação aplicadas automaticamente, sem necessidade de ajustar cada transportadora individualmente.

Lojas que ainda utilizam a versão anterior (legado) continuam funcionando normalmente e podem migrar para a nova versão a qualquer momento pelo próprio painel administrativo, sem perda de configurações.

### Compatibilidade
Caso seja utilizado algum outro plugin que altere o Woocommerce não é garantida a sua compatibilidade, como plugins que adicionam ao Woocommerce funcionalidades de marketplace.

Para utilizar grupos de produtos ou kits, recomendamos a utilização dos plugins 
<a href="https://br.wordpress.org/plugins/woo-product-bundle/" target="_blank">WPC Product Bundles</a> ou
<a href="https://br.wordpress.org/plugins/wpc-composite-products/" target="_blank">WPC Composite Products</a> pois esses são plugins compatíveis com o plugin da Melhor Envio.

## Contribuindo com o Projeto
Caso queira contribuir com o projeto, o processo para isto é criar um brach separado, implementar o desejado, e encaminhar um pull request para o develop, com descrição da alteração.
<a href="https://github.com/melhorenvio/wp-melhorenvio-v2" target="_blank">Repositório público do Plugin do Melhor Envio</a>

## Suporte
Para entrar em contato com o suporte desse plugin, enviar e-mail para contato@melhorenvio.com

== Installation ==
A instalação do plugin é simples, basta acessar a aba "Plugins > Instalar novo" no seu painel administrativo do WordPress e buscar pelo plugin "Melhor Envio" na barra de busca.

Ou se preferir, basta fazer o download do plugin na página oficial do plugin no portal do WordPress e mover o arquivo .zip para o diretório wp-content/plugins. O próximo passo é acessar todos os plugins pelo menu Plugins -> Plugins instalados, encontrar o plugin "Melhor Envio" e clicar em "Ativar".

A partir da versão 3.0, não é mais necessário utilizar um plugin de campos de checkout adicional — os campos necessários para o funcionamento do frete já são adicionados automaticamente pelo próprio Melhor Envio. Caso já utilize o plugin <a href="https://wordpress.org/plugins/woo-better-shipping-calculator-for-brazil/" target="_blank">Calculadora de Frete e Campos Checkout para o Brasil</a> e prefira mantê-lo, a compatibilidade continua garantida.

Após ativar o plugin, acesse o menu "Melhor Envio" dentro do WooCommerce. Você será direcionado ao painel da Melhor Envio, onde basta concluir a conexão da sua conta Melhor Envio com a loja — não é necessário gerar ou colar nenhum token manualmente. Caso ainda não tenha uma conta, é possível criá-la diretamente pelo próprio painel.

Com a conta conectada, as cotações de frete já ficam disponíveis automaticamente no carrinho e na tela do produto, considerando todas as transportadoras habilitadas na sua conta Melhor Envio.

Não esqueça de cadastrar corretamente as medidas do produto na aba de entrega de cada produto cadastrado.


Pronto! O plugin do Melhor Envio está funcionando.

== Changelog ==

= 3.0.3 =
* Adiciona filtro de webhook para o Melhor Integrador, evitando reenvios desnecessários quando nenhum dado relevante do pedido foi alterado

= 3.0.2 =
* Adiciona suporte a frete expresso, econômico e frete personalizado na cotação

= 3.0.1 =
* Adiciona upload de XML NF-e no admin do pedido, com extração automática da chave de acesso

= 2.16.5 =
* Correção na cotação de pedidos

= 2.16.4 =
* Correção de vulnerabilidades

= 2.16.3 =
* Adiciona notificação de pesquisa com usuários

= 2.16.2 =
* Altera plugin de campos de checkout para recomendado ao invés de obrigatório

= 2.16.1 =
* Adiciona envio de NF para JeT e Loggi

= 2.16.0 =
* Altera plugin obrigatório para campos de checkout
* Adiciona nova transportadora (Total Express)
* Adiciona mascára para o campo de CNPJ
* Melhoria nas telas de admin

= 2.15.18 =
* Corrige os dados do produto passados para o WooCommerce

= 2.15.17 =
* Valida se o ID do produto existe

= 2.15.16 =
* Corrige cotação na página do produto

= 2.15.15 =
* Corrige formatação de valor do produto para duas casas decimais

= 2.15.14 =
* Busca agencias usando latitude e longitude

= 2.15.13 =
* Altera tags de busca

= 2.15.12 =
* Adiciona nova capacidade

= 2.15.11 =
* Path com correções importantes

= 2.15.10 =
* Path com correções importantes

= 2.15.9 =
* Adiciona validacao quando o cep é somente zeros.
* Atualiza versão compativel do wordpress.

= 2.15.8 =
* Adiciona log no caso de falha em uma requisição.
* Corrige cotacao em compras com produtos virtuais.

= 2.15.7 =
* Corrige problema na busca do CEP.

= 2.15.6 =
* Remove serviços centralizados dos Correios.

= 2.15.5 =
* Corrige reatividade na adição dos dados de NF.

= 2.15.4 =
* Adiciona plugins requiridos pelo ME.
* Adiciona tratativas para produtos excluídos.

= 2.15.3 =
* Corrige lentidão no checkout.

= 2.15.2 =
* Atualiza a versão testada do Wordpress e WooCommerce.

= 2.15.1 =
* Adiciona compatibilidade com os plugins "WPC Composite Products e WPC Product Bundles".
* Ajuste na tabela de pedidos.

= 2.15.0 =
* Adiciona a transportadora JeT.
* Adiciona o serviço JeT Standard.
* Adiciona compatibilidade com WC HPOS.
* Ajuste na exibição de rastreio para os clientes.
* Ajuste na listagem de agências de coletas.

= 2.14.0 =
* Adiciona serviço de Loggi coleta.

= 2.13.1 =
* Remove validação para produtos com valor zero.

= 2.13.0 =
* Adição de validação de chave de acesso da nota fiscal para envios comerciais de Correios.

= 2.12.0 =
* Adição de serviços de Correios centralizados, Jadlog centralizado e loggi Express.

= 2.11.35 =
* Correção para aplicar o valor do produto para produtos combos.

= 2.11.34 =
* Correção para utilizar o valor do produto com cupom de desconto aplicado no campo de valor segurado.

= 2.11.33 =
* Correção do bug para buscar o CNPJ do comprador durante a geração de pedido.

= 2.11.32 =
* Correção do bug na interferência do complemento de endereço de entrega e cobrança do cliente final.

= 2.11.31 =
* Correção do bug de listagem de pedidos em algumas lojas.
* Correção no load de Namespace do Plugin
* Ajuste no problema de quantidade de produtos na tela do produto em alguns temas.

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
