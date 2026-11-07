// Lista dos pedidos do usuário logado (10 por página).

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { formatPrice, formatDate, rotuloStatus } from './util.js';

const params = new URLSearchParams(location.search);
const lista = document.getElementById('lista');
const mensagem = document.getElementById('mensagem');
const paginacao = document.getElementById('paginacao');

async function iniciar() {
  const usuario = await loadLayout();

  // Página protegida: sem login, vai para o login e volta para cá
  if (!usuario) {
    location.href = '/login.html?next=' + encodeURIComponent(location.pathname + location.search);
    return;
  }

  const pagina = params.get('page');
  const query = pagina ? '?page=' + encodeURIComponent(pagina) : '';

  try {
    const resposta = await api('/orders' + query);
    desenhar(resposta.data);
    mostrarPaginacao(resposta.meta);
  } catch (erro) {
    mensagem.textContent = erro.message || 'Não foi possível carregar os pedidos.';
  }
}

function desenhar(pedidos) {
  if (pedidos.length === 0) {
    lista.innerHTML = '<p>Você ainda não fez nenhum pedido. <a href="/index.html">Ver o catálogo</a></p>';
    return;
  }

  lista.innerHTML = `
    <table class="tabela">
      <thead>
        <tr><th>Pedido</th><th>Data</th><th>Total</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        ${pedidos.map((p) => `
          <tr>
            <td>#${p.id}</td>
            <td>${formatDate(p.created_at)}</td>
            <td>${formatPrice(p.total_cents)}</td>
            <td><span class="status status-${p.status}">${rotuloStatus(p.status)}</span></td>
            <td><a href="/pedido.html?id=${p.id}">Ver detalhes</a></td>
          </tr>`).join('')}
      </tbody>
    </table>`;
}

function mostrarPaginacao(meta) {
  if (!meta || meta.last_page <= 1) {
    paginacao.innerHTML = '';
    return;
  }

  const link = (numero, texto) => `<a href="/pedidos.html?page=${numero}">${texto}</a>`;
  const anterior = meta.current_page > 1 ? link(meta.current_page - 1, '‹ Anterior') : '';
  const proxima = meta.current_page < meta.last_page ? link(meta.current_page + 1, 'Próxima ›') : '';

  paginacao.innerHTML = `${anterior}<span>Página ${meta.current_page} de ${meta.last_page}</span>${proxima}`;
}

iniciar();