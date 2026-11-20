<?php
declare(strict_types=1);

if (!function_exists('convertGramsToOz')) {
    function convertGramsToOz(float $grams): float
    {
        return round($grams / 28.3495, 1);
    }
}

if (!function_exists('parseIngredientsString')) {
    /**
     * Перетворює рядок інгредієнтів («Борошно: 200, Молоко: 300» або «Картопля, цибуля»)
     * в асоціативний масив ['Назва' => грамів].
     *
     * @return array<string, float>
     */
    function parseIngredientsString(string $raw): array
    {
        $items = preg_split('/[\r\n,]+/', $raw) ?: [];
        $parsed = [];

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }

            if (preg_match('/^(.+?)\s*[:\-–]\s*(\d+(?:[.,]\d+)?)\s*(?:г|g)?$/iu', $item, $m)) {
                $name = trim($m[1]);
                $grams = (float) str_replace(',', '.', $m[2]);
                $parsed[$name] = $grams;
            } else {
                $parsed[$item] = 0.0;
            }
        }

        return $parsed;
    }
}

if (!function_exists('formatIngredientsList')) {
    /**
     * Форматує масив інгредієнтів з урахуванням обраної одиниці виміру ('g' або 'oz').
     *
     * @param array<string, float> $ingredients
     */
    function formatIngredientsList(array $ingredients, string $unit = 'g'): string
    {
        if ($ingredients === []) {
            return '—';
        }

        $parts = [];
        foreach ($ingredients as $name => $grams) {
            if ($grams > 0) {
                $amount = $unit === 'oz'
                    ? convertGramsToOz((float) $grams) . ' унц.'
                    : rtrim(rtrim(number_format((float) $grams, 1, '.', ''), '0'), '.') . ' г';
                $parts[] = "{$name} — {$amount}";
            } else {
                $parts[] = (string) $name;
            }
        }

        return implode(', ', $parts);
    }
}

if (!function_exists('formatCookTime')) {
    /** Форматує хвилини: 90 → «1 год 30 хв», 20 → «20 хв». */
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

if (!function_exists('getRecipeBadge')) {
    /**
     * Повертає мітку тривалості приготування з ЛР №1 (умова <= 20 хв — «Швидкий рецепт»).
     *
     * @return array{label: string, class: string, isQuick: bool}
     */
    function getRecipeBadge(int $cookTimeMin): array
    {
        if ($cookTimeMin <= 20) {
            return [
                'label'   => '⚡ Швидкий рецепт',
                'class'   => 'badge-quick',
                'isQuick' => true,
            ];
        }
        if ($cookTimeMin <= 45) {
            return [
                'label'   => '⏱ Помірний час',
                'class'   => 'badge-medium',
                'isQuick' => false,
            ];
        }
        return [
            'label'   => '⏳ Тривале готування',
            'class'   => 'badge-slow',
            'isQuick' => false,
        ];
    }
}
