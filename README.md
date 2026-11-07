# Loja de Plantas (Projeto Final - E-commerce)

Loja virtual completa: o visitante navega pelo catálogo e monta um carrinho sem login, o carrinho é preservado ao entrar na conta, o cliente finaliza o pedido com endereço, confirma um **pagamento simulado** e acompanha o status. Um administrador gerencia produtos, categorias, estoque e pedidos.

## Tecnologias

- **Backend:** Laravel (API REST), padrão MVC + Service Layer, Laravel Sanctum
- **Banco de dados:** MySQL 8.4
- **Frontend:** HTML, CSS e JavaScript puros (sem Blade, sem frameworks), servido pelo Nginx
- **Infra:** Docker Compose, Nginx como proxy reverso
- **Documentação da API:** OpenAPI (`docs/openapi.yaml`) + Swagger UI

## Arquitetura

```
Navegador ──> proxy (Nginx, porta 80)
                 ├── /api, /sanctum, /storage ──> api (Laravel)  ──> db (MySQL)
                 ├── /docs                    ──> swagger (Swagger UI)
                 └── /                        ──> frontend (Nginx, arquivos estáticos)
```

Só o proxy publica porta. Os outros serviços ficam na rede interna do Docker. Como frontend e API respondem pelo **mesmo endereço**, o cookie de sessão funciona sem CORS.

## Como executar

Pré-requisito: Docker e Docker Compose.

```bash
# 1) Variáveis de ambiente (os valores do exemplo já funcionam com o compose.yaml)
cp backend/.env.example backend/.env

# 2) Subir todos os serviços (a primeira vez demora: instala as dependências do PHP)
docker compose up -d --build

# 3) Gerar a chave da aplicação, criar as tabelas e popular com dados de teste
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate:fresh --seed

# 4) Liga a pasta das imagens enviadas ao diretório público
docker compose exec api php artisan storage:link
```

Pronto. Acesse:

| O quê | Endereço |
|---|---|
| Loja | http://localhost |
| Swagger UI (documentação da API) | http://localhost/docs/ |

Se a porta 80 estiver ocupada no seu computador, troque `"80:80"` por `"8080:80"` no serviço `proxy` do `compose.yaml` e use `http://localhost:8080` (ajuste também `APP_URL` e `SANCTUM_STATEFUL_DOMAINS` no `backend/.env`).

Para recomeçar do zero (apaga os dados): `docker compose exec api php artisan migrate:fresh --seed`.

## Usuários de teste (seeders)

Todos com a senha `password`.

| Papel | E-mail |
|---|---|
| Administrador | admin@loja.com |
| Cliente | ana@loja.com |
| Cliente | bruno@loja.com |
| Cliente | carla@loja.com |

Os seeders criam também 5 categorias, 32 produtos (um com estoque zero e um inativo), um carrinho de visitante, um carrinho de usuário (Carla) e pedidos em todos os status (aguardando pagamento, pago, enviado, entregue e cancelado).

## Regras de negócio adotadas

**Autenticação.** Sessão com cookie (Sanctum em modo SPA), com proteção CSRF. Dois papéis: `customer` e `admin`. O papel não pode ser alterado pelo cadastro nem pelo perfil. Cliente em rota administrativa recebe 403. O administrador também pode fazer pedidos; ele acumula as permissões de cliente.

**Dinheiro.** Sempre em **centavos inteiros** (R$ 39,90 = 3990), em todo o banco e na API. O frontend converte para reais só na exibição.

**Carrinho.**
- Visitante: o carrinho é identificado pelo **id da sessão** e guardado no banco.
- Um usuário logado tem no máximo um carrinho. A dupla carrinho + produto é única: adicionar de novo soma a quantidade.
- Quantidade 0 remove o item. A quantidade não ultrapassa o estoque. Produto inativo ou sem estoque não entra no carrinho.
- O carrinho **não congela preços** (mostra sempre o preço atual) e o total é sempre calculado no servidor. Itens que ficaram indisponíveis são sinalizados e não entram no total.

