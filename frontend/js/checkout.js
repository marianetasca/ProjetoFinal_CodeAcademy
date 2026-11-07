// Checkout: escolher (ou cadastrar) o endereço, revisar o pedido e confirmar.
// Só usuário logado acessa. Visitante é mandado para o login e volta para cá.

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { formatPrice, escapeHtml } from './util.js';

const conteudo = document.getElementById('conteudo');
const mensagem = document.getElementById('mensagem');

let enderecos = [];      // endereços do usuário
let selecionado = null;  // id do endereço escolhido

async function iniciar() {
  const usuario = await loadLayout();

  // Página protegida: sem login, vai para o login (e volta aqui depois)
  if (!usuario) {
    location.href = '/login.html?next=' + encodeURIComponent('/checkout.html');
    return;
  }

  try {
    const [carrinho, listaEnderecos] = await Promise.all([api('/cart'), api('/addresses')]);
    enderecos = listaEnderecos.data;
    desenhar(carrinho.data);
  } catch (erro) {
    mensagem.textContent = erro.message || 'Não foi possível carregar o checkout.';
  }
}

function desenhar(carrinho) {
  // Carrinho vazio: não dá para finalizar
  if (carrinho.items.length === 0) {
    conteudo.innerHTML = '<p>Seu carrinho está vazio. <a href="/index.html">Ver o catálogo</a></p>';
    return;
  }

  // Se houver item indisponível, a API recusaria o pedido. Avisamos antes.
  const temIndisponivel = carrinho.items.some((item) => !item.available);

  const linhas = carrinho.items.map((item) => `
    <li class="${item.available ? '' : 'erro'}">
      <span>${item.quantity} × ${escapeHtml(item.product.name)}
        ${item.available ? '' : ' (indisponível)'}</span>
      <span>${formatPrice(item.unit_price_cents)} cada · <strong>${formatPrice(item.subtotal_cents)}</strong></span>
    </li>`).join('');

  conteudo.innerHTML = `
    <div class="itens">
      <section class="bloco">
        <h2>1. Endereço de entrega</h2>
        <div id="lista-enderecos"></div>
        <details id="novo-endereco" ${enderecos.length === 0 ? 'open' : ''}>
          <summary>Cadastrar novo endereço</summary>
          <form id="form-endereco" class="form-endereco">
            <label>Rua <input name="street" maxlength="255" required></label>
            <label>Número <input name="number" maxlength="20" required></label>
            <label>Complemento <input name="complement" maxlength="255"></label>
            <label>Bairro <input name="district" maxlength="255" required></label>
            <label>Cidade <input name="city" maxlength="255" required></label>
            <label>Estado (sigla) <input name="state" maxlength="2" minlength="2" placeholder="PR" required></label>
            <label>CEP <input name="zip_code" maxlength="9" placeholder="85010-000" required></label>
            <button type="submit">Salvar endereço</button>
          </form>
          <p id="erro-endereco" class="erro" role="alert"></p>
        </details>
      </section>

      <section class="bloco">
        <h2>2. Revise os itens</h2>
        <ul class="lista-resumo">${linhas}</ul>
        ${temIndisponivel ? '<p class="erro">Há itens indisponíveis. <a href="/carrinho.html">Ajuste o carrinho</a> para continuar.</p>' : ''}
      </section>
    </div>

    <aside class="resumo">
      <h2>Resumo do pedido</h2>
      <p>Subtotal <strong>${formatPrice(carrinho.total_cents)}</strong></p>
      <p>Frete <strong>${carrinho.shipping_cents === 0 ? 'Grátis' : formatPrice(carrinho.shipping_cents)}</strong></p>
      <p class="total">Total <strong>${formatPrice(carrinho.grand_total_cents)}</strong></p>
      <p class="aviso-frete">Frete fixo de R$ 15,00, grátis em compras a partir de R$ 200,00.</p>
      <button type="button" id="btn-confirmar" class="botao botao-grande" disabled>Confirmar pedido</button>
      <p class="aviso-frete">O pagamento é feito na próxima tela (simulado).</p>
    </aside>`;

  desenharEnderecos();

  document.getElementById('form-endereco').addEventListener('submit', salvarEndereco);
  document.getElementById('btn-confirmar').addEventListener('click', confirmarPedido);
  atualizarBotao(!temIndisponivel);
  conteudo.dataset.podeConfirmar = String(!temIndisponivel);
}

// Mostra os endereços como opções (uma escolhida por vez)
function desenharEnderecos() {
  const area = document.getElementById('lista-enderecos');

  if (enderecos.length === 0) {
    area.innerHTML = '<p class="mensagem">Você ainda não tem endereços. Cadastre um abaixo.</p>';
    return;
  }

  area.innerHTML = enderecos.map((e) => `
    <label class="endereco">
      <input type="radio" name="endereco" value="${e.id}" ${e.id === selecionado ? 'checked' : ''}>
      <span>${escapeHtml(e.street)}, ${escapeHtml(e.number)}${e.complement ? ' - ' + escapeHtml(e.complement) : ''}<br>
        ${escapeHtml(e.district)} · ${escapeHtml(e.city)}/${escapeHtml(e.state)} · CEP ${escapeHtml(e.zip_code)}</span>
    </label>`).join('');

  area.querySelectorAll('input[name="endereco"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      selecionado = parseInt(radio.value, 10);
      atualizarBotao(conteudo.dataset.podeConfirmar === 'true');
    });
  });
}

// O botão só liga quando há endereço escolhido e nenhum item indisponível
function atualizarBotao(podeConfirmar) {
  document.getElementById('btn-confirmar').disabled = !(selecionado && podeConfirmar);
}

async function salvarEndereco(evento) {
  evento.preventDefault();
  const erro = document.getElementById('erro-endereco');
  erro.textContent = '';

  try {
    const resposta = await api('/addresses', {
      method: 'POST',
      body: Object.fromEntries(new FormData(evento.target)),
    });

    enderecos.unshift(resposta.data);     // coloca o novo no começo da lista
    selecionado = resposta.data.id;       // e já deixa escolhido
    desenharEnderecos();
    evento.target.reset();
    document.getElementById('novo-endereco').open = false;
    atualizarBotao(conteudo.dataset.podeConfirmar === 'true');
  } catch (e) {
    erro.textContent = e.message;
  }
}

async function confirmarPedido() {
  const botao = document.getElementById('btn-confirmar');
  mensagem.textContent = '';
  botao.disabled = true; // evita criar o pedido duas vezes com um duplo clique

  try {
    const resposta = await api('/orders', { method: 'POST', body: { address_id: selecionado } });
    location.href = '/pedido.html?id=' + resposta.data.id; // vai para a tela de pagamento
  } catch (erro) {
    // Ex.: "Jiboia: estoque insuficiente (disponível: 1)." ou carrinho vazio
    mensagem.textContent = erro.message;
    botao.disabled = false;
  }
}

iniciar();