<?php
declare(strict_types=1);

require_once __DIR__ . '/Recipe.php';

/**
 * Похідний клас: веганський рецепт.
 * Додає масив замін «звичайний продукт => веганська альтернатива».
 */
class VeganRecipe extends Recipe
{
    private array $substitutions;

    public function __construct(
        string $title,
        array $ingredients,
        int $cookTimeMin,
        int $servings = 2,
        array $substitutions = []
    ) {
        parent::__construct($title, $ingredients, $cookTimeMin, $servings);
        $this->substitutions = $substitutions;
    }

    public function getSubstitutions(): array
    {
        return $this->substitutions;
    }

    public function getType(): string
    {
        return 'Веганський';
    }

    public function getInfo(): string
    {
        $info = parent::getInfo() . ' | 🌱 веганський';

        if ($this->substitutions === []) {
            return $info . ', без замін';
        }

        $pairs = [];
        foreach ($this->substitutions as $from => $to) {
            $pairs[] = "{$from} → {$to}";
        }

        return $info . '; заміни: ' . implode(', ', $pairs);
    }
}
