<?php
declare(strict_types=1);

require_once __DIR__ . '/Recipe.php';

class VeganRecipe extends Recipe
{
    private string $rawSubstitutions;

    /**
     * @param array<string, float> $ingredients
     */
    public function __construct(
        string $title,
        array $ingredients,
        int $cookTimeMin,
        int $servings = 2,
        string $difficulty = 'Легкий',
        ?int $id = null,
        string $rawIngredients = '',
        string $steps = '',
        string $rawSubstitutions = ''
    ) {
        parent::__construct($title, $ingredients, $cookTimeMin, $servings, $difficulty, $id, $rawIngredients, $steps);
        $this->rawSubstitutions = $rawSubstitutions;
    }

    public function getType(): string { return 'Веганський'; }

    public function getInfo(): string
    {
        $info = parent::getInfo() . ' | веганський';
        return $this->rawSubstitutions !== '' ? $info . '; заміни: ' . $this->rawSubstitutions : $info;
    }

    /** @return array<string, mixed> */
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
