<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Authentication check
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /projectos/auth/login.php');
    exit;
}

$errors = [];
$success = '';

// CSRF token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    }

    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $confirm === '' || $current === '') {
        $errors[] = 'All fields are required.';
    }

    if ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($current, $user['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $up = $pdo->prepare('UPDATE users SET password = :pw WHERE id = :id');
                $up->execute([':pw' => $hashed, ':id' => $_SESSION['user_id']]);
                $success = 'Password changed successfully.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred while updating password.';
        }
    }
}

// Example statistics (attempt to query, fall back to placeholders)
$totalProjects = 0;
$totalContracts = 0;
$totalUsers = 0;
try {
    $pdo = getDB();
    $r = $pdo->query('SELECT COUNT(*) AS c FROM projects')->fetch();
    $totalProjects = $r['c'] ?? 0;
    $r = $pdo->query('SELECT COUNT(*) AS c FROM contracts')->fetch();
    $totalContracts = $r['c'] ?? 0;
    $r = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch();
    $totalUsers = $r['c'] ?? 0;
} catch (Exception $e) {
    // If tables don't exist yet, keep zeros as placeholders
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Welcome, <?=htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8')?></h1>
            <p class="muted">Here's an overview of your workspace.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert success"><?=htmlspecialchars($success, ENT_QUOTES, 'UTF-8')?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert errors">
                <?php foreach ($errors as $e): ?>
                    <div><?=htmlspecialchars($e, ENT_QUOTES, 'UTF-8')?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <section class="cards">
            <div class="card stat">
                <div class="stat-icon"><i class="fa-solid fa-folder-open" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-title">Total Projects</div>
                    <div class="stat-value"><?=htmlspecialchars((string)$totalProjects, ENT_QUOTES, 'UTF-8')?></div>
                </div>
            </div>

            <div class="card stat">
                <div class="stat-icon"><i class="fa-solid fa-file-contract" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-title">Total Contracts</div>
                    <div class="stat-value"><?=htmlspecialchars((string)$totalContracts, ENT_QUOTES, 'UTF-8')?></div>
                </div>
            </div>

            <div class="card stat">
                <div class="stat-icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                <div class="stat-body">
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value"><?=htmlspecialchars((string)$totalUsers, ENT_QUOTES, 'UTF-8')?></div>
                </div>
            </div>
        </section>

        <section class="content-grid">
            <div class="card">
                <h3>Recent Activity</h3>
                <p class="muted">No activity yet — this area will show recent changes.</p>
            </div>
            <div class="card">
                <h3>Quick Actions</h3>
                <p>
                    <button class="btn" onclick="openModal('addProjectModal')">Add Project</button>
                    <button class="btn ghost" onclick="openModal('changePasswordModal')">Change Password</button>
                </p>
            </div>
        </section>
    </div>
</main>

<?php require_once __DIR__ . '/../components/modal.php'; ?>

<script src="/projectos/js/modal.js"></script>
</body>
</html>