**Fusão de carrinhos no login (e no registro).** O carrinho de visitante é fundido ao da conta:
- produto só no carrinho de visitante: é **copiado** para o da conta;
- produto nos dois: as quantidades são **somadas**, limitadas ao estoque; se passar, ajusta para o máximo e devolve um aviso em `cart_warnings`;
- produto inativo ou sem estoque: **não é copiado**, e também gera aviso;
- ao final, o carrinho de visitante é **apagado** e a sessão antiga deixa de valer (o Laravel gera uma sessão nova no login).

A fusão roda em uma **transação**. O id da sessão antiga é guardado *antes* da autenticação, porque o login troca o id.

**Checkout e pedido.** Exige login e um endereço do próprio usuário. Tudo ocorre em **uma transação**: confere o estoque (com `lockForUpdate`), cria o pedido como `pending`, grava nome e preço **congelados** em cada item, copia o endereço para o pedido, baixa o estoque e esvazia o carrinho. Se qualquer item falhar, nada é gravado (422 informando o produto).

**Frete.** Fixo de R$ 15,00, **grátis** quando o subtotal é de R$ 200,00 ou mais. A regra existe em um lugar só (`OrderService::shippingFor`).

**Status do pedido.** `pending` → `paid` → `shipped` → `delivered`, e `cancelled` somente a partir de `pending`. Transição inválida retorna **409**. Cancelar devolve o estoque.

**Pagamento simulado.** O botão "Confirmar pagamento" marca o pedido como pago e registra data e hora. Nenhum dado de cartão é solicitado, trafegado ou armazenado. Só o **dono** do pedido paga (outro usuário recebe 403); pagar um pedido que não está aguardando pagamento retorna 409. Ver e cancelar: dono ou admin.

**Produtos e categorias.** Produto usa **exclusão lógica** (soft delete) e o campo `active`, então pedidos antigos continuam intactos. Categoria com produtos vinculados **não pode ser excluída** (409).

**Erros.** Respostas em JSON com códigos coerentes: 401, 403, 404, 409, 419 (CSRF), 422 (validação, mensagens em português) e 429 (excesso de tentativas de login).

## Estrutura do repositório

```
.
├── compose.yaml          # todos os serviços: db, api, frontend, swagger, proxy
├── backend/              # API Laravel (Dockerfile.dev e Dockerfile de produção)
│   ├── app/Http/Controllers/Api   # recebem a requisição e devolvem a resposta
│   ├── app/Http/Requests          # validação
│   ├── app/Http/Resources         # formato das respostas JSON
│   ├── app/Services               # regras de negócio (carrinho, fusão, pedido, estoque)
│   └── app/Models, database/      # entidades, migrations e seeders
├── frontend/             # HTML, CSS e JS + Dockerfile (a URL da API vem da variável API_URL)
├── proxy/                # Nginx que reúne frontend, API e documentação
└── docs/openapi.yaml     # documentação da API (Swagger UI)
```

## Variáveis de ambiente importantes

| Variável | Onde | Para quê |
|---|---|---|
| `DB_*` | `backend/.env` | conexão com o banco |
| `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` | `backend/.env` | endereço da loja (necessário para a sessão por cookie) |
| `SESSION_LIFETIME` | `backend/.env` | duração da sessão (o carrinho de visitante sobrevive a fechar o navegador) |
| `API_URL` | serviço `frontend` no `compose.yaml` | endereço da API usado pelo navegador (padrão `/api`) |

O arquivo `.env` real não é versionado. O `backend/.env.example` não contém segredos reais (a senha do banco é só de desenvolvimento).

## Observações

- Dockerfile de produção do backend: `backend/Dockerfile` (Apache + PHP). Em produção, rode `php artisan migrate --force` no primeiro deploy e monte um volume em `storage/app/public` para as imagens dos produtos.
- O Docker Compose deste repositório é de **desenvolvimento**: o código é espelhado nos containers e a API usa o servidor de desenvolvimento do Laravel.
