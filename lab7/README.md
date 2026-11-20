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
