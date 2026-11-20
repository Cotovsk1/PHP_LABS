-- Lab 7 var 6

CREATE DATABASE IF NOT EXISTS practicum7
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practicum7;

DROP TABLE IF EXISTS recipes;

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    ingredients TEXT NOT NULL,
    cook_time_min INT NOT NULL,
    servings INT NOT NULL DEFAULT 2,
    difficulty VARCHAR(50) NOT NULL DEFAULT 'Середній',
    is_vegan TINYINT(1) NOT NULL DEFAULT 0,
    substitutions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_recipes_cook_time ON recipes (cook_time_min);

INSERT INTO recipes (title, ingredients, cook_time_min, servings, difficulty, is_vegan, substitutions) VALUES
('Український червоний борщ', 'Яловичина: 400, Буряк: 250, Капуста: 200, Картопля: 300, Морква: 100, Сметана: 60', 90, 6, 'Складний', 0, NULL),
('Картопляні деруни з грибами', 'Картопля: 600, Печериці: 200, Цибуля: 80, Борошно: 60, Яйце: 50, Сметана: 80', 35, 4, 'Середній', 0, NULL),
('Салат Цезар з хрусткою куркою', 'Куряче філе: 250, Салат Ромен: 150, Сир Пармезан: 40, Сухарики: 50, Соус Цезар: 60', 20, 2, 'Легкий', 0, NULL),
('Веганські панкейки з ягодами', 'Борошно: 200, Рослинне молоко: 300, Банан: 120, Кленовий сироп: 40, Ягоди: 80', 15, 3, 'Легкий', 1, 'Молоко -> рослинне молоко; Яйце -> пюре з банана'),
('Класична паста Карбонара', 'Спагеті: 200, Бекон: 120, Яєчні жовтки: 60, Сир Пекоріно: 50, Чорний перець: 5', 25, 2, 'Середній', 0, NULL),
('Тости з авокадо та тофу', 'Цільнозерновий хліб: 80, Авокадо: 150, Тофу: 90, Лимонний сік: 10, Перець чилі: 2', 10, 1, 'Легкий', 1, 'Яйце пашот -> обсмажений тофу'),
('Ніжні сирники з ваніллю', 'Кисломолочний сир: 400, Яйце: 50, Борошно: 60, Цукор: 40, Ванільний екстракт: 5', 30, 3, 'Легкий', 0, NULL),
('Крем-суп із гарбуза та імбиру', 'Гарбуз: 500, Картопля: 150, Морква: 100, Кокосове молоко: 150, Імбир: 15', 35, 4, 'Легкий', 1, 'Вершки -> кокосове молоко');
