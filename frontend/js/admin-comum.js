// Parte em comum das páginas do painel: confere se é admin e monta o sub-menu.

import { loadLayout } from './layout.js';

// Devolve o usuário admin, ou null quando a página não deve continuar.
export async function iniciarAdmin() {
  const usuario = await loadLayout();

  // Sem login: vai para o login e volta para esta página
  if (!usuario) {
    location.href = '/login.html?next=' + encodeURIComponent(location.pathname + location.search);
    return null;
  }

  // Logado mas cliente: o painel nem aparece (e a API também recusaria com 403)
  if (usuario.role !== 'admin') {
    document.getElementById('mensagem').textContent = 'Acesso restrito a administradores.';
    return null;
  }

  document.getElementById('subnav').innerHTML = `
    <a href="/admin/index.html" data-sub>Produtos</a>
    <a href="/admin/categorias.html" data-sub>Categorias</a>
    <a href="/admin/pedidos.html" data-sub>Pedidos</a>`;

  document.querySelectorAll('#subnav a').forEach((link) => {
    if (new URL(link.href).pathname === location.pathname || (location.pathname === '/admin/' && link.getAttribute('href').endsWith('index.html'))) {
      link.classList.add('ativo');
    }
  });

  document.getElementById('conteudo-admin').hidden = false; // mostra o conteúdo da página
  return usuario;
}