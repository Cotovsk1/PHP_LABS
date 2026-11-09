<?php
require_once __DIR__ . '/db.php';

$stmt = $pdo->query("SELECT * FROM recipes ORDER BY id DESC");
$recipes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Кулінарна книга (Lab 6)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-container">
        <header class="header">
            <div class="header-content">
                <div>
                    <span class="header-badge" style="background:#f97316; color:white; padding:4px 10px; border-radius:20px; font-size:0.75rem;">Практична №6 · API</span>
                    <h1>Кулінарна книга (REST API)</h1>
                    <p class="subtitle">Єдина точка входу: <code>api.php?resource=recipes</code></p>
                </div>
            </div>
            <div>
                <a href="setup.php" class="btn btn-secondary" style="text-decoration:none; font-size:0.9rem;">⚙️ Setup БД</a>
            </div>
        </header>

        <section class="results-section">
            <h2 class="section-title">📋 Списокrecipeів від API</h2>
            <table class="recipes-table" style="margin-top:20px;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Назва</th>
                        <th>Інгредієнти</th>
                        <th>Час (хв)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $r): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
                        <td><?= htmlspecialchars($r['ingredients']) ?></td>
                        <td><span style="background: <?= $r['cook_time_min'] <= 20 ? '#dcfce7;color:#166534' : '#f1f5f9;color:#475569' ?>; padding:4px 8px; border-radius:6px; font-weight:600;"><?= $r['cook_time_min'] ?> хв</span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>