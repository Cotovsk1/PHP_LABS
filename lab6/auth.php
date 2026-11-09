<?php
declare(strict_types=1);

// Ініціалізація сесії з налаштуваннями безпеки
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true, // Захист від XSS доступу до cookies
        'cookie_secure' => false,  // Встановити true якщо використовується HTTPS
        'use_strict_mode' => true,
    ]);
}

/**
 * Перевірка чи користувач авторизований
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Отримання даних поточного користувача
 */
function getCurrentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Вимагати авторизацію для доступу до сторінки
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php?msg=auth_required');
        exit;
    }
}

/**
 * Перевірка прав на редагування/видалення (власник або адмін)
 */
function canManageRecipe(int $recipeUserId): bool {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return ($user['id'] === $recipeUserId || $user['role'] === 'admin');
}

/**
 * Безпечне виведення (захист від XSS)
 */
function e(string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
