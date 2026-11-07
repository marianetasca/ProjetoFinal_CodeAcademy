// Página do carrinho. Funciona para visitante e para usuário logado.
// Importante: quem calcula subtotais e total é a API. Aqui só mostramos o que ela devolve.

import { api } from './api.js';
import { loadLayout, getUser, atualizarContador } from './layout.js';
import { formatPrice, escapeHtml } from './util.js';

const conteudo = document.getElementById('conteudo');
const mensagem = document.getElementById('mensagem');

async function iniciar() {
  await loadLayout();
  try {
    desenhar((await api('/cart')).data);
  } catch (erro) {
    mensagem.textContent = erro.message || 'Não foi possível carregar o carrinho.';
  }
}

// Desenha a página inteira a partir do carrinho que a API devolveu.
function desenhar(carrinho) {
  atualizarContador(carrinho.items_count); // mantém o número do menu igual ao do carrinho

  // Carrinho vazio: explica e oferece o caminho de volta
  if (carrinho.items.length === 0) {
    conteudo.innerHTML = '<p>Seu carrinho está vazio. <a href="/index.html">Ver o catálogo</a></p>';
    return;
  }

  const temDisponivel = carrinho.items.some((item) => item.available);

  // Finalizar compra:
  //  - logado: vai direto para o checkout
  //  - visitante: vai para o login e volta ao checkout depois. O carrinho NÃO se perde,
  //    porque ele está guardado no servidor e é fundido à conta no login.
  const destino = getUser() ? '/checkout.html' : '/login.html?next=/checkout.html';
  const aviso = getUser() ? '' : '<p class="aviso-frete">Você vai entrar ou criar uma conta. Seu carrinho será mantido.</p>';

  const botaoFinalizar = temDisponivel
    ? `<a class="botao botao-grande" href="${destino}">Finalizar compra</a>`
    : '<button type="button" disabled>Nenhum item disponível</button>';

  conteudo.innerHTML = `
    <div class="itens">${carrinho.items.map(linhaDoItem).join('')}</div>
    <aside class="resumo">
      <h2>Resumo</h2>
      <p>Itens no carrinho: <strong>${carrinho.items_count}</strong></p>
      <p class="total">Total <strong>${formatPrice(carrinho.total_cents)}</strong></p>
      <p class="aviso-frete">O frete (R$ 15,00, grátis a partir de R$ 200,00) é somado no checkout.</p>
      ${botaoFinalizar}
      ${aviso}
    </aside>`;

  // Liga os eventos dos campos de quantidade e dos botões "Remover"
  conteudo.querySelectorAll('[data-quantidade]').forEach((campo) => {
    campo.addEventListener('change', () => alterarQuantidade(campo.dataset.quantidade, campo.value));
  });
  conteudo.querySelectorAll('[data-remover]').forEach((botao) => {
    botao.addEventListener('click', () => remover(botao.dataset.remover));
  });
}

function linhaDoItem(item) {
  const p = item.product;
  const imagem = p.image_url
    ? `<img src="${escapeHtml(p.image_url)}" alt="${escapeHtml(p.name)}">`
    : '<div class="sem-imagem">🌿</div>';

  // item.available = false: produto inativo, apagado ou sem estoque para essa quantidade.
  // Esses itens aparecem sinalizados e NÃO entram no total.
  const alerta = item.available
    ? ''
    : '<p class="erro">Indisponível: sem estoque suficiente ou produto desativado. Este item não entra no total.</p>';

  // Se o produto nem existe mais para venda (p.available = false), só dá para remover.
  const podeEditar = p.available;

  return `
    <article class="item ${item.available ? '' : 'item-indisponivel'}">
      <a href="/produto.html?id=${p.id}" class="item-imagem">${imagem}</a>
      <div class="item-info">
        <a href="/produto.html?id=${p.id}"><strong>${escapeHtml(p.name)}</strong></a>
        <p class="categoria-nome">${formatPrice(item.unit_price_cents)} cada</p>
        ${alerta}
        <label>Quantidade
          <input type="number" min="1" max="${p.stock}" value="${item.quantity}"
                 data-quantidade="${item.id}" ${podeEditar ? '' : 'disabled'}>
        </label>
        <button type="button" class="btn-remover" data-remover="${item.id}">Remover</button>
      </div>
      <p class="subtotal">${item.available ? formatPrice(item.subtotal_cents) : 'Fora do total'}</p>
    </article>`;
}

// Executa uma ação na API e redesenha com o carrinho novo.
// Se der erro (ex.: estoque insuficiente), volta a mostrar o carrinho real e explica o erro.
async function atualizar(acao) {
  mensagem.textContent = '';
  try {
    desenhar((await acao()).data);
  } catch (erro) {
    try {
      desenhar((await api('/cart')).data); // restaura os valores corretos nos campos
    } catch (outroErro) {
      // se nem isso funcionar, a mensagem abaixo já avisa
    }
    mensagem.textContent = erro.message;
  }
}

function alterarQuantidade(idDoItem, valor) {
  const quantidade = parseInt(valor, 10);

  // Valor inválido (vazio, zero, negativo): só redesenha com o que o servidor tem
  if (!quantidade || quantidade < 1) {
    return atualizar(() => api('/cart'));
  }

  return atualizar(() => api(`/cart/items/${idDoItem}`, { method: 'PATCH', body: { quantity: quantidade } }));
}

function remover(idDoItem) {
  return atualizar(() => api(`/cart/items/${idDoItem}`, { method: 'DELETE' }));
}

iniciar();