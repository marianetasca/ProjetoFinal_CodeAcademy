// Parte em comum entre as páginas de login e de cadastro:
// o que fazer depois que a API aceitou a entrada da pessoa.

import { escapeHtml } from './util.js';

// A API devolve "cart_warnings" quando a fusão do carrinho precisou ajustar algo
// (quantidade limitada ao estoque, produto indisponível...).
//  - Sem avisos: vai direto para o destino.
//  - Com avisos: mostra a lista e um botão "Continuar", para a pessoa conseguir ler.
export function concluirEntrada(resposta, destino, areaAvisos) {
  const avisos = resposta.cart_warnings || [];

  if (avisos.length === 0) {
    location.href = destino;
    return;
  }

  areaAvisos.innerHTML = `
    <div class="avisos-carrinho">
      <h2>Atenção com o seu carrinho</h2>
      <ul>${avisos.map((aviso) => `<li>${escapeHtml(aviso)}</li>`).join('')}</ul>
      <a class="botao" href="${escapeHtml(destino)}">Continuar</a>
    </div>`;
}