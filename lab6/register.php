<?php
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        $error = "Помилка реєстрації: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Реєстрація</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container" style="max-width: 400px; padding-top: 100px;">
        <form method="post" class="form-card">
            <h2>Реєстрація</h2>
            <?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
            <input type="text" name="username" class="form-control" placeholder="Ім'я користувача" required>
            <input type="email" name="email" class="form-control" placeholder="Email" required>
            <input type="password" name="password" class="form-control" placeholder="Пароль" required>
            <button type="submit" class="btn btn-primary" style="width:100%">Зареєструватися</button>
        </form>
    </div>
</body>
</html>
