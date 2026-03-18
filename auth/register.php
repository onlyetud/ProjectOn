<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$old = ['username' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    $old['username'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $old['email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

    if ($username === '' || strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDB();

        // Check existing username or email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute(['username' => $username, 'email' => $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors[] = 'Username or email already in use.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (:username, :email, :password)');
            try {
                $insert->execute(['username' => $username, 'email' => $email, 'password' => $hash]);
                header('Location: /projectos/auth/login.php?registered=1');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
</head>
<body>
<div class="center-wrap">
    <div class="card">
        <h2>Create account</h2>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $e): ?>
                    <div><?=htmlspecialchars($e, ENT_QUOTES, 'UTF-8')?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">

            <label for="username">Username</label>
            <input id="username" name="username" type="text" required value="<?=$old['username']?>">

            <label for="email">Email</label>
            <input id="email" name="email" type="email" required value="<?=$old['email']?>">

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <label for="password_confirm">Confirm Password</label>
            <input id="password_confirm" name="password_confirm" type="password" required>

            <button type="submit">Register</button>
        </form>

        <p class="muted">Already have an account? <a href="/projectos/auth/login.php">Sign in</a></p>
    </div>
</div>
</body>
</html>
