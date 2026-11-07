// Página do catálogo: busca, filtro por categoria, ordenação e paginação.
// Os filtros ficam na URL (?search=jiboia&sort=price_asc&page=2), então o botão
// "voltar" do navegador e o compartilhamento de links funcionam sozinhos.

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { formatPrice, escapeHtml } from './util.js';

const params = new URLSearchParams(location.search);
const lista = document.getElementById('lista');
const mensagem = document.getElementById('mensagem');
const paginacao = document.getElementById('paginacao');

async function iniciar() {
  await loadLayout();
  await carregarCategorias();

  // Preenche os campos do formulário com o que está na URL
  document.getElementById('busca').value = params.get('search') || '';
  document.getElementById('categoria').value = params.get('category_id') || '';
  document.getElementById('ordem').value = params.get('sort') || 'latest';

  await carregarProdutos();
}

async function carregarCategorias() {
  try {
    const resposta = await api('/categories');
    const select = document.getElementById('categoria');
    resposta.data.forEach((c) => {
      select.insertAdjacentHTML('beforeend', `<option value="${c.id}">${escapeHtml(c.name)}</option>`);
    });
  } catch (erro) {
    // Sem categorias o filtro fica só com "Todas", e o catálogo continua funcionando
  }
}

async function carregarProdutos() {
  // Monta a query só com os filtros que existem
  const query = new URLSearchParams();
  ['search', 'category_id', 'sort', 'page'].forEach((campo) => {
    if (params.get(campo)) query.set(campo, params.get(campo));
  });

  try {
    const resposta = await api('/products?' + query.toString());
    mostrarProdutos(resposta.data);
    mostrarPaginacao(resposta.meta);
  } catch (erro) {
    lista.innerHTML = '';
    mensagem.textContent = erro.message || 'Não foi possível carregar os produtos.';
  }
}

function imagem(produto) {
  if (produto.image_url) {
    return `<img src="${escapeHtml(produto.image_url)}" alt="${escapeHtml(produto.name)}" loading="lazy">`;
  }
  return '<div class="sem-imagem">🌿</div>'; // produto sem foto
}

function mostrarProdutos(produtos) {
  // Busca sem resultado: mensagem explicando (nunca uma tela vazia)
  if (produtos.length === 0) {
    lista.innerHTML = '';
    mensagem.innerHTML = 'Nenhum produto encontrado. <a href="/index.html">Limpar filtros</a>';
    return;
  }

  mensagem.textContent = '';
  lista.innerHTML = produtos.map((p) => `
    <article class="card ${p.available ? '' : 'card-indisponivel'}">
      <a href="/produto.html?id=${p.id}">
        ${imagem(p)}
        <h2>${escapeHtml(p.name)}</h2>
      </a>
      <p class="categoria-nome">${p.category ? escapeHtml(p.category.name) : ''}</p>
      <p class="preco">${formatPrice(p.price_cents)}</p>
      ${p.available ? '' : '<span class="selo-indisponivel">Indisponível</span>'}
    </article>
  `).join('');
}

function linkDaPagina(numero) {
  const q = new URLSearchParams(params); // mantém os filtros
  q.set('page', numero);
  return '/index.html?' + q.toString();
}

function mostrarPaginacao(meta) {
  if (!meta || meta.last_page <= 1) {
    paginacao.innerHTML = '';
    return;
  }

  const anterior = meta.current_page > 1
    ? `<a href="${linkDaPagina(meta.current_page - 1)}">‹ Anterior</a>` : '';
  const proxima = meta.current_page < meta.last_page
    ? `<a href="${linkDaPagina(meta.current_page + 1)}">Próxima ›</a>` : '';

  paginacao.innerHTML = `${anterior}<span>Página ${meta.current_page} de ${meta.last_page}</span>${proxima}`;
}

iniciar();