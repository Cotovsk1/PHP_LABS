CREATE DATABASE IF NOT EXISTS practicum8
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE practicum8;

DROP TABLE IF EXISTS recipes;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(255) NOT NULL,
    ingredients TEXT NOT NULL,
    steps TEXT NOT NULL,
    cook_time_min INT NOT NULL,
    servings INT NOT NULL DEFAULT 2,
    difficulty VARCHAR(50) NOT NULL DEFAULT 'Середній',
    is_vegan TINYINT(1) NOT NULL DEFAULT 0,
    substitutions TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_recipes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_recipes_cook_time ON recipes (cook_time_min);

-- Демо-користувач (логін: chef, пароль: Password123!)
INSERT INTO users (username, email, password_hash, role) VALUES
('chef', 'chef@cookbook.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO recipes (user_id, title, ingredients, steps, cook_time_min, servings, difficulty, is_vegan, substitutions) VALUES
(1, 'Український червоний борщ', 'Яловичина: 400, Буряк: 250, Капуста: 200, Картопля: 300, Морква: 100, Сметана: 60', '1. Зваріть м’ясний бульйон (45 хв). 2. Пасеруйте буряк з морквою. 3. Додайте картоплю, капусту та засмажку, варіть ще 25 хв.', 90, 6, 'Складний', 0, NULL),
(1, 'Картопляні деруни з грибами', 'Картопля: 600, Печериці: 200, Цибуля: 80, Борошно: 60, Яйце: 50, Сметана: 80', '1. Натріть картоплю та цибулю на дрібній тертці. 2. Додайте яйце і борошно, обсмажте до золотистої скоринки. 3. Подавайте з тушкованими грибами.', 35, 4, 'Середній', 0, NULL),
(1, 'Салат Цезар з хрусткою куркою', 'Куряче філе: 250, Салат Ромен: 150, Сир Пармезан: 40, Сухарики: 50, Соус Цезар: 60', '1. Обсмажте куряче філе скибочками. 2. Порвіть листя Ромену, додайте соус, курку, сухарики та тертий пармезан.', 20, 2, 'Легкий', 0, NULL),
(1, 'Веганські панкейки з ягодами', 'Борошно: 200, Рослинне молоко: 300, Банан: 120, Кленовий сироп: 40, Ягоди: 80', '1. Розімніть банан виделкою, змішайте з рослинним молоком та борошном. 2. Випікайте на сухій сковороді по 2 хв з кожного боку.', 15, 3, 'Легкий', 1, 'Молоко -> рослинне молоко; Яйце -> пюре з банана'),
(1, 'Тости з авокадо та тофу', 'Цільнозерновий хліб: 80, Авокадо: 150, Тофу: 90, Лимонний сік: 10, Перець чилі: 2', '1. Підсушіть хліб у тостері. 2. Розімніть авокадо з лимонним соком, викладіть зверху скибочки підсмаженого тофу.', 10, 1, 'Легкий', 1, 'Яйце пашот -> обсмажений тофу');
