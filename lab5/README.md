# Практична робота №5: Взаємодія з сервером через AJAX (REST/JSON API)

**Дисципліна:** Сучасні технології розробки WEB-застосувань мовою програмування PHP
**Варіант №6:** Кулінарна книга (рецепти, інгредієнти)

---

## 🎯 Мета роботи
- Опанувати технологію **AJAX** (Asynchronous JavaScript and XML) для обміну даними між клієнтом та сервером без перезавантаження сторінки.
- Створити REST-подібний бекенд на PHP, що обробляє запити та повертає відповіді у форматі **JSON**.
- Реалізувати клієнтську частину на JavaScript з використанням сучасного **Fetch API**.
- Реалізувати "живий пошук" (live search) та миттєве оновлення інтерфейсу (Dynamic DOM Manipulation).

---

## 📁 Структура проєкту (`lab5/`)

* [`index.html`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/index.html) — Головна сторінка з формою та контейнером для динамічного виведення результатів.
* [`script.js`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/script.js) — Клієнтська логіка: AJAX-запити до серверних API, обробка відповідей, динамічна зміна DOM.
* [`db.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/db.php) — Підключення до БД `practicum5` через PDO.
* [`api_list.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/api_list.php) — API-ендпоінт для отримання списку рецептів (з підтримкою фільтрації за параметром `q`).
* [`api_add.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/api_add.php) — API-ендпоінт для додавання нового рецепта (приймає JSON через `php://input`).
* [`api_delete.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/api_delete.php) — API-ендпоінт для видалення рецепта.
* [`setup.php`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/setup.php) — Веб-майстер для швидкої ініціалізації бази даних та таблиць.
* [`schema.sql`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/schema.sql) — SQL-скрипт структури БД.
* [`style.css`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/style.css) — Сучасні стилі інтерфейсу.
* [`run.bat`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/run.bat) — Скрипт запуску локального сервера.

---

## 🛠 Реалізований функціонал

1.  **Живий пошук (Live Search):**
    При введенні символів у поле пошуку JavaScript відправляє асинхронний `GET` запит до `api_list.php?q=...`. Сервер виконує SQL `LIKE` запит та повертає JSON. Сторінка оновлюється миттєво.
2.  **Додавання без перезавантаження:**
    Форма додавання перехоплюється через `event.preventDefault()`. Дані збираються в об'єкт, серіалізуються в JSON та відправляються `POST` запитом до `api_add.php`. Після успіху список рецептів оновлюється автоматично.
3.  **Видалення через AJAX:**
    Кнопки видалення мають атрибут `data-id`. При натисканні відправляється запит до `api_delete.php`. Рядок таблиці зникає без перезавантаження всієї сторінки.
4.  **REST API Backend:**
    Використано `header('Content-Type: application/json')` для всіх API файлів. Обробка JSON-вводу через `file_get_contents('php://input')`.

---

## 🚀 Інструкція із запуску

1. Переконайтеся, що MySQL сервер запущений.
2. Запустіть [`run.bat`](file:///C:/Users/Andrii/Desktop/liricka/PHP/lab5/run.bat) (або `php -S localhost:8000`).
3. Відкрийте [http://localhost:8000/setup.php](http://localhost:8000/setup.php) для створення бази даних.
4. Відкрийте [http://localhost:8000/index.html](http://localhost:8000/index.html).
