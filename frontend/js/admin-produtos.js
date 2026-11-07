// Painel: CRUD de produtos (criar, editar, atualizar estoque, ativar/desativar, excluir).

import { api } from './api.js';
import { iniciarAdmin } from './admin-comum.js';
import { formatPrice, escapeHtml } from './util.js';

const params = new URLSearchParams(location.search);
const form = document.getElementById('form-produto');
const msgForm = document.getElementById('msg-form');
const mensagem = document.getElementById('mensagem');

let produtos = [];     // produtos da página atual
let editandoId = null; // null = cadastrando; número = editando esse produto

async function iniciar() {
  if (!(await iniciarAdmin())) return;

  document.getElementById('busca').value = params.get('search') || '';
  await carregarCategorias();
  await carregarProdutos();
}

async function carregarCategorias() {
  const select = form.elements.category_id;
  try {
    (await api('/categories')).data.forEach((c) => {
      select.insertAdjacentHTML('beforeend', `<option value="${c.id}">${escapeHtml(c.name)}</option>`);
    });
  } catch (erro) {
    mensagem.textContent = erro.message;
  }
}

async function carregarProdutos() {
  const query = new URLSearchParams();
  ['search', 'page'].forEach((campo) => { if (params.get(campo)) query.set(campo, params.get(campo)); });

  try {
    const resposta = await api('/admin/products?' + query.toString());
    produtos = resposta.data;
    desenharTabela();
    mostrarPaginacao(resposta.meta);
  } catch (erro) {
    mensagem.textContent = erro.message;
  }
}

function desenharTabela() {
  const lista = document.getElementById('lista');

  if (produtos.length === 0) {
    lista.innerHTML = '<p class="mensagem">Nenhum produto encontrado.</p>';
    return;
  }

  lista.innerHTML = `
    <table class="tabela">
      <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Ativo</th><th>Ações</th></tr></thead>
      <tbody>
        ${produtos.map((p) => `
          <tr>
            <td>${escapeHtml(p.name)}</td>
            <td>${p.category ? escapeHtml(p.category.name) : ''}</td>
            <td>${formatPrice(p.price_cents)}</td>
            <td class="${p.stock === 0 ? 'erro' : ''}">${p.stock}</td>
            <td>${p.active ? 'Sim' : 'Não'}</td>
            <td>
              <button type="button" class="btn-link-verde" data-editar="${p.id}">Editar</button>
              <button type="button" class="btn-remover" data-excluir="${p.id}">Excluir</button>
            </td>
          </tr>`).join('')}
      </tbody>
    </table>`;

  lista.querySelectorAll('[data-editar]').forEach((b) => b.addEventListener('click', () => editar(parseInt(b.dataset.editar, 10))));
  lista.querySelectorAll('[data-excluir]').forEach((b) => b.addEventListener('click', () => excluir(parseInt(b.dataset.excluir, 10))));
}

function mostrarPaginacao(meta) {
  const area = document.getElementById('paginacao');
  if (!meta || meta.last_page <= 1) { area.innerHTML = ''; return; }

  const q = new URLSearchParams(params);
  const link = (n, texto) => { q.set('page', n); return `<a href="/admin/index.html?${q.toString()}">${texto}</a>`; };

  area.innerHTML = `${meta.current_page > 1 ? link(meta.current_page - 1, '‹ Anterior') : ''}
    <span>Página ${meta.current_page} de ${meta.last_page}</span>
    ${meta.current_page < meta.last_page ? link(meta.current_page + 1, 'Próxima ›') : ''}`;
}

// ---------- Formulário ----------
function editar(id) {
  const p = produtos.find((x) => x.id === id);
  editandoId = id;

  form.elements.category_id.value = p.category ? p.category.id : '';
  form.elements.name.value = p.name;
  form.elements.preco.value = (p.price_cents / 100).toFixed(2); // centavos viram reais
  form.elements.stock.value = p.stock;
  form.elements.description.value = p.description || '';
  form.elements.active.checked = p.active;
  form.elements.image.value = '';

  document.getElementById('dica-imagem').textContent = p.image_url ? 'Já tem imagem. Envie outra só se quiser trocar.' : '';
  document.getElementById('titulo-form').textContent = 'Editar produto';
  document.getElementById('btn-cancelar').hidden = false;
  form.scrollIntoView?.({ behavior: 'smooth' });
}

function sairDaEdicao() {
  editandoId = null;
  form.reset();
  document.getElementById('dica-imagem').textContent = '';
  document.getElementById('titulo-form').textContent = 'Novo produto';
  document.getElementById('btn-cancelar').hidden = true;
}

document.getElementById('btn-cancelar').addEventListener('click', sairDaEdicao);

form.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  msgForm.textContent = '';

  // Arquivos só viajam em multipart/form-data, por isso usamos FormData (e não JSON)
  const dados = new FormData(form);

  // A API trabalha com centavos: 39.90 reais viram 3990
  dados.set('price_cents', String(Math.round(parseFloat(dados.get('preco')) * 100)));
  dados.delete('preco');

  // Checkbox desmarcado não é enviado pelo navegador. Mandamos 1 ou 0 explicitamente.
  dados.set('active', form.elements.active.checked ? '1' : '0');

  // Sem arquivo escolhido: não manda o campo da imagem (e a imagem atual é mantida)
  if (form.elements.image.files.length === 0) dados.delete('image');

  try {
    if (editandoId) {
      // O PHP não lê multipart em requisições PUT. Enviamos POST com _method=PUT,
      // e o Laravel trata como PUT.
      dados.set('_method', 'PUT');
      await api(`/admin/products/${editandoId}`, { method: 'POST', body: dados });
    } else {
      await api('/admin/products', { method: 'POST', body: dados });
    }

    sairDaEdicao();
    msgForm.className = 'aviso sucesso';
    msgForm.textContent = 'Produto salvo.';
    await carregarProdutos();
  } catch (erro) {
    msgForm.className = 'aviso erro';
    msgForm.textContent = erro.message;
  }
});

async function excluir(id) {
  if (!confirm('Excluir este produto? Pedidos antigos não são afetados.')) return;
  mensagem.textContent = '';

  try {
    await api(`/admin/products/${id}`, { method: 'DELETE' });
    if (editandoId === id) sairDaEdicao();
    await carregarProdutos();
  } catch (erro) {
    mensagem.textContent = erro.message;
  }
}

iniciar();