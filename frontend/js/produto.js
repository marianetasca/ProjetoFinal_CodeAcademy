// Página de um produto. O endereço é /produto.html?id=3 (cada produto tem URL própria).

import { api } from './api.js';
import { loadLayout, atualizarContador } from './layout.js';
import { formatPrice, escapeHtml } from './util.js';

const id = new URLSearchParams(location.search).get('id');
const mensagem = document.getElementById('mensagem');
const area = document.getElementById('produto');

async function iniciar() {
  await loadLayout();

  // Sem id, ou com id que não é número: nem precisa perguntar para a API
  if (!id || !/^\d+$/.test(id)) {
    return naoEncontrado();
  }

  try {
    const resposta = await api('/products/' + id);
    mostrarProduto(resposta.data);
  } catch (erro) {
    // A API devolve 404 para produto inexistente OU inativo
    if (erro.status === 404) return naoEncontrado();
    mensagem.textContent = erro.message || 'Não foi possível carregar o produto.';
  }
}

function naoEncontrado() {
  area.innerHTML = '';
  mensagem.innerHTML = 'Produto não encontrado. <a href="/index.html">Ver o catálogo</a>';
}

function mostrarProduto(p) {
  document.title = p.name + ' | Loja de Plantas';

  const imagem = p.image_url
    ? `<img src="${escapeHtml(p.image_url)}" alt="${escapeHtml(p.name)}">`
    : '<div class="sem-imagem">🌿</div>';

  // Disponibilidade: deixa CLARO quando não dá para comprar
  const disponibilidade = p.available
    ? `<p class="disponibilidade ok">Em estoque (${p.stock} ${p.stock === 1 ? 'unidade' : 'unidades'})</p>`
    : '<p class="disponibilidade esgotado">Produto indisponível no momento</p>';

  // Só mostra o seletor de quantidade se der para comprar.
  // O máximo é o estoque: a pessoa não consegue escolher mais do que existe.
  const compra = p.available
    ? `<form id="form-carrinho" class="form-compra">
         <label for="quantidade">Quantidade</label>
         <input type="number" id="quantidade" name="quantity" value="1" min="1" max="${p.stock}" required>
         <button type="submit">Adicionar ao carrinho</button>
       </form>`
    : '<button type="button" disabled>Indisponível</button>';

  area.innerHTML = `
    <div class="produto-imagem">${imagem}</div>
    <div class="produto-info">
      <p class="categoria-nome">${p.category ? escapeHtml(p.category.name) : ''}</p>
      <h1>${escapeHtml(p.name)}</h1>
      <p class="preco preco-grande">${formatPrice(p.price_cents)}</p>
      ${disponibilidade}
      <p class="descricao">${escapeHtml(p.description || 'Sem descrição.')}</p>
      ${compra}
      <p id="aviso" class="aviso" role="status"></p>
    </div>`;

  const form = document.getElementById('form-carrinho');
  if (form) form.addEventListener('submit', (evento) => adicionar(evento, p));
}

async function adicionar(evento, produto) {
  evento.preventDefault(); // não deixa o formulário recarregar a página

  const aviso = document.getElementById('aviso');
  const botao = evento.target.querySelector('button');
  const quantidade = parseInt(document.getElementById('quantidade').value, 10);

  // Conferência rápida no navegador. A conferência que vale mesmo é a da API.
  if (!quantidade || quantidade < 1 || quantidade > produto.stock) {
    aviso.className = 'aviso erro';
    aviso.textContent = `Escolha uma quantidade entre 1 e ${produto.stock}.`;
    return;
  }

  botao.disabled = true; // evita clicar duas vezes enquanto envia
  try {
    const resposta = await api('/cart/items', {
      method: 'POST',
      body: { product_id: produto.id, quantity: quantidade },
    });

    atualizarContador(resposta.data.items_count); // atualiza o número do menu
    aviso.className = 'aviso sucesso';
    aviso.innerHTML = 'Adicionado ao carrinho. <a href="/carrinho.html">Ver carrinho</a>';
  } catch (erro) {
    // Ex.: "Estoque insuficiente" quando já há unidades desse produto no carrinho
    aviso.className = 'aviso erro';
    aviso.textContent = erro.message;
  } finally {
    botao.disabled = false;
  }
}

iniciar();