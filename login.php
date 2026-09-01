<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/database.php';

if (current_user()) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $account = $stmt->fetch();
    if ($account && password_verify($password, $account['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$account['id'];
        header('Location: index.php'); exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Log in - Twit</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="auth"><a class="logo" href="index.php">Twit</a><section class="card"><h1>Log in</h1><?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?><form method="post"><label>Username<input name="username" required autofocus></label><label>Password<input type="password" name="password" required></label><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><button class="button">Log in</button></form><p>Need an account? <a href="register.php">Sign up</a></p></section></main></body></html>
