# Практична робота №4: Взаємодія вебзастосунку з базою даних (PDO/MySQL)

**Дисципліна:** Сучасні технології розробки WEB-застосувань мовою програмування PHP  
**Курс / Факультет:** 3 курс, ФІОТ КПІ ім. Ігоря Сікорського  
**Варіант №6:** Кулінарна книга (рецепти, інгредієнти)

---

## 🎯 Мета роботи
- Опанувати взаємодію вебзастосунку з СУБД MySQL/MariaDB засобами розширення **PDO (PHP Data Objects)**.
- Створити базу даних та таблицю `recipes` відповідно до вимог варіанта №6.
- Реалізувати повний цикл базових CRUD-операцій:
  - **C**reate (`INSERT`)
  - **R**ead (`SELECT`)
  - **U**pdate (`UPDATE`)
  - **D**elete (`DELETE`)
- Застосувати підготовлені запити (**prepared statements**) з іменованими плейсхолдерами для надійного захисту від SQL-ін'єкцій.
- Реалізувати спеціальні предметні вибірки варіанта:
  - `findByIngredient($text)` — пошук рецептів за інгредієнтом із шаблоном `LIKE :pattern`.
  - `shortestCookTime()` — визначення рецепта з мінімальним часом приготування.

---

## 📁 Структура проєкту (`lab4/`)

* [`schema.sql`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/schema.sql) — DDL-скрипт створення бази даних `practicum4`, структури таблиці `recipes` та початкового набору тестових даних.
* [`db.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/db.php) — підключення до MySQL через PDO (налаштування DSN, `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, захист від емуляції підготовлених запитів).
* [`functions.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/functions.php) — репозиторій функцій предметної області варіанта №6 (`getAllRecipes`, `getRecipeById`, `findByIngredient`, `shortestCookTime`, `addRecipe`, `updateRecipe`, `deleteRecipe`).
* [`index.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/index.php) — головна сторінка: виведення таблиці рецептів, картка найшвидшого рецепта, форма пошуку за інгредієнтом, сповіщення про успіх операцій та блок демонстрації стійкості до SQL-ін'єкцій.
* [`add.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/add.php) — форма та обробник додавання нового рецепта (`INSERT`).
* [`edit.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/edit.php) — форма редагування існуючого запису (`UPDATE`).
* [`delete.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/delete.php) — обробник видалення запису за ідентифікатором (`DELETE`).
* [`setup.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/setup.php) — вебмайстер автоматичної ініціалізації та скидання бази даних в один клік.
* [`style.css`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/style.css) — стилі інтерфейсу (адаптивність, сучасна кольорова гама з бейджами з попередніх практикумів).
* [`run_server.bat`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/run_server.bat) — пакетний файл для швидкого запуску вбудованого вебсервера PHP (`php -S localhost:8000`).

---

## 🛠 Хід виконання роботи

### Крок 1. Створення бази даних та таблиці (`schema.sql`)
Створено базу даних `practicum4` та таблицю `recipes` згідно зі специфікацією варіанта №6:

```sql
CREATE DATABASE IF NOT EXISTS `practicum4`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `practicum4`;

CREATE TABLE IF NOT EXISTS `recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `ingredients` TEXT NOT NULL,
    `cook_time_min` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Також додано початкові тестові дані для перевірки всіх типів рецептів та роботи фільтрів.

### Крок 2. Налаштування з'єднання з базою даних (`db.php`)
Створено об'єкт PDO з обов'язковими параметрами:
```php
$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $options);
```

### Крок 3. Виведення списку рецептів (SELECT)
У файлі `index.php` реалізовано отримання всіх записів та показ у вигляді HTML-таблиці з екрануванням через `htmlspecialchars()`:
```php
$stmt = $pdo->query('SELECT * FROM recipes ORDER BY id DESC');
$recipes = $stmt->fetchAll();
```
Додатково реалізовано вибірки варіанта №6:
* **Пошук за інгредієнтом:**
  ```php
  function findByIngredient(PDO $pdo, string $text): array {
      $stmt = $pdo->prepare('SELECT * FROM recipes WHERE ingredients LIKE :pattern ORDER BY id DESC');
      $stmt->execute([':pattern' => '%' . $text . '%']);
      return $stmt->fetchAll();
  }
  ```
* **Найшвидший рецепт:**
  ```php
  function shortestCookTime(PDO $pdo): ?array {
      $stmt = $pdo->query('SELECT * FROM recipes ORDER BY cook_time_min ASC LIMIT 1');
      return $stmt->fetch() ?: null;
  }
  ```

### Крок 4. Додавання рецепта (INSERT)
У файлі `add.php` розміщено HTML-форму. Після відправлення виконується валідація та вставка через підготовлений запит:
```php
$stmt = $pdo->prepare('INSERT INTO recipes (title, ingredients, cook_time_min) VALUES (:title, :ingredients, :cook_time_min)');
$stmt->execute([
    ':title'         => $title,
    ':ingredients'   => $ingredients,
    ':cook_time_min' => $cookTimeMin,
]);
header('Location: index.php?msg=added');
exit;
```

### Крок 5. Редагування рецепта (UPDATE)
У файлі `edit.php` завантажуються поточні дані (`SELECT ... WHERE id = :id`), заповнюються у поля форми, а після відправлення оновлюються:
```php
$stmt = $pdo->prepare('UPDATE recipes SET title = :title, ingredients = :ingredients, cook_time_min = :cook_time_min WHERE id = :id');
$stmt->execute([
    ':id'            => $id,
    ':title'         => $title,
    ':ingredients'   => $ingredients,
    ':cook_time_min' => $cookTimeMin,
]);
header('Location: index.php?msg=updated');
exit;
```

### Крок 6. Видалення рецепта (DELETE)
У файлі `delete.php` виконується запит:
```php
$stmt = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
$stmt->execute([':id' => $id]);
header('Location: index.php?msg=deleted');
exit;
```
Перед видаленням користувач підтверджує дію через JavaScript `confirm()`.

### Крок 7. Захист від SQL-ін'єкцій
Завдяки розділенню коду запиту та параметрів через `prepare()` та `execute()` введення рядків кшталту:
```
' OR '1'='1
O'Connor's Cake'); DROP TABLE recipes; --
```
не змінює синтаксичного дерева SQL-запиту, а зберігається та шукається виключно як звичайний текст.

---

## 🚀 Інструкція із запуску проєкту

1. Переконайтеся, що сервер MySQL запущений (наприклад, у XAMPP / MariaDB / Docker) на стандартному порті `3306`.
2. Запустіть файл [`run_server.bat`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab4/run_server.bat) або виконайте в терміналі:
   ```bash
   cd lab4
   php -S localhost:8000
   ```
3. Відкрийте браузер за адресою [http://localhost:8000/index.php](http://localhost:8000/index.php).
4. Якщо базу даних ще не створено, перейдіть до [http://localhost:8000/setup.php](http://localhost:8000/setup.php) та натисніть кнопку для автоматичної ініціалізації БД.
