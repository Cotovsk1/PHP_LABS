<?php
declare(strict_types=1);

require_once __DIR__ . '/Recipe.php';

class VeganRecipe extends Recipe
{
    /** @var array<string, string> */
    private array $substitutions;
    private string $rawSubstitutions;

    /**
     * @param array<string, float> $ingredients
     * @param array<string, string> $substitutions
     */
    public function __construct(
        string $title,
        array $ingredients,
        int $cookTimeMin,
        int $servings = 2,
        array $substitutions = [],
        string $difficulty = 'Легкий',
        ?int $id = null,
        string $rawIngredients = '',
        string $rawSubstitutions = ''
    ) {
        parent::__construct($title, $ingredients, $cookTimeMin, $servings, $difficulty, $id, $rawIngredients);
        $this->substitutions = $substitutions;
        $this->rawSubstitutions = $rawSubstitutions;
    }

    /** @return array<string, string> */
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
        if ($this->rawSubstitutions !== '') {
            return $info . '; заміни: ' . $this->rawSubstitutions;
        }
        if ($this->substitutions === []) {
            return $info . ', без замін';
        }

        $pairs = [];
        foreach ($this->substitutions as $from => $to) {
            $pairs[] = "{$from} → {$to}";
        }
        return $info . '; заміни: ' . implode(', ', $pairs);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        $data['is_vegan'] = 1;
        $data['type_label'] = $this->getType();
        $data['substitutions'] = $this->rawSubstitutions;
        $data['info'] = $this->getInfo();
        return $data;
    }
}
