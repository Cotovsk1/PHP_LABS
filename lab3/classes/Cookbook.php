<?php
declare(strict_types=1);

require_once __DIR__ . '/Recipe.php';

/**
 * Клас-менеджер: колекція рецептів (базових і похідних).
 */
class Cookbook
{
    /** @var Recipe[] */
    private array $recipes = [];

    public function addRecipe(Recipe $recipe): void
    {
        $this->recipes[] = $recipe;
    }

    /** @return Recipe[] */
    public function getRecipes(): array
    {
        return $this->recipes;
    }

    public function count(): int
    {
        return count($this->recipes);
    }

    /**
     * Пошук рецептів, де є інгредієнт зі збігом підрядка (без урахування регістру).
     *
     * @return Recipe[]
     */
    public function findByIngredient(string $needle): array
    {
        $needle = trim($needle);
        if ($needle === '') {
            return $this->recipes;
        }

        $result = [];
        foreach ($this->recipes as $recipe) {
            foreach (array_keys($recipe->getIngredients()) as $name) {
                if (mb_stripos((string) $name, $needle) !== false) {
                    $result[] = $recipe;
                    break;
                }
            }
        }
        return $result;
    }

    /** Рецепт із найменшим часом приготування (null, якщо книга порожня). */
    public function shortestCookTime(): ?Recipe
    {
        $shortest = null;
        foreach ($this->recipes as $recipe) {
            if ($shortest === null || $recipe->getCookTimeMin() < $shortest->getCookTimeMin()) {
                $shortest = $recipe;
            }
        }
        return $shortest;
    }
}
