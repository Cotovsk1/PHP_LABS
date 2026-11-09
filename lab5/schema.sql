CREATE DATABASE IF NOT EXISTS `practicum5` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `practicum5`;

CREATE TABLE IF NOT EXISTS `recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `ingredients` TEXT NOT NULL,
    `cook_time_min` INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `recipes` (`title`, `ingredients`, `cook_time_min`) VALUES
('Український червоний борщ', 'Яловичина, буряк, капуста, картопля, морква, цибуля, томатна паста', 90),
('Класичні деруни зі сметаною', 'Картопля, цибуля, яйце, борошно, сметана', 25),
('Швидкі тости з авокадо', 'Хліб, авокадо, яйце, лимон, перець', 15),
('Паста Карбонара', 'Спагеті, бекон, жовтки, сир пармезан, перець', 20),
('Ніжні сирники', 'Сир, яйце, борошно, цукор, ваніль', 30);