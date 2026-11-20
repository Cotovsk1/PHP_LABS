<?php
declare(strict_types=1);

/**
 * Практична робота №8: Модуль безпеки (сесії, CSRF, XSS-екранування, контроль доступу).
 */
class Security
{
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * Генерує або повертає поточний криптографічно стійкий CSRF-токен сесії.
     */
    public static function getCsrfToken(): string
    {
        self::initSession();
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Повертає приховане HTML-поле форми з CSRF-токеном.
     */
    public static function csrfInput(): string
    {
        $token = self::e(self::getCsrfToken());
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Перевіряє CSRF-токен з POST-поля або HTTP-заголовка X-CSRF-Token (для AJAX/fetch).
     */
    public static function verifyCsrfToken(?string $providedToken = null): bool
    {
        self::initSession();
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if ($sessionToken === '') {
            return false;
        }

        $candidate = $providedToken
            ?? ($_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        return hash_equals($sessionToken, $candidate);
    }

    /**
     * Безпечне виведення рядків у HTML (захист від XSS — Крок 2 ЛР №8).
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Екранує спецсимволи SQL LIKE (% та _), щоб користувач не ламав шаблон пошуку.
     */
    public static function escapeLikeWildcards(string $term): string
    {
        return addcslashes($term, '%_\\');
    }

    public static function isLoggedIn(): bool
    {
        self::initSession();
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    /**
     * @return array{id: int, username: string, email: string, role: string}|null
     */
    public static function currentUser(): ?array
    {
        self::initSession();
        return self::isLoggedIn() ? $_SESSION['user'] : null;
    }

    public static function loginUser(array $userRow): void
    {
        self::initSession();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $userRow['id'],
            'username' => (string) $userRow['username'],
            'email'    => (string) $userRow['email'],
            'role'     => (string) ($userRow['role'] ?? 'user'),
        ];
        // Оновлюємо CSRF-токен при зміні привілеїв сесії
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function logoutUser(): void
    {
        self::initSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
    }
}
