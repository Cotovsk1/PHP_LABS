# API Documentation — Практична робота №6

**Варіант №6:** Кулінарна книга (рецепти, інгредієнти)
**Єдина точка входу:** `api.php`
**Формат відповідей:** `application/json`

---

## Загальна структура відповіді

### Успішна відповідь:
```json
{
  "success": true,
  "data": { ... }
}
```

### Помилка:
```json
{
  "success": false,
  "error": "Опис помилки"
}
```

---

## Перелік підтримуваних ендпоінтів

| HTTP Метод | Параметри URL | Тіло запиту (JSON) | Код статусу | Опис |
|------------|---------------|-------------------|-------------|------|
| `GET` | `resource=recipes` | — | `200 OK` | Отримати список усіх рецептів |
| `GET` | `resource=recipes&id={id}` | — | `200 OK` / `404 Not Found` | Отримати один рецепт за ID |
| `GET` | `resource=recipes&action=shortest` | — | `200 OK` / `404 Not Found` | **Доменна дія:** найшвидший рецепт |
| `GET` | `resource=recipes&action=search&q={text}` | — | `200 OK` | **Доменна дія:** пошук рецептів за інгредієнтом |
| `POST` | `resource=recipes` | `{"title": "...", "ingredients": "...", "cook_time_min": 20}` | `201 Created` / `400 Bad Request` | Створити новий рецепт |
| `POST` | `resource=recipes&action=convert_units` | `{"ingredients_text": "...", "target": "oz"}` | `200 OK` / `400 Bad Request` | **Доменна дія:** перерахунок мір інгредієнтів (грами ↔ унції) |

---

## Приклади тестування через `curl`

### 1. Отримати всі рецепти (GET)
```bash
curl -i -X GET "http://localhost:8000/api.php?resource=recipes"
```

### 2. Отримати один рецепт за ID (GET)
```bash
curl -i -X GET "http://localhost:8000/api.php?resource=recipes&id=1"
```

### 3. Знайти найшвидший рецепт (Доменна дія GET)
```bash
curl -i -X GET "http://localhost:8000/api.php?resource=recipes&action=shortest"
```

### 4. Пошук за інгредієнтом (Доменна дія GET)
```bash
curl -i -X GET "http://localhost:8000/api.php?resource=recipes&action=search&q=буряк"
```

### 5. Створити новий рецепт (POST)
```bash
curl -i -X POST "http://localhost:8000/api.php?resource=recipes" \
  -H "Content-Type: application/json" \
  -d '{"title": "Омлет з сиром", "ingredients": "Яйця, сир, масло, сіль", "cook_time_min": 10}'
```

### 6. Перерахувати міри інгредієнтів в унції (Доменна дія POST)
```bash
curl -i -X POST "http://localhost:8000/api.php?resource=recipes&action=convert_units" \
  -H "Content-Type: application/json" \
  -d '{"ingredients_text": "Борошно 200г, цукор 100г", "target": "oz"}'
```
