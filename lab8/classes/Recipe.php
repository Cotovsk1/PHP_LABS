<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/functions.php';

class Recipe
{
    private ?int $id;
    private string $title;
    /** @var array<string, float> */
    protected array $ingredients;
    private string $rawIngredients;
    private string $steps;
    private int $cookTimeMin;
    private int $servings;
    private string $difficulty;

    /**
     * @param array<string, float> $ingredients
     */
    public function __construct(
        string $title,
        array $ingredients,
        int $cookTimeMin,
        int $servings = 2,
        string $difficulty = 'Середній',
        ?int $id = null,
        string $rawIngredients = '',
        string $steps = ''
    ) {
        $this->id             = $id;
        $this->title          = $title;
        $this->ingredients    = $ingredients;
        $this->rawIngredients = $rawIngredients !== '' ? $rawIngredients : implode(', ', array_keys($ingredients));
        $this->steps          = $steps;
        $this->cookTimeMin    = $cookTimeMin;
        $this->servings       = $servings;
        $this->difficulty     = $difficulty;
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    /** @return array<string, float> */
    public function getIngredients(): array { return $this->ingredients; }
    public function getRawIngredients(): string { return $this->rawIngredients; }
    public function getSteps(): string { return $this->steps; }
    public function getCookTimeMin(): int { return $this->cookTimeMin; }
    public function getServings(): int { return $this->servings; }
    public function getDifficulty(): string { return $this->difficulty; }
    public function getType(): string { return 'Звичайний'; }

    public function getInfo(): string
    {
        return sprintf(
            '%s — %d порц., %s (%s), інгредієнтів: %d',
            $this->title,
            $this->servings,
            formatCookTime($this->cookTimeMin),
            $this->difficulty,
            count($this->ingredients)
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'ingredients'     => $this->rawIngredients,
            'steps'           => $this->steps,
            'ingredients_map' => $this->ingredients,
            'formatted_g'     => formatIngredientsList($this->ingredients, 'g'),
            'formatted_oz'    => formatIngredientsList($this->ingredients, 'oz'),
            'cook_time_min'   => $this->cookTimeMin,
            'cook_time_human' => formatCookTime($this->cookTimeMin),
            'servings'        => $this->servings,
            'difficulty'      => $this->difficulty,
            'is_vegan'        => 0,
            'type_label'      => $this->getType(),
            'substitutions'   => null,
            'info'            => $this->getInfo(),
            'badge'           => getRecipeBadge($this->cookTimeMin),
        ];
    }
}
