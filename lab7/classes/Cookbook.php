<?php
declare(strict_types=1);

require_once __DIR__ . '/Recipe.php';
require_once __DIR__ . '/VeganRecipe.php';
require_once __DIR__ . '/../lib/functions.php';

class Cookbook
{
    /** @var Recipe[] */
    private array $recipes = [];

    public function addRecipe(Recipe $recipe): void
    {
        $this->recipes[] = $recipe;
    }

    /**
     * Створює відповідний об'єкт (Recipe або VeganRecipe) з рядка БД.
     *
     * @param array<string, mixed> $row
     */
    public static function createFromDbRow(array $row): Recipe
    {
        $id = isset($row['id']) ? (int) $row['id'] : null;
        $title = (string) ($row['title'] ?? '');
        $rawIngredients = (string) ($row['ingredients'] ?? '');
        $ingredientsMap = parseIngredientsString($rawIngredients);
        $cookTimeMin = (int) ($row['cook_time_min'] ?? 0);
        $servings = (int) ($row['servings'] ?? 2);
        $difficulty = (string) ($row['difficulty'] ?? 'Середній');
        $isVegan = (int) ($row['is_vegan'] ?? 0) === 1;
        $rawSubs = trim((string) ($row['substitutions'] ?? ''));

        if ($isVegan) {
            return new VeganRecipe(
                $title,
                $ingredientsMap,
                $cookTimeMin,
                $servings,
                [],
                $difficulty,
                $id,
                $rawIngredients,
                $rawSubs
            );
        }

        return new Recipe(
            $title,
            $ingredientsMap,
            $cookTimeMin,
            $servings,
            $difficulty,
            $id,
            $rawIngredients
        );
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

    /** @return Recipe[] */
    public function findByIngredient(string $needle): array
    {
        $needle = trim($needle);
        if ($needle === '') {
            return $this->recipes;
        }

        $result = [];
        foreach ($this->recipes as $recipe) {
            if (mb_stripos($recipe->getRawIngredients(), $needle) !== false || mb_stripos($recipe->getTitle(), $needle) !== false) {
                $result[] = $recipe;
            }
        }
        return $result;
    }

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

    public function getAverageCookTime(): float
    {
        $total = count($this->recipes);
        if ($total === 0) {
            return 0.0;
        }
        $sum = 0;
        foreach ($this->recipes as $recipe) {
            $sum += $recipe->getCookTimeMin();
        }
        return round($sum / $total, 1);
    }

    public function getQuickRecipesCount(): int
    {
        $count = 0;
        foreach ($this->recipes as $recipe) {
            if ($recipe->getCookTimeMin() <= 20) {
                $count++;
            }
        }
        return $count;
    }
}
