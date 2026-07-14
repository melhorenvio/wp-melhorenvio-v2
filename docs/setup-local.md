# Setup de Ambiente Local — Plugin Melhor Envio

Guia para subir o plugin WordPress integrado ao backend `tray-native` e ao frontend `melhor-integrador-app`, com proxy HTTPS via Caddy.

---

## Arquitetura

```
Browser / Caddy (proxy HTTPS local)
├── app.localhost       → tray-native (Laravel/Octane :80)
├── cotacao.localhost   → wp-melhorenvio-v2 (WordPress :80)
└── front.localhost     → melhor-integrador-app/packages/wordpress (:3030)

Rede compartilhada: tray-native_sail (Docker)
```

> O `make mi-up`, rodado dentro de `wp-melhorenvio-v2`, orquestra os três repositórios. Configure cada um antes de executá-lo.

---

## Pré-requisitos

- **Docker e Docker Compose** instalados e em execução
- **Node.js 24** — use `nvm` com o `.nvmrc` de cada projeto
- **make** — disponível em Unix/WSL2
- **GitHub PAT** com permissão `read:packages` — para pacotes npm privados `@melhorenvio`
- Acesso ao **KEEPER** — cofre de credenciais da equipe

> **WSL2:** mantenha os três repositórios dentro do filesystem WSL (`/home/...`), não em `/mnt/c/...`. Performance e resolução de rede podem falhar do lado Windows.

---

## Passo 1 — tray-native (Backend Laravel)

Backend Laravel com Octane + RoadRunner. Exposto em `app.localhost` via Caddy.

### Variáveis de Ambiente

#### Variáveis específicas para o fluxo WordPress

Configure manualmente no `.env`:

> **Conflito de porta com o Caddy:** o Caddy ocupa a porta 80 do host. Mude `APP_PORT` para evitar conflito — o Caddy roteia para `app:80` internamente via rede Docker, então a porta exposta no host não precisa ser 80.

```dotenv
APP_PORT=3600
```

```dotenv
# CORS — permite que o iframe em front.localhost chame a API
CORS_ALLOWED_ORIGINS=https://front.localhost
CORS_SUPPORTS_CREDENTIALS=true

# Links do dashboard — apontam para o frontend WordPress via Caddy
LINKS_DASHBOARD_HOME=https://front.localhost/home
LINKS_DASHBOARD_PRODUCT=https://front.localhost/product
LINKS_DASHBOARD_LOADING=https://front.localhost/loading
LINKS_DASHBOARD_LOGIN=https://front.localhost/login
LINKS_DASHBOARD_500=https://front.localhost/500

WORDPRESS_APP_NAME="WP Native"
WORDPRESS_SCOPE="read_write"
# Callback do WordPress usa .me.test porque containers com glibc resolvem
# *.localhost para 127.0.0.1 (loopback interno), ignorando o Caddy
WORDPRESS_CALLBACK_BASE=http://app.me.test
WORDPRESS_VERIFY_SSL=false
```

---

## Passo 2 — melhor-integrador-app (Frontend Nuxt)

Monorepo Nuxt 3. O pacote relevante é `packages/wordpress`, exposto em `front.localhost` via Caddy na porta 3030.

### GitHub Token para pacotes privados

Configure `~/.npmrc` para acessar pacotes `@melhorenvio`:

```
//npm.pkg.github.com/:_authToken=SEU_GITHUB_PAT_AQUI
@melhorenvio:registry=https://npm.pkg.github.com
```

> O PAT precisa da permissão `read:packages`. No GitHub, vá em **Settings → Personal access tokens → Configure SSO** e autorize a organização Melhor Envio.

### Variáveis de Ambiente

Crie o arquivo `packages/wordpress/.env`:

```dotenv
# Browser (iframe no WP) chama a API via Caddy HTTPS cross-site
NUXT_PUBLIC_API_BASE=https://app.localhost

# Vazio = frontend usa ${origin}/api (same-origin via Caddy -> int-1-proxy)
ME_API_URL=

# Endpoint OAuth no backend
NUXT_BACKEND_APP=https://app.localhost/third-party/wordpress/authorize

# Flipt Feature Flags
FLIPT_NAMESPACE=tray-native
FLIPT_HOST=
```

Verifique o `.env` na raiz do projeto (usado pelo Docker para build das imagens):

```dotenv
NPM_TOKEN=ghp_seu_token_aqui
```

---

## Passo 3 — wp-melhorenvio-v2 (Plugin WordPress)

Plugin WordPress + WooCommerce para cotação e compra de frete. Roda em `cotacao.localhost` via Caddy.

### Variáveis de Ambiente

```bash
cp .env.example .env
```

Os valores padrão funcionam para desenvolvimento local:

| Variável | Valor local | Status |
|---|---|---|
| `WORDPRESS_URL` | `https://cotacao.localhost` | Padrão OK |
| `WORDPRESS_SITEURL` | `https://cotacao.localhost` | Padrão OK |
| `WORDPRESS_HOME` | `https://cotacao.localhost` | Padrão OK |
| `MELHOR_INTEGRADOR_BASE_URL` | `https://app.localhost/wp` | Padrão OK |
| `WORDPRESS_ADMIN_USER` | `melhorenvio` | Padrão OK |
| `WORDPRESS_ADMIN_PASSWORD` | `melhorenvio` | Padrão OK |
| `WWWUSER` / `WWWGROUP` | Herdado do shell (Passo 1) | **Obrigatório** |

