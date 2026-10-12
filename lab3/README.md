# Практична робота №3: Основи ООП у PHP — класи, повторне використання коду

**Варіант №6:** Кулінарна книга (рецепти, інгредієнти)

## Структура проєкту (`lab3/`)
- `classes/Recipe.php` — базовий клас: `title`, `ingredients`, `cookTimeMin`, `servings` (private/protected), конструктор, геттери, `getInfo()`.
- `classes/VeganRecipe.php` — `VeganRecipe extends Recipe`: додає `substitutions`, перевизначає `getInfo()` через `parent::getInfo()`.
- `classes/Cookbook.php` — менеджер: `addRecipe()`, `findByIngredient()`, `shortestCookTime()`.
- `lib/functions.php` — функції: `formatIngredientsList()`, `convertGramsToOz()`, `formatCookTime()`.
- `index.php` — підключення через `require_once`, створення об'єктів, таблиця результатів, пошук та перемикач г/унції.
- `style.css` — стилі з практикуму №1 + кілька нових.
- `run_server.bat` — запуск `php -S localhost:8000`.

## Хід виконання
1. **Структура:** створено `classes/` і `lib/`.
2. **Базовий клас `Recipe`:** властивості з модифікаторами доступу (`private` та `protected`), конструктор, `getInfo(): string`.
3. **Успадкування:** `VeganRecipe` викликає `parent::__construct()` та `parent::getInfo()` і доповнює результат замінами.
4. **Менеджер `Cookbook`:** масив `Recipe[]`, додавання, пошук за інгредієнтом (`mb_stripos`, без урахування регістру), пошук найшвидшого рецепта.
5. **Бібліотека функцій:** три функції, захищені `function_exists`, щоб уникнути повторного оголошення.
6. **`index.php`:** `require_once` усіх файлів, 3 об'єкти `Recipe` і 2 `VeganRecipe`, виведення в таблицю, вивід екрановано через `htmlspecialchars()`.
7. **Перевірка:** відкрити `http://localhost:8000/index.php`; у веганських рецептів у колонці `getInfo()` є додаткова інформація про заміни.
