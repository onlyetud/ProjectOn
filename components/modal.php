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

        <!-- Stakeholder Modals -->
        <!-- Add Stakeholder -->
        <div class="modal" id="addStakeholderModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Add Stakeholder</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="add_stakeholder">

                        <label for="name">Name</label>
                        <input id="name" name="name" type="text" required>

                        <label for="organization">Organization</label>
                        <input id="organization" name="organization" type="text">

                        <label for="type">Type</label>
                        <input id="type" name="type" type="text">

                        <label for="email">Email</label>
                        <input id="email" name="email" type="email">

                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="text">

                        <label for="wilaya">Wilaya</label>
                        <input id="wilaya" name="wilaya" type="text">

                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"></textarea>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Create</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Stakeholder -->
        <div class="modal" id="editStakeholderModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Edit Stakeholder</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="edit_stakeholder">
                        <input type="hidden" name="id" value="">

                        <label for="ename">Name</label>
                        <input id="ename" name="name" type="text" required>

                        <label for="eorganization">Organization</label>
                        <input id="eorganization" name="organization" type="text">

                        <label for="etype">Type</label>
                        <input id="etype" name="type" type="text">

                        <label for="ewilaya">Wilaya</label>
                        <input id="ewilaya" name="wilaya" type="text">

                        <label for="ephone">Phone</label>
                        <input id="ephone" name="phone" type="text">

                        <label for="enotes">Notes</label>
                        <textarea id="enotes" name="notes"></textarea>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Save</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Stakeholder -->
        <div class="modal" id="deleteStakeholderModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Remove Stakeholder</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="delete_stakeholder">
                        <input type="hidden" name="id" value="">
                        <p>Are you sure you want to remove this stakeholder? This is a soft delete.</p>
                        <div class="modal-actions">
                            <button type="submit" class="btn">Yes, remove</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add NT -->
        <div class="modal" id="addNTModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Add NT</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="add_nt">
                        <input type="hidden" name="stakeholder_id" value="">

                        <label for="nt_code">NT Code</label>
                        <input id="nt_code" name="nt_code" type="text" required>

                        <label for="nt_name">NT Name</label>
                        <input id="nt_name" name="nt_name" type="text" required>

                        <label for="description">Description</label>
                        <textarea id="description" name="description"></textarea>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Create</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit NT -->
        <div class="modal" id="editNTModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Edit NT</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="edit_nt">
                        <input type="hidden" name="id" value="">

                        <label for="ent_code">NT Code</label>
                        <input id="ent_code" name="nt_code" type="text" required>

                        <label for="ent_name">NT Name</label>
                        <input id="ent_name" name="nt_name" type="text" required>

                        <label for="edescription">Description</label>
                        <textarea id="edescription" name="description"></textarea>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Save</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete NT -->
        <div class="modal" id="deleteNTModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Remove NT</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="delete_nt">
                        <input type="hidden" name="id" value="">
                        <p>Are you sure you want to remove this NT record? This is a soft delete.</p>
                        <div class="modal-actions">
                            <button type="submit" class="btn">Yes, remove</button>
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
