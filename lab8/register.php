<?php
declare(strict_types=1);

require_once __DIR__ . '/lib/Security.php';
require_once __DIR__ . '/db.php';

Security::initSession();

if (Security::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCsrfToken()) {
        http_response_code(403);
        $errors[] = 'Недійсний CSRF-токен (403).';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Логін має містити від 3 до 30 латинських літер, цифр або _.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Вкажіть коректний Email.';
        }
        if (mb_strlen($password) < 8) {
            $errors[] = 'Пароль повинен містити щонайменше 8 символів.';
        }

        if ($errors === []) {
            try {
                $check = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1');
                $check->execute([':u' => $username, ':e' => $email]);
                if ($check->fetch()) {
                    $errors[] = 'Користувач із таким логіном або Email уже існує.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, :r)');
                    $ins->execute([':u' => $username, ':e' => $email, ':p' => $hash, ':r' => 'user']);
                    Security::loginUser([
                        'id'       => (int) $pdo->lastInsertId(),
                        'username' => $username,
                        'email'    => $email,
                        'role'     => 'user',
                    ]);
                    header('Location: index.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log('[SecurityAudit] Register PDOException: ' . $e->getMessage());
                $errors[] = 'Помилка реєстрації. Спробуйте пізніше.';
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
    <title>Реєстрація — Кулінарна книга (ЛР №8)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 480px;">
        <header class="header">
            <div class="header-content">
                <span class="header-icon">📝</span>
                <div>
                    <span class="header-badge">Практична №8 · Безпека</span>
                    <h1>Реєстрація</h1>
                </div>
            </div>
        </header>

        <section class="card">
            <?php if ($errors !== []): ?>
                <div class="alert alert-error" style="margin-bottom: 16px;">
                    <?php foreach ($errors as $err): ?>
                        <div>• <?= Security::e($err) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <?= Security::csrfInput() ?>

                <div class="form-group">
                    <label for="username" class="form-label">Логін (латиниця, цифри, _)</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= Security::e($username) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= Security::e($email) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Пароль (мін. 8 символів)</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">Створити акаунт</button>
            </form>

            <p style="margin-top: 16px; font-size: 0.85rem; color: var(--gray-600); text-align: center;">
                Вже зареєстровані? <a href="login.php">Увійти</a> · <a href="index.php">На головну</a>
            </p>
        </section>
    </div>
</body>
</html>
