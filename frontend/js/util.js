// Funções pequenas que várias páginas usam.

// 3990 (centavos) vira "R$ 39,90"
export function formatPrice(cents) {
  return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

// Troca caracteres perigosos por texto seguro.
// SEMPRE use isto ao colocar dados vindos da API dentro de innerHTML (evita XSS).
export function escapeHtml(texto) {
  return String(texto ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Para onde ir depois do login: o parâmetro ?next=/checkout.html da URL.
// Só aceita caminhos do PRÓPRIO site (começam com uma única "/").
// Sem isso, alguém poderia criar um link de login que leva para um site falso.
export function proximaPagina() {
  const destino = new URLSearchParams(location.search).get('next');
  if (destino && /^\/(?![\/\\])/.test(destino)) return destino;
  return '/index.html';
}

// Nomes dos status do pedido, em português
const ROTULOS_STATUS = {
  pending: 'Aguardando pagamento',
  paid: 'Pago',
  shipped: 'Enviado',
  delivered: 'Entregue',
  cancelled: 'Cancelado',
};

export function rotuloStatus(status) {
  return ROTULOS_STATUS[status] || status;
}

// "2026-11-05T14:30:00.000000Z" vira "05/11/2026 11:30"
export function formatDate(texto) {
  return texto ? new Date(texto).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) : '';
}