<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

/**
 * Базовий клас: звичайний рецепт.
 *
 * Інкапсуляція: поля private/protected, доступ — через геттери.
 * $ingredients — асоціативний масив «назва => вага в грамах».
 */
class Recipe
{
    private string $title;
    protected array $ingredients;   // protected: потрібен нащадкам
    private int $cookTimeMin;
    private int $servings;

    public function __construct(string $title, array $ingredients, int $cookTimeMin, int $servings = 2)
    {
        $this->title       = $title;
        $this->ingredients = $ingredients;
        $this->cookTimeMin = $cookTimeMin;
        $this->servings    = $servings;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    public function getCookTimeMin(): int
    {
        return $this->cookTimeMin;
    }

    public function getServings(): int
    {
        return $this->servings;
    }

    /** Людська назва типу рецепта (перевизначається в нащадках). */
    public function getType(): string
    {
        return 'Звичайний';
    }

    public function getInfo(): string
    {
        return sprintf(
            '%s — %d порц., %s, інгредієнтів: %d',
            $this->title,
            $this->servings,
            formatCookTime($this->cookTimeMin),
            count($this->ingredients)
        );
    }
}
