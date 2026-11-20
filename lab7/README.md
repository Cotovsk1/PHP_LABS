lab7/
├── classes/
│   ├── Cookbook.php       # Менеджер колекції рецептів та агрегатів (ЛР №1, 3)
│   ├── Recipe.php         # Базова сутність рецепта з мапінгом із БД (ЛР №3)
│   └── VeganRecipe.php    # Похідний клас веганського рецепта (ЛР №3)
├── lib/
│   ├── Cache.php          # Файловий кеш із TTL та інвалідацією (ЛР №7)
│   └── functions.php      # Конвертація г/унц, форматування часу та міток (ЛР №1, 2, 3)
├── api.php                # Єдина точка входу REST API + профілювання + N+1/Optimized (ЛР №5, 6, 7)
├── db.php                 # Підключення PDO + обгортка TrackedPDO (лічильник SQL-запитів) (ЛР №4, 7)
├── index.php              # Головний інтерфейс застосунку (ЛР №1–7 разом)
├── schema.sql             # Схема БД practicum7 + індекс idx_recipes_cook_time (ЛР №4, 7)
├── script.js              # AJAX-фронтенд, live search, localStorage, панель профайлера (ЛР №2, 5, 7)
├── setup.php              # Майстер БД: ініціалізація, керування індексом, генератор 200 записів для ab
├── style.css              # Адаптивні стилі інтерфейсу
├── run_server.bat         # Запуск локального сервера PHP
├── API.md                 # Контракт та приклади запитів API (ЛР №6, 7)
└── README.md              # Звіт та інструкція для EXPLAIN і Apache Bench (ab) (ЛР №7)

# Практична робота №7: Оптимізація серверних скриптів та навантажувальне тестування

**Варіант №6:** Кулінарна книга (рецепти, інгредієнти)

## Реалізовані кроки та оптимізації

1. **Профілювання в коді (`api.php`, `db.php`):** вимірювання часу через `microtime(true)`, пікової пам'яті через `memory_get_peak_usage(true)` та підрахунок усіх SQL-запитів через обгортку `TrackedPDO`[cite: 3, 5].
2. **Індекс БД (`schema.sql`, `setup.php`):** створено `CREATE INDEX idx_recipes_cook_time ON recipes (cook_time_min)`[cite: 4, 7].
   - `EXPLAIN SELECT * FROM recipes WHERE cook_time_min <= 20` **до** індексу: `type = ALL`, перегляд усіх рядків таблиці[cite: 4].
   - `EXPLAIN SELECT * FROM recipes WHERE cook_time_min <= 20` **після** індексу: `type = range`, `key = idx_recipes_cook_time`[cite: 4].
3. **Усунення проблеми $N+1$ (`api.php`):**
   - У режимі `?mode=unoptimized` для кожного рецепта виконується окремий `SELECT COUNT(*) FROM recipes WHERE ingredients LIKE ...` у циклі ($N + 2$ запити)[cite: 4, 7].
   - У режимі `?mode=optimized` виконується лише **1 вибірковий запит**, а кількість рецептів зі спільним основним інгредієнтом обчислюється в пам'яті PHP за один прохід $O(N)$[cite: 5].
4. **Файловий кеш із TTL (`lib/Cache.php`):** результати списку, агрегатів та `shortestCookTime()` кешуються на 45 секунд у тимчасовій директорії сервера (`sys_get_temp_dir()`) і автоматично інвалідуються при `INSERT`, `UPDATE` або `DELETE`[cite: 4, 5, 7].

## Команди для навантажувального тестування Apache Bench (`ab`)

1. Відкрийте `http://localhost:8000/setup.php`, ініціалізуйте БД та натисніть **«Додати +100 записів для тесту ab»**.
2. Замір **«До оптимізації» ($N+1$, без кешу)**:
   ```bash
   ab -n 200 -c 20 "http://localhost:8000/api.php?resource=recipes&mode=unoptimized"
