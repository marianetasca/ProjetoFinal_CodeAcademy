// Painel: todos os pedidos da loja, com filtro por status e avanço de status.
// Ordem válida: pago -> enviado -> entregue. A API recusa (409) qualquer outra transição.

import { api } from './api.js';
import { iniciarAdmin } from './admin-comum.js';
import { formatPrice, formatDate, rotuloStatus, escapeHtml } from './util.js';

const params = new URLSearchParams(location.search);
const lista = document.getElementById('lista');
const mensagem = document.getElementById('mensagem');

// Para cada status, qual é o próximo passo (só existe para pago e enviado)
const PROXIMO = {
  paid: { status: 'shipped', texto: 'Marcar como enviado' },
  shipped: { status: 'delivered', texto: 'Marcar como entregue' },
};

async function iniciar() {
  if (!(await iniciarAdmin())) return;
  document.getElementById('filtro-status').value = params.get('status') || '';
  await carregar();
}

async function carregar() {
  const query = new URLSearchParams();
  ['status', 'page'].forEach((campo) => { if (params.get(campo)) query.set(campo, params.get(campo)); });

  try {
    const resposta = await api('/admin/orders?' + query.toString());
    desenhar(resposta.data);
    mostrarPaginacao(resposta.meta);
  } catch (erro) {
    mensagem.textContent = erro.message;
  }
}

function desenhar(pedidos) {
  if (pedidos.length === 0) {
    lista.innerHTML = '<p class="mensagem">Nenhum pedido encontrado.</p>';
    return;
  }

  lista.innerHTML = `
    <table class="tabela">
      <thead><tr><th>Pedido</th><th>Cliente</th><th>Data</th><th>Total</th><th>Status</th><th>Ações</th></tr></thead>
      <tbody>
        ${pedidos.map((p) => `
          <tr>
            <td><a href="/pedido.html?id=${p.id}">#${p.id}</a></td>
            <td>${p.user ? escapeHtml(p.user.name) : ''}</td>
            <td>${formatDate(p.created_at)}</td>
            <td>${formatPrice(p.total_cents)}</td>
            <td><span class="status status-${p.status}">${rotuloStatus(p.status)}</span></td>
            <td>${PROXIMO[p.status]
              ? `<button type="button" class="botao" data-avancar="${p.id}" data-para="${PROXIMO[p.status].status}">${PROXIMO[p.status].texto}</button>`
              : ''}</td>
          </tr>`).join('')}
      </tbody>
    </table>`;

  lista.querySelectorAll('[data-avancar]').forEach((botao) => {
    botao.addEventListener('click', () => avancar(botao.dataset.avancar, botao.dataset.para));
  });
}

async function avancar(id, novoStatus) {
  mensagem.textContent = '';
  try {
    await api(`/admin/orders/${id}/status`, { method: 'PATCH', body: { status: novoStatus } });
  } catch (erro) {
    mensagem.textContent = erro.message; // ex.: "Transição inválida: ..."
  }
  await carregar(); // mostra sempre o estado real
}

function mostrarPaginacao(meta) {
  const area = document.getElementById('paginacao');
  if (!meta || meta.last_page <= 1) { area.innerHTML = ''; return; }

  const q = new URLSearchParams(params);
  const link = (n, texto) => { q.set('page', n); return `<a href="/admin/pedidos.html?${q.toString()}">${texto}</a>`; };

  area.innerHTML = `${meta.current_page > 1 ? link(meta.current_page - 1, '‹ Anterior') : ''}
    <span>Página ${meta.current_page} de ${meta.last_page}</span>
    ${meta.current_page < meta.last_page ? link(meta.current_page + 1, 'Próxima ›') : ''}`;
}

iniciar();