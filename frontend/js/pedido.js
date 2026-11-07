// Detalhe de um pedido (/pedido.html?id=5): itens, valores, endereço, status.
// Quando o pedido está aguardando pagamento, mostra "Confirmar pagamento" (simulado) e "Cancelar".

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { formatPrice, formatDate, rotuloStatus, escapeHtml } from './util.js';

const id = new URLSearchParams(location.search).get('id');
const conteudo = document.getElementById('conteudo');
const mensagem = document.getElementById('mensagem');

async function iniciar() {
  const usuario = await loadLayout();

  // Página protegida: sem login, vai para o login e volta para este mesmo pedido
  if (!usuario) {
    location.href = '/login.html?next=' + encodeURIComponent(location.pathname + location.search);
    return;
  }

  if (!id || !/^\d+$/.test(id)) {
    mensagem.textContent = 'Pedido não encontrado.';
    return;
  }

  await carregar();
}

async function carregar() {
  try {
    desenhar((await api('/orders/' + id)).data);
  } catch (erro) {
    if (erro.status === 403) mensagem.textContent = 'Você não tem acesso a este pedido.';
    else if (erro.status === 404) mensagem.textContent = 'Pedido não encontrado.';
    else mensagem.textContent = erro.message;
  }
}

function desenhar(pedido) {
  document.title = `Pedido #${pedido.id} | Loja de Plantas`;
  const e = pedido.shipping_address;

  const linhas = pedido.items.map((item) => `
    <li>
      <span>${item.quantity} × ${escapeHtml(item.product_name)}</span>
      <span>${formatPrice(item.unit_price_cents)} cada · <strong>${formatPrice(item.subtotal_cents)}</strong></span>
    </li>`).join('');

  // Só pedido aguardando pagamento tem ações
  const acoes = pedido.status === 'pending'
    ? `<p class="aviso-simulado">Pagamento <strong>simulado</strong>, para fins acadêmicos.
         Nenhum dado de cartão é solicitado ou armazenado.</p>
       <button type="button" id="btn-pagar" class="botao botao-grande">Confirmar pagamento</button>
       <button type="button" id="btn-cancelar" class="btn-remover">Cancelar pedido</button>`
    : '';

  conteudo.innerHTML = `
    <div class="itens">
      <section class="bloco">
        <h1>Pedido #${pedido.id}</h1>
        <p>Feito em ${formatDate(pedido.created_at)}</p>
        <p>Status: <span class="status status-${pedido.status}">${rotuloStatus(pedido.status)}</span></p>
        ${pedido.paid_at ? `<p>Pago em ${formatDate(pedido.paid_at)}</p>` : ''}
      </section>

      <section class="bloco">
        <h2>Itens</h2>
        <ul class="lista-resumo">${linhas}</ul>
      </section>

      <section class="bloco">
        <h2>Endereço de entrega</h2>
        <p>${escapeHtml(e.street)}, ${escapeHtml(e.number)}${e.complement ? ' - ' + escapeHtml(e.complement) : ''}<br>
          ${escapeHtml(e.district)} · ${escapeHtml(e.city)}/${escapeHtml(e.state)} · CEP ${escapeHtml(e.zip_code)}</p>
      </section>
    </div>

    <aside class="resumo">
      <h2>Valores</h2>
      <p>Subtotal <strong>${formatPrice(pedido.subtotal_cents)}</strong></p>
      <p>Frete <strong>${pedido.shipping_cents === 0 ? 'Grátis' : formatPrice(pedido.shipping_cents)}</strong></p>
      <p class="total">Total <strong>${formatPrice(pedido.total_cents)}</strong></p>
      ${acoes}
    </aside>`;

  document.getElementById('btn-pagar')?.addEventListener('click', pagar);
  document.getElementById('btn-cancelar')?.addEventListener('click', cancelar);
}

// Executa uma ação (pagar ou cancelar) e redesenha com o pedido atualizado
async function executar(caminho) {
  mensagem.textContent = '';
  try {
    desenhar((await api(caminho, { method: 'POST' })).data);
  } catch (erro) {
    mensagem.textContent = erro.message; // ex.: "Este pedido não está aguardando pagamento."
    await carregar();                    // mostra o estado real do pedido
  }
}

function pagar() {
  return executar(`/orders/${id}/pay`);
}

function cancelar() {
  if (!confirm('Cancelar este pedido? Os itens voltam para o estoque.')) return;
  return executar(`/orders/${id}/cancel`);
}

iniciar();