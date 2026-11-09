<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user'] = ['id' => $user['id'], 'username' => $user['username'], 'role' => $user['role']];
        header('Location: index.php');
        exit;
    }
    $error = "Невірне ім'я користувача або пароль.";
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Вхід - Кулінарна книга</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 400px; padding-top: 100px;">
        <form method="post" class="form-card">
            <h2>Вхід в систему</h2>
            <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
            <input type="text" name="username" class="form-control" placeholder="Ім'я користувача" required>
            <input type="password" name="password" class="form-control" placeholder="Пароль" required>
            <button type="submit" class="btn btn-primary" style="width:100%">Увійти</button>
            <p style="margin-top:15px">Немає облікового запису? <a href="register.php">Зареєструватися</a></p>
        </form>
    </div>
</body>
</html>
