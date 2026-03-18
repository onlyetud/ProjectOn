<?php
session_start();

if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /project/auth/login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/project/css/style.css">
</head>
<body>
<div class="center-wrap">
    <div class="card">
        <h2>Welcome, <?=$username?></h2>
        <p class="muted">Role: <?=$role?></p>

        <p>You are logged in.</p>

        <a class="button" href="/project/auth/logout.php">Logout</a>
    </div>
</div>
</body>
</html>
