## Regra de fusão de carrinhos

Ao fazer login ou registro, o carrinho de visitante (identificado pelo id da sessão)
é fundido ao carrinho da conta:
- produto só no carrinho de visitante: é copiado para o da conta;
- produto nos dois: as quantidades são somadas, limitadas ao estoque disponível
  (se exceder, ajusta para o máximo e devolve um aviso em `cart_warnings`);
- produto inativo ou sem estoque: não é copiado, e devolve um aviso;
- ao final, o carrinho de visitante é apagado e a sessão antiga deixa de valer.
A fusão roda dentro de uma transação de banco.
