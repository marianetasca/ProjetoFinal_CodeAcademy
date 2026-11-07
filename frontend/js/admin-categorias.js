// Painel: CRUD de categorias.
// Categoria que ainda tem produtos NÃO pode ser excluída: a API responde 409 e mostramos o motivo.

import { api } from './api.js';
import { iniciarAdmin } from './admin-comum.js';
import { escapeHtml } from './util.js';

const form = document.getElementById('form-categoria');
const msgForm = document.getElementById('msg-form');
const mensagem = document.getElementById('mensagem');

let categorias = [];
let editandoId = null;

async function iniciar() {
  if (!(await iniciarAdmin())) return;
  await carregar();
}

async function carregar() {
  try {
    categorias = (await api('/categories')).data;
    desenhar();
  } catch (erro) {
    mensagem.textContent = erro.message;
  }
}

function desenhar() {
  const lista = document.getElementById('lista');

  if (categorias.length === 0) {
    lista.innerHTML = '<p class="mensagem">Nenhuma categoria cadastrada.</p>';
    return;
  }

  lista.innerHTML = `
    <table class="tabela">
      <thead><tr><th>Nome</th><th>Slug</th><th>Ações</th></tr></thead>
      <tbody>
        ${categorias.map((c) => `
          <tr>
            <td>${escapeHtml(c.name)}</td>
            <td>${escapeHtml(c.slug)}</td>
            <td>
              <button type="button" class="btn-link-verde" data-editar="${c.id}">Editar</button>
              <button type="button" class="btn-remover" data-excluir="${c.id}">Excluir</button>
            </td>
          </tr>`).join('')}
      </tbody>
    </table>`;

  lista.querySelectorAll('[data-editar]').forEach((b) => b.addEventListener('click', () => editar(parseInt(b.dataset.editar, 10))));
  lista.querySelectorAll('[data-excluir]').forEach((b) => b.addEventListener('click', () => excluir(parseInt(b.dataset.excluir, 10))));
}

function editar(id) {
  editandoId = id;
  form.elements.name.value = categorias.find((c) => c.id === id).name;
  document.getElementById('titulo-form').textContent = 'Editar categoria';
  document.getElementById('btn-cancelar').hidden = false;
}

function sairDaEdicao() {
  editandoId = null;
  form.reset();
  document.getElementById('titulo-form').textContent = 'Nova categoria';
  document.getElementById('btn-cancelar').hidden = true;
}

document.getElementById('btn-cancelar').addEventListener('click', sairDaEdicao);

form.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  msgForm.textContent = '';
  const dados = Object.fromEntries(new FormData(form));

  try {
    if (editandoId) {
      await api(`/admin/categories/${editandoId}`, { method: 'PUT', body: dados });
    } else {
      await api('/admin/categories', { method: 'POST', body: dados });
    }
    sairDaEdicao();
    msgForm.className = 'aviso sucesso';
    msgForm.textContent = 'Categoria salva.';
    await carregar();
  } catch (erro) {
    msgForm.className = 'aviso erro';
    msgForm.textContent = erro.message; // ex.: "Este valor de nome já está em uso."
  }
});

async function excluir(id) {
  if (!confirm('Excluir esta categoria?')) return;
  mensagem.textContent = '';

  try {
    await api(`/admin/categories/${id}`, { method: 'DELETE' });
    if (editandoId === id) sairDaEdicao();
    await carregar();
  } catch (erro) {
    mensagem.textContent = erro.message; // 409: "Não é possível excluir uma categoria que possui produtos."
  }
}

iniciar();