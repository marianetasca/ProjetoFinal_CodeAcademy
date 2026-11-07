// Perfil: editar os próprios dados e gerenciar endereços (criar, editar, excluir).

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { escapeHtml } from './util.js';

const formDados = document.getElementById('form-dados');
const formEndereco = document.getElementById('form-endereco');
const msgDados = document.getElementById('msg-dados');
const msgEndereco = document.getElementById('msg-endereco');

let enderecos = [];
let editandoId = null; // null = cadastrando um novo; número = editando esse endereço

async function iniciar() {
  const usuario = await loadLayout();

  // Página protegida
  if (!usuario) {
    location.href = '/login.html?next=' + encodeURIComponent('/perfil.html');
    return;
  }

  formDados.elements.name.value = usuario.name;
  formDados.elements.email.value = usuario.email;

  try {
    enderecos = (await api('/addresses')).data;
    desenharEnderecos();
  } catch (erro) {
    msgEndereco.className = 'aviso erro';
    msgEndereco.textContent = erro.message;
  }
}

// ---------- Meus dados ----------
formDados.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  msgDados.textContent = '';

  try {
    await api('/auth/me', { method: 'PUT', body: Object.fromEntries(new FormData(formDados)) });
    msgDados.className = 'aviso sucesso';
    msgDados.textContent = 'Dados atualizados.';
    await loadLayout(); // atualiza o "Olá, ..." do menu
  } catch (erro) {
    msgDados.className = 'aviso erro';
    msgDados.textContent = erro.message; // ex.: "Este valor de e-mail já está em uso."
  }
});

// ---------- Endereços ----------
function desenharEnderecos() {
  const area = document.getElementById('lista-enderecos');

  if (enderecos.length === 0) {
    area.innerHTML = '<p class="mensagem">Você ainda não tem endereços cadastrados.</p>';
    return;
  }

  area.innerHTML = enderecos.map((e) => `
    <div class="endereco-item">
      <span>${escapeHtml(e.street)}, ${escapeHtml(e.number)}${e.complement ? ' - ' + escapeHtml(e.complement) : ''}<br>
        ${escapeHtml(e.district)} · ${escapeHtml(e.city)}/${escapeHtml(e.state)} · CEP ${escapeHtml(e.zip_code)}</span>
      <span>
        <button type="button" class="btn-link-verde" data-editar="${e.id}">Editar</button>
        <button type="button" class="btn-remover" data-excluir="${e.id}">Excluir</button>
      </span>
    </div>`).join('');

  area.querySelectorAll('[data-editar]').forEach((b) => b.addEventListener('click', () => editar(parseInt(b.dataset.editar, 10))));
  area.querySelectorAll('[data-excluir]').forEach((b) => b.addEventListener('click', () => excluir(parseInt(b.dataset.excluir, 10))));
}

// Coloca o endereço escolhido no formulário para a pessoa alterar
function editar(id) {
  const e = enderecos.find((x) => x.id === id);
  editandoId = id;

  ['street', 'number', 'complement', 'district', 'city', 'state', 'zip_code'].forEach((campo) => {
    formEndereco.elements[campo].value = e[campo] || '';
  });

  document.getElementById('titulo-endereco').textContent = 'Editar endereço';
  document.getElementById('btn-cancelar-edicao').hidden = false;
  formEndereco.scrollIntoView?.({ behavior: 'smooth' });
}

function sairDaEdicao() {
  editandoId = null;
  formEndereco.reset();
  document.getElementById('titulo-endereco').textContent = 'Novo endereço';
  document.getElementById('btn-cancelar-edicao').hidden = true;
}

document.getElementById('btn-cancelar-edicao').addEventListener('click', sairDaEdicao);

formEndereco.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  msgEndereco.textContent = '';

  const dados = Object.fromEntries(new FormData(formEndereco));
  dados.state = dados.state.toUpperCase(); // "pr" vira "PR"

  try {
    if (editandoId) {
      const resposta = await api(`/addresses/${editandoId}`, { method: 'PUT', body: dados });
      enderecos = enderecos.map((e) => (e.id === editandoId ? resposta.data : e));
    } else {
      const resposta = await api('/addresses', { method: 'POST', body: dados });
      enderecos.unshift(resposta.data);
    }

    sairDaEdicao();
    desenharEnderecos();
    msgEndereco.className = 'aviso sucesso';
    msgEndereco.textContent = 'Endereço salvo.';
  } catch (erro) {
    msgEndereco.className = 'aviso erro';
    msgEndereco.textContent = erro.message;
  }
});

async function excluir(id) {
  if (!confirm('Excluir este endereço? Pedidos já feitos não são afetados.')) return;
  msgEndereco.textContent = '';

  try {
    await api(`/addresses/${id}`, { method: 'DELETE' });
    enderecos = enderecos.filter((e) => e.id !== id);
    if (editandoId === id) sairDaEdicao();
    desenharEnderecos();
  } catch (erro) {
    msgEndereco.className = 'aviso erro';
    msgEndereco.textContent = erro.message;
  }
}

iniciar();