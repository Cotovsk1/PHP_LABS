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

    /** @param array<string, mixed> $row */
    public static function createFromDbRow(array $row): Recipe
    {
        $id             = isset($row['id']) ? (int) $row['id'] : null;
        $title          = (string) ($row['title'] ?? '');
        $rawIngredients = (string) ($row['ingredients'] ?? '');
        $steps          = (string) ($row['steps'] ?? '');
        $ingredientsMap = parseIngredientsString($rawIngredients);
        $cookTimeMin    = (int) ($row['cook_time_min'] ?? 0);
        $servings       = (int) ($row['servings'] ?? 2);
        $difficulty     = (string) ($row['difficulty'] ?? 'Середній');
        $isVegan        = (int) ($row['is_vegan'] ?? 0) === 1;
        $rawSubs        = trim((string) ($row['substitutions'] ?? ''));

        if ($isVegan) {
            return new VeganRecipe($title, $ingredientsMap, $cookTimeMin, $servings, $difficulty, $id, $rawIngredients, $steps, $rawSubs);
        }

        return new Recipe($title, $ingredientsMap, $cookTimeMin, $servings, $difficulty, $id, $rawIngredients, $steps);
    }

    public function count(): int { return count($this->recipes); }

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
        if ($total === 0) return 0.0;
        $sum = 0;
        foreach ($this->recipes as $r) $sum += $r->getCookTimeMin();
        return round($sum / $total, 1);
    }

    public function getQuickRecipesCount(): int
    {
        $count = 0;
        foreach ($this->recipes as $r) {
            if ($r->getCookTimeMin() <= 20) $count++;
        }
        return $count;
    }
}
