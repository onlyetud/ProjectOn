<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please fill in both fields.';
    }

    if (empty($errors)) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT id, username, email,
         password, role, is_active FROM users WHERE email = :ident 
         OR username = :ident1 LIMIT 1');
        $stmt->execute([':ident' => $identifier, ':ident1' => $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (!(int)$user['is_active']) {
                $errors[] = 'Account is deactivated.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;

                header('Location: /project/dashboard/index.php');
                exit;
            }
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login</title>
    <link rel="stylesheet" href="/project/css/style.css">
</head>
<body>
<div class="center-wrap">
    <div class="card">
        <h2>Sign in</h2>

        <?php if (isset($_GET['registered'])): ?>
            <div class="success">Registration successful. Please sign in.</div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $e): ?>
                    <div><?=htmlspecialchars($e, ENT_QUOTES, 'UTF-8')?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">

            <label for="identifier">Email or Username</label>
            <input id="identifier" name="identifier" type="text" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Login</button>
        </form>

        <p class="muted">Don't have an account? <a href="/project/auth/register.php">Register</a></p>
    </div>
</div>
</body>
</html>
