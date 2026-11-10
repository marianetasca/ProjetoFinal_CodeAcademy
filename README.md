# Loja de Plantas (Projeto Final - E-commerce)

Loja virtual completa: o visitante navega pelo catálogo e monta um carrinho sem login, o carrinho é preservado ao entrar na conta, o cliente finaliza o pedido com endereço, confirma um **pagamento simulado** e acompanha o status. Um administrador gerencia produtos, categorias, estoque e pedidos.

## Sumário

1. [Tecnologias](#tecnologias)
2. [Arquitetura](#arquitetura)
3. [Pré-requisitos](#pré-requisitos)
4. [Passo a passo para rodar](#passo-a-passo-para-rodar)
5. [Como conferir que funcionou](#como-conferir-que-funcionou)
6. [Usuários de teste](#usuários-de-teste-seeders)
7. [Roteiro de teste (5 minutos)](#roteiro-de-teste-5-minutos)
8. [Comandos úteis](#comandos-úteis)
9. [Problemas comuns](#problemas-comuns)
10. [Regras de negócio adotadas](#regras-de-negócio-adotadas)
11. [Estrutura do repositório](#estrutura-do-repositório)
12. [Variáveis de ambiente](#variáveis-de-ambiente)

## Tecnologias

- **Backend:** Laravel (API REST), padrão MVC + Service Layer, Laravel Sanctum (sessão com cookie)
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

São 5 serviços no `compose.yaml`: `db`, `api`, `frontend`, `swagger` e `proxy`. Só o proxy publica porta; os outros ficam na rede interna do Docker. Como frontend e API respondem pelo **mesmo endereço**, o cookie de sessão funciona sem CORS.

## Pré-requisitos

- **Git**
- **Docker** e **Docker Compose** (versão 2: o comando é `docker compose`, com espaço)
- Porta **80** livre no seu computador (veja "Problemas comuns" se estiver ocupada)
- Conexão com a internet na primeira execução (baixa imagens e dependências)

Confira se está tudo instalado:

```bash
git --version
docker --version
docker compose version
```

No Windows, use o Docker Desktop com WSL 2 e rode o projeto **dentro do sistema de arquivos do Linux** (por exemplo `~/loja-virtual`), não em `/mnt/c/...`, que deixa o Docker muito lento.

## Passo a passo para rodar

### 1. Baixe o projeto

```bash
git clone https://github.com/marianetasca/ProjetoFinal_CodeAcademy.git
cd ProjetoFinal_CodeAcademy
```

### 2. Crie o arquivo de variáveis de ambiente

```bash
cp backend/.env.example backend/.env
```

Os valores do exemplo já combinam com o `compose.yaml` (banco `loja`, usuário `loja`, senha de desenvolvimento). Não precisa editar nada.

### 3. Suba os serviços

```bash
docker compose up -d --build
```

**A primeira vez demora** (de 3 a 10 minutos, dependendo da internet): o Docker baixa as imagens e a API instala as dependências do PHP. O comando só termina quando a API fica saudável. Para acompanhar o que está acontecendo, abra **outro terminal** e rode:

```bash
docker compose logs -f api
```

Se o comando terminar com um erro dizendo que a `api` está `unhealthy`, normalmente ela só precisava de mais tempo. Espere o log mostrar `Server running on [http://0.0.0.0:8000]` e rode de novo:

```bash
docker compose up -d
```

Confira o estado dos serviços:

```bash
docker compose ps
```

Os 5 devem estar `Up`, com `db` e `api` marcados como `healthy`.

### 4. Gere a chave da aplicação

```bash
docker compose exec api php artisan key:generate
```

### 5. Crie as tabelas e popule com dados de teste

```bash
docker compose exec api php artisan migrate:fresh --seed
```

### 6. Ligue a pasta das imagens ao diretório público

```bash
docker compose exec api php artisan storage:link
```

Pronto.

## Como conferir que funcionou

| O quê | Endereço | Esperado |
|---|---|---|
| Loja | http://localhost | Catálogo com os produtos e fotos |
| Documentação da API (Swagger UI) | http://localhost/docs/ | Página com as rotas, agrupadas por assunto |
| Página 404 | http://localhost/qualquer-coisa | Página "Página não encontrada" com o menu da loja |
| Endereço da API usado pelo site | http://localhost/config.js | `export const API_URL = '/api';` |

## Usuários de teste (seeders)

Todos com a senha `password`.

| Papel | E-mail |
|---|---|
| Administrador | admin@loja.com |
| Cliente | ana@loja.com |
| Cliente | bruno@loja.com |
| Cliente | carla@loja.com |

Os seeders criam também 5 categorias, 32 produtos (um com estoque zero e um inativo), um carrinho de visitante, um carrinho de usuário (Carla) e pedidos em todos os status (aguardando pagamento, pago, enviado, entregue e cancelado).

## Roteiro de teste (5 minutos)

Percorre o fluxo completo da loja:

1. **Visitante:** abra http://localhost, busque "jiboia", filtre por categoria, ordene por preço e abra um produto.
2. **Carrinho sem login:** adicione 2 unidades de um produto. O número no menu sobe. Feche e abra o navegador: o carrinho continua.
3. **Produto indisponível:** abra a "Orquídea Phalaenopsis" (estoque zero): não dá para adicionar.
4. **Fusão de carrinho:** com itens no carrinho, clique em **Finalizar compra**, entre como `ana@loja.com`. O carrinho foi preservado.
5. **Checkout:** cadastre um endereço, confira o frete e confirme o pedido. O estoque do produto diminui.
6. **Pagamento simulado:** na página do pedido, clique em **Confirmar pagamento**. O status vira "Pago".
7. **Cancelamento:** faça outro pedido e cancele: o estoque volta.
8. **Painel admin:** entre como `admin@loja.com`, abra **Painel**, filtre os pedidos por "Pago" e marque como **enviado** e depois **entregue**. Crie um produto com foto e uma categoria.
9. **Permissões:** como `ana@loja.com`, abra `/admin/index.html`: aparece "Acesso restrito a administradores".

### Testando pela API (Swagger ou Postman)

A API usa sessão com cookie e proteção CSRF. Para rotas que alteram dados (POST, PUT, PATCH, DELETE):

1. Faça `GET /sanctum/csrf-cookie`.
2. Envie o header `X-XSRF-TOKEN` com o valor (decodificado) do cookie `XSRF-TOKEN`.
3. Envie o header `Referer: http://localhost` (o navegador já faz isso sozinho).

O Swagger UI explica esse passo a passo na própria descrição.

## Comandos úteis

| O que fazer | Comando |
|---|---|
| Ver o estado dos serviços | `docker compose ps` |
| Ver os logs de um serviço | `docker compose logs -f api` (troque `api` por `proxy`, `frontend`, `db`) |
| Parar tudo (mantém os dados) | `docker compose down` |
| Subir de novo, sem reconstruir | `docker compose up -d` |
| Parar e **apagar os dados do banco** | `docker compose down -v` |
| Zerar e repopular o banco | `docker compose exec api php artisan migrate:fresh --seed` |
| Abrir um terminal do Laravel | `docker compose exec api php artisan tinker` |
| Reiniciar só o proxy | `docker compose restart proxy` |

Para começar **do zero absoluto** (como num computador novo):

```bash
docker compose down -v
docker compose up -d --build
docker compose exec api php artisan key:generate
docker compose exec api php artisan migrate:fresh --seed
docker compose exec api php artisan storage:link
```

## Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| `port is already allocated` / a loja não abre | A porta 80 está ocupada (por exemplo, um Apache instalado no computador) | Troque `"80:80"` por `"8080:80"` no serviço `proxy` do `compose.yaml`, use `http://localhost:8080` e ajuste `APP_URL` e `SANCTUM_STATEFUL_DOMAINS` no `backend/.env` |
| `502 Bad Gateway` depois de reconstruir um serviço | O proxy guardou o endereço antigo do container | `docker compose restart proxy` |
| `docker compose up` termina dizendo que a API está `unhealthy` | A primeira instalação das dependências é lenta | Espere o log da API (`docker compose logs -f api`) e rode `docker compose up -d` de novo |
| Erro `No application encryption key has been specified` | Faltou o passo 4 | `docker compose exec api php artisan key:generate` |
| Erro `Access denied for user 'loja'` | O banco foi criado antes com outra senha | `docker compose down -v` e suba de novo (apaga o banco) |
| Catálogo vazio | Faltou popular o banco | `docker compose exec api php artisan migrate:fresh --seed` |
| Produtos sem foto | Faltou o passo 6 | `docker compose exec api php artisan storage:link` e recarregue com `Ctrl+F5` |
| Erro `419 CSRF token mismatch` no Postman | Falta o cookie ou o header `X-XSRF-TOKEN` | Veja "Testando pela API". No navegador isso não acontece |
| Mudanças no JavaScript não aparecem | Cache do navegador | `Ctrl+F5` |

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

**Produtos e categorias.** Produto usa **exclusão lógica** (soft delete) e o campo `active`, então pedidos antigos continuam intactos. Categoria com produtos vinculados **não pode ser excluída** (409). Imagens: jpg, jpeg, png ou webp, até 2 MB.

**Erros.** Respostas em JSON com códigos coerentes: 401, 403, 404, 409, 419 (CSRF), 422 (validação, mensagens em português) e 429 (excesso de tentativas de login).

## Estrutura do repositório

```
.
├── compose.yaml          # todos os serviços: db, api, frontend, swagger, proxy
├── README.md
├── backend/              # API Laravel (Dockerfile.dev e Dockerfile de produção)
│   ├── app/Http/Controllers/Api   # recebem a requisição e devolvem a resposta
│   ├── app/Http/Requests          # validação
│   ├── app/Http/Resources         # formato das respostas JSON
│   ├── app/Services               # regras de negócio (carrinho, fusão, pedido, estoque)
│   ├── app/Models, app/Enums      # entidades e status do pedido
│   └── database/                  # migrations e seeders (com as imagens dos produtos)
├── frontend/             # HTML, CSS e JS + Dockerfile (a URL da API vem da variável API_URL)
├── proxy/                # Nginx que reúne frontend, API e documentação
└── docs/openapi.yaml     # documentação da API (Swagger UI)
```

## Variáveis de ambiente

| Variável | Onde | Para quê |
|---|---|---|
| `DB_*` | `backend/.env` | conexão com o banco (precisa combinar com o serviço `db` do `compose.yaml`) |
| `APP_KEY` | `backend/.env` | chave da aplicação, gerada no passo 4 |
| `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` | `backend/.env` | endereço da loja (necessário para a sessão por cookie) |
| `APP_LOCALE` | `backend/.env` | idioma das mensagens de validação (`pt_BR`) |
| `SESSION_LIFETIME` | `backend/.env` | duração da sessão (o carrinho de visitante sobrevive a fechar o navegador) |
| `API_URL` | serviço `frontend` no `compose.yaml` | endereço da API usado pelo navegador (padrão `/api`) |

O arquivo `.env` real não é versionado. O `backend/.env.example` não contém segredos reais (a senha do banco é só de desenvolvimento).

## Observações

- O Docker Compose deste repositório é de **desenvolvimento**: o código é espelhado nos containers e a API usa o servidor de desenvolvimento do Laravel.
- Dockerfile de produção do backend: `backend/Dockerfile` (Apache + PHP). Em produção, rode `php artisan migrate --force` no primeiro deploy e monte um volume em `storage/app/public` para as imagens dos produtos.
- O pagamento é **simulado**, para fins acadêmicos.