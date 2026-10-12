<?php
declare(strict_types=1);

/**
 * «Бібліотека» допоміжних функцій (практична робота №3, варіант 6).
 */

if (!function_exists('convertGramsToOz')) {
    /** Переводить грами в унції (1 oz = 28,3495 г), округлення до 0,1. */
    function convertGramsToOz(float $grams): float
    {
        return round($grams / 28.3495, 1);
    }
}

if (!function_exists('formatIngredientsList')) {
    /**
     * Форматує інгредієнти («назва => грами») у рядок: «Борошно — 250 г, Молоко — 300 г».
     * $unit: 'g' (грами) або 'oz' (унції).
     */
    function formatIngredientsList(array $ingredients, string $unit = 'g'): string
    {
        if ($ingredients === []) {
            return '—';
        }

        $parts = [];
        foreach ($ingredients as $name => $grams) {
            $amount  = $unit === 'oz'
                ? convertGramsToOz((float) $grams) . ' унц.'
                : $grams . ' г';
            $parts[] = "{$name} — {$amount}";
        }
        return implode(', ', $parts);
    }
}

if (!function_exists('formatCookTime')) {
    /** 90 → «1 год 30 хв», 45 → «45 хв», 120 → «2 год». */
    function formatCookTime(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($h === 0) {
            return "{$minutes} хв";
        }
        return $m === 0 ? "{$h} год" : "{$h} год {$m} хв";
    }
}