---

## Passo 4 — Subir o Ambiente Completo

Com os três repositórios configurados, rode a partir de `wp-melhorenvio-v2`:

```bash
make mi-up
```

O que esse comando faz:

1. Sobe os containers do **tray-native** (`docker compose up -d`)
2. Sobe o container **wordpress** do melhor-integrador-app na porta 3030
3. Sobe **WordPress + MySQL + Caddy** neste projeto
4. Conecta todos os containers à rede `tray-native_sail` com aliases corretos (`wordpress`, `int-1-proxy`)
5. Configura o WordPress: instala o core, ativa WooCommerce, ativa o plugin `melhor-envio-cotacao`

> **Atenção:** o tray-native precisa ter sido inicializado antes para que a rede `tray-native_sail` exista. Caso contrário, `make mi-up` falha ao conectar containers à rede.

O comando aguarda o WordPress inicializar automaticamente (até 90 segundos) antes de prosseguir.

> **Rebuild do container frontend:** sempre que o container `melhor-integrador-app-wordpress-1` for recriado (ex: `docker compose up --build`), ele perde a conexão com a rede do Caddy. Re-execute `make mi-network` para reconectá-lo.

---

## Passo 5 — Certificado HTTPS Local

O Caddy gera uma CA interna automaticamente. Para que o navegador confie nos domínios `*.localhost` e `*.me.test`, exporte e instale a CA:

```bash
make mi-trust-ca
# Exporta: ./caddy/caddy-root-ca.crt
```

### Windows (WSL2)

```bash
# No WSL — copie para o Windows:
cp ./caddy/caddy-root-ca.crt /mnt/c/Users/<seu-usuario>/
```

```powershell
# No PowerShell como Administrador:
certutil -addstore -f "ROOT" C:\Users\<seu-usuario>\caddy-root-ca.crt
```

### Linux

```bash
sudo cp ./caddy/caddy-root-ca.crt /usr/local/share/ca-certificates/
sudo update-ca-certificates
```

> Reinicie o navegador completamente após instalar. No Chrome/Edge no Windows, encerre o processo pelo gerenciador de tarefas se necessário.

---

## URLs e Credenciais

| URL | Serviço | Credenciais |
|---|---|---|
| https://cotacao.localhost/wp-admin | WordPress Admin | `melhorenvio` / `melhorenvio` |
| https://cotacao.localhost | Plugin (WooCommerce frontend) | — |
| https://app.localhost | Backend tray-native (API) | — |
| https://front.localhost | Frontend Melhor Integrador (SPA) | — |

O menu **Melhor Integrador** aparece no admin do WooCommerce após a ativação do plugin. Ele abre o iframe da SPA (`front.localhost`) para o fluxo de integração.

---

## Comandos Úteis

Todos rodados a partir de `wp-melhorenvio-v2/`:

| Comando | Descrição |
|---|---|
| `make mi-up` | Sobe o ambiente completo (backend + frontend + WP + Caddy + redes + config) |
| `make mi-down` | Para Caddy + WordPress + frontend (tray-native permanece ativo) |
| `make mi-info` | Exibe as URLs de acesso do ambiente |
| `make mi-trust-ca` | Exporta o certificado CA do Caddy para instalação no SO |
| `make mi-clean` | Reseta dados do onboard para testar o fluxo inicial novamente |
| `make mi-network` | Reconecta containers à rede compartilhada (idempotente) |
| `make mi-wp-config` | Reconfigura WordPress: core, WooCommerce, plugin |
| `make init` | Reset completo do WP: derruba volumes + rebuild + setup |
| `make wc-setup` | Cria zonas de frete e métodos de pagamento no WooCommerce |
| `make wc-create-products` | Cria produtos de exemplo no WooCommerce |

---

## Troubleshooting

### Rede `tray-native_sail` não encontrada

O tray-native precisa ter sido iniciado pelo menos uma vez para criar a rede Docker compartilhada:

```bash
cd ../tray-native && sail up -d app
```

### WordPress não instala / "ERRO: WordPress nao iniciou em 90s"

O container pode estar lento na primeira vez. Re-execute apenas a etapa de configuração:

```bash
make mi-wp-config
```

### Erros de certificado HTTPS no navegador

Execute `make mi-trust-ca`, siga as instruções para seu SO e reinicie o navegador completamente.

### Frontend não carrega em `front.localhost`

Verifique se `packages/wordpress/.env` foi criado. Confirme que o container está rodando:

```bash
docker ps --filter "name=melhor-integrador"
```

### Pacotes npm privados não instalam (`@melhorenvio/...`)

Verifique o `~/.npmrc`: token correto e SSO habilitado para a organização Melhor Envio no GitHub.

### Plugin não aparece ou não ativa no WP admin

Ative manualmente via WP-CLI:

```bash
docker exec --user www-data wp-melhorenvio-cotacao \
  wp plugin activate melhor-envio-cotacao
```

### Frontend sumiu do Caddy após rebuild do container

Sempre que o container `melhor-integrador-app-wordpress-1` for recriado, ele perde a conexão com a rede `tray-native_sail`. O Caddy para de rotear `front.localhost` para ele. Reconecte:

```bash
make mi-network
```

### Containers não se comunicam entre si

Re-execute a conexão de rede (idempotente):

```bash
make mi-network
```
