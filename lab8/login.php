<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';
require_once __DIR__ . '/db.php';

Security::initSession();

if (Security::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken()) {
        http_response_code(403);
        $error = 'Недійсний CSRF-токен (403). Оновіть сторінку та спробуйте ще раз.';
    } else {
        $loginValue = trim((string) ($_POST['login'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($loginValue === '' || $password === '') {
            $error = 'Заповніть логін (або email) і пароль.';
        } else {
            try {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u OR email = :e LIMIT 1');
                $stmt->execute([':u' => $loginValue, ':e' => $loginValue]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, (string) $user['password_hash'])) {
                    Security::loginUser($user);
                    header('Location: index.php');
                    exit;
                }
                $error = 'Невірний логін або пароль.';
            } catch (PDOException $e) {
                error_log('[SecurityAudit] Login PDOException: ' . $e->getMessage());
                $error = 'Помилка авторизації. Спробуйте пізніше.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід — Кулінарна книга (ЛР №8)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 480px;">
        <header class="header">
            <div class="header-content">
                <span class="header-icon">🔐</span>
                <div>
                    <span class="header-badge">Практична №8 · Безпека</span>
                    <h1>Вхід у систему</h1>
                </div>
            </div>
        </header>

        <section class="card">
            <?php if ($error !== ''): ?>
                <div class="alert alert-error" style="margin-bottom: 16px;"><?= Security::e($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?= Security::csrfInput() ?>

                <div class="form-group">
                    <label for="login" class="form-label">Логін або Email</label>
                    <input type="text" id="login" name="login" class="form-control" value="<?= Security::e($loginValue) ?>" required placeholder="chef">
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="Password123!">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">Увійти</button>
            </form>

            <p style="margin-top: 16px; font-size: 0.85rem; color: var(--gray-600); text-align: center;">
                Демо-акаунт: <code>chef</code> / <code>Password123!</code><br>
                Немає акаунту? <a href="register.php">Зареєструватися</a> · <a href="index.php">На головну</a>
            </p>
        </section>
    </div>
</body>
</html>
