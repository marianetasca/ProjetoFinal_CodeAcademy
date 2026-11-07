// Página de login.

import { api } from './api.js';
import { loadLayout } from './layout.js';
import { concluirEntrada } from './auth.js';
import { proximaPagina } from './util.js';

const form = document.getElementById('form-login');
const mensagem = document.getElementById('mensagem');
const areaAvisos = document.getElementById('avisos');
const destino = proximaPagina(); // para onde ir depois de entrar (padrão: catálogo)

async function iniciar() {
  const usuario = await loadLayout();

  // Quem já está logado não precisa ver o login
  if (usuario) {
    location.href = destino;
    return;
  }

  // O link "criar conta" leva junto o destino
  document.getElementById('link-cadastro').href = '/cadastro.html?next=' + encodeURIComponent(destino);
}

form.addEventListener('submit', async (evento) => {
  evento.preventDefault(); // não recarrega a página
  mensagem.textContent = '';

  const botao = form.querySelector('button');
  botao.disabled = true; // evita enviar duas vezes

  try {
    // FormData lê os campos do formulário pelo atributo name (email e password)
    const resposta = await api('/auth/login', {
      method: 'POST',
      body: Object.fromEntries(new FormData(form)),
    });
    form.hidden = true; // esconde o formulário: a entrada foi aceita
    concluirEntrada(resposta, destino, areaAvisos);
  } catch (erro) {
    mensagem.textContent = erro.message; // ex.: "E-mail ou senha inválidos."
    botao.disabled = false;
  }
});

iniciar();