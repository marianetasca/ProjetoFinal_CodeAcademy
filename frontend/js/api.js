// Único lugar do frontend que conversa com a API.
// Todas as páginas usam a função api(), e ela cuida de cookies e CSRF.

import { API_URL } from '../config.js'; // endereço da API (configurável por variável de ambiente)

const BASE = API_URL;

// Raiz do servidor da API, sem o "/api" no final. É onde fica o /sanctum/csrf-cookie.
// Com API_URL = "/api" a raiz é vazia (mesmo endereço do site).
const RAIZ = BASE.replace(/\/api\/?$/, '');

// Erro com o status HTTP (401, 404, 422...) e a mensagem já pronta para mostrar.
export class ApiError extends Error {
  constructor(status, dados) {
    super(montarMensagem(status, dados));
    this.status = status;
    this.dados = dados;
  }
}

function montarMensagem(status, dados) {
  // 429 = muitas tentativas seguidas (o login permite 6 por minuto)
  if (status === 429) return 'Muitas tentativas. Aguarde um minuto e tente de novo.';
  // 422: junta todas as mensagens de validação numa frase só
  if (dados && dados.errors) {
    return Object.values(dados.errors).flat().join(' ');
  }
  return (dados && dados.message) || `Erro inesperado (${status}).`;
}

// Lê um cookie do navegador pelo nome.
function lerCookie(nome) {
  const par = document.cookie.split('; ').find((c) => c.startsWith(nome + '='));
  return par ? decodeURIComponent(par.split('=').slice(1).join('=')) : null;
}

// Pede ao Laravel o cookie de proteção CSRF (XSRF-TOKEN).
async function pedirCookieCsrf() {
  await fetch(RAIZ + '/sanctum/csrf-cookie', {
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
}

// Faz uma chamada à API.
//   api('/products?page=2')
//   api('/cart/items', { method: 'POST', body: { product_id: 1, quantity: 2 } })
// Devolve o JSON da resposta (ou null quando vem 204). Lança ApiError se der erro.
export async function api(caminho, { method = 'GET', body = null } = {}, jaTentouDeNovo = false) {
  const opcoes = {
    method,
    credentials: 'include', // envia o cookie de sessão junto
    headers: { Accept: 'application/json' },
  };

  // Todo método que altera dados precisa do token CSRF no header.
  if (method !== 'GET') {
    if (!lerCookie('XSRF-TOKEN')) await pedirCookieCsrf();
    opcoes.headers['X-XSRF-TOKEN'] = lerCookie('XSRF-TOKEN');
  }

  if (body instanceof FormData) {
    opcoes.body = body; // upload de imagem: o navegador define o Content-Type sozinho
  } else if (body) {
    opcoes.headers['Content-Type'] = 'application/json';
    opcoes.body = JSON.stringify(body);
  }

  const resposta = await fetch(BASE + caminho, opcoes);

  // 419 = token CSRF velho (acontece depois de logout, por exemplo).
  // Pega um novo e tenta UMA vez de novo.
  if (resposta.status === 419 && !jaTentouDeNovo) {
    await pedirCookieCsrf();
    return api(caminho, { method, body }, true);
  }

  if (resposta.status === 204) return null; // deu certo, sem conteúdo

  const dados = await resposta.json().catch(() => null);
  if (!resposta.ok) throw new ApiError(resposta.status, dados);

  return dados;
}