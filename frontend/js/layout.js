// Monta o cabeçalho (menu) em todas as páginas.
// Cada página chama loadLayout() e recebe de volta o usuário logado (ou null).

import { api } from './api.js';
import { escapeHtml } from './util.js';

let usuarioAtual = null;

export function getUser() {
  return usuarioAtual;
}

// Atualiza o número do carrinho no menu (as páginas de carrinho usam isto depois).
export function atualizarContador(quantidade) {
  const el = document.querySelector('.contador');
  if (el) el.textContent = quantidade;
}

export async function loadLayout() {
  // Quem está logado? Visitante recebe 401, e isso NÃO é erro: só significa "ninguém logado".
  try {
    usuarioAtual = (await api('/auth/me')).data;
  } catch (erro) {
    usuarioAtual = null;
  }

  // Quantos itens há no carrinho (vale para visitante e para logado).
  let quantidade = 0;
  try {
    quantidade = (await api('/cart')).data.items_count;
  } catch (erro) {
    quantidade = 0;
  }

  const u = usuarioAtual;

  document.getElementById('cabecalho').innerHTML = `
    <div class="container barra">
      <a class="logo" href="/index.html">🌿 Loja de Plantas</a>
      <nav>
        <a href="/index.html" data-nav>Catálogo</a>
        <a href="/carrinho.html" data-nav>Carrinho <span class="contador">${quantidade}</span></a>
        ${u ? `
          <a href="/pedidos.html" data-nav>Meus pedidos</a>
          <a href="/perfil.html" data-nav>Perfil</a>
          ${u.role === 'admin' ? '<a href="/admin/index.html" data-nav>Painel</a>' : ''}
          <span class="usuario">Olá, ${escapeHtml(u.name)}</span>
          <button id="btn-sair" type="button" class="btn-link">Sair</button>
        ` : `
          <a href="/login.html" data-nav>Entrar</a>
        `}
      </nav>
    </div>`;

  // Destaca o item do menu da página atual.
  const atual = location.pathname === '/' ? '/index.html' : location.pathname;
  document.querySelectorAll('#cabecalho a[data-nav]').forEach((link) => {
    const caminho = new URL(link.href).pathname;
    const mesmaPagina = caminho === atual;
    const dentroDoPainel = caminho.startsWith('/admin/') && atual.startsWith('/admin/');
    if (mesmaPagina || dentroDoPainel) link.classList.add('ativo');
  });

  // Botão Sair
  const sair = document.getElementById('btn-sair');
  if (sair) {
    sair.addEventListener('click', async () => {
      await api('/auth/logout', { method: 'POST' });
      location.href = '/index.html';
    });
  }

  return usuarioAtual;
}