<?php
/**
 * Calcula o valor de mercado de um jogador baseado em OVR e idade.
 * OVR 75 ≈ R$1M | OVR 90 ≈ R$10M | jovens têm prêmio, veteranos têm desconto.
 */
function valorMercado(int $ovr, int $idade): int {
    // Exponencial calibrada: OVR 75 → ~R$1M, OVR 90 → ~R$10M
    $base = 10 * exp(0.1535 * $ovr);

    // Fator de idade
    if ($idade <= 21)      $af = 1.30;
    elseif ($idade <= 24)  $af = 1.15;
    elseif ($idade <= 27)  $af = 1.00;
    elseif ($idade <= 30)  $af = 0.80;
    elseif ($idade <= 33)  $af = 0.60;
    else                   $af = 0.45;

    $v = max(50000, (int)($base * $af));

    // Arredondamento progressivo
    if ($v < 500000)  return (int)round($v / 10000) * 10000;
    if ($v < 2000000) return (int)round($v / 50000) * 50000;
    if ($v < 5000000) return (int)round($v / 200000) * 200000;
    return (int)round($v / 500000) * 500000;
}

/**
 * Preço de venda = 72% do valor de mercado (spread bid-ask).
 */
function precoVenda(int $ovr, int $idade): int {
    return (int)(valorMercado($ovr, $idade) * 0.72);
}
