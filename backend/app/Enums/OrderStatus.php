<?php

namespace App\Enums;

// Enum com valor em texto: cada caso é gravado no banco como essa string.
// Evita escrever 'paid' ou 'pago' solto pelo código e errar a grafia.
enum OrderStatus: string
{
    case Pending   = 'pending';     // aguardando pagamento
    case Paid      = 'paid';        // pago
    case Shipped   = 'shipped';     // enviado
    case Delivered = 'delivered';   // entregue
    case Cancelled = 'cancelled';   // cancelado

    // Transições válidas, exatamente como na spec:
    // pending -> paid -> shipped -> delivered, e pending -> cancelled.
    // Qualquer outra combinação é inválida (o Service vai devolver 409).
    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending   => in_array($next, [self::Paid, self::Cancelled]),
            self::Paid      => $next === self::Shipped,
            self::Shipped   => $next === self::Delivered,
            self::Delivered, self::Cancelled => false,   // estados finais
        };
    }
}
