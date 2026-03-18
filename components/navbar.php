<?php
// navbar.php - top navigation bar
?>
<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <a href="/projectos/dashboard/index.php" class="logo">
                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                <span>ProjectOS</span>
            </a>
        </div>

        <div class="topbar-right">
            <div class="user-badge" tabindex="0">
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                <span class="user-name-tooltip"><?=htmlspecialchars($_SESSION['username'] ?? 'User', ENT_QUOTES, 'UTF-8')?></span>
                <div class="dropdown">
                    <ul class="dropmenu">
                        <li><button type="button" class="link-like" onclick="openModal('changePasswordModal')">Change Password</button></li>
                        <li><a href="/projectos/auth/logout.php" class="link-like">Logout</a></li>
                    </ul>
                </div>
            </div>
            <button class="hamburger" id="sidebarToggle" aria-label="Toggle sidebar" aria-expanded="false"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
        </div>
    </div>
</header>
