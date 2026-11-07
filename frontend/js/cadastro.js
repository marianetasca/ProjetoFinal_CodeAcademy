// Página de cadastro (registro). Depois de criar a conta, a pessoa já fica logada.

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { concluirEntrada } from './auth.js';
import { proximaPagina } from './util.js';

const form = document.getElementById('form-cadastro');
const mensagem = document.getElementById('mensagem');
const areaAvisos = document.getElementById('avisos');
const destino = proximaPagina();

async function iniciar() {
  const usuario = await loadLayout();

  if (usuario) {
    location.href = destino;
    return;
  }

  document.getElementById('link-login').href = '/login.html?next=' + encodeURIComponent(destino);
}

form.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  mensagem.textContent = '';

  // Lê todos os campos do formulário: { name, email, password, password_confirmation }
  const dados = Object.fromEntries(new FormData(form));

  // Confere no navegador para avisar rápido. A API também valida.
  if (dados.password !== dados.password_confirmation) {
    mensagem.textContent = 'A confirmação da senha não confere.';
    return;
  }

  const botao = form.querySelector('button');
  botao.disabled = true;

  try {
    const resposta = await api('/auth/register', { method: 'POST', body: dados });
    form.hidden = true;
    concluirEntrada(resposta, destino, areaAvisos);
  } catch (erro) {
    mensagem.textContent = erro.message; // ex.: "O e-mail já está em uso."
    botao.disabled = false;
  }
});

iniciar();