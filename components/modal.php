<?php
// modal.php - reusable modal markup
?>
<!-- Change Password Modal -->
<div class="modal" id="changePasswordModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Change Password</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                <input type="hidden" name="action" value="change_password">

                <label for="current_password">Current Password</label>
                <input id="current_password" name="current_password" type="password" required>

                <label for="new_password">New Password</label>
                <input id="new_password" name="new_password" type="password" required>

                <label for="confirm_password">Confirm New Password</label>
                <input id="confirm_password" name="confirm_password" type="password" required>

                <div class="modal-actions">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Project Modal (placeholder) -->
<div class="modal" id="addProjectModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Add Project</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" novalidate>
                <label for="project_title">Title</label>
                <input id="project_title" name="project_title" type="text" required>

                <label for="project_desc">Description</label>
                <textarea id="project_desc" name="project_desc"></textarea>

                <div class="modal-actions">
                    <button type="button" class="btn">Create</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
