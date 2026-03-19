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

<!-- Toast container for pop-up alerts -->
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>


        <!-- Lot Modals -->
        <div class="modal" id="addLotModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Add Project Lot</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="add_lot">

                        <input type="hidden" id="project_id" name="project_id" value="" required>
                        <!--
                        <label for="project_name">Project</label>
                        <input id="project_name" name="project_name" type="text" readonly placeholder="(selected project)">
                        -->
                        <label for="lot_code">Lot Code</label>
                        <input id="lot_code" name="lot_code" type="text">

                        <label for="lot_name">Lot Name</label>
                        <input id="lot_name" name="lot_name" type="text" required>

                        <label for="description">Description</label>
                        <textarea id="description" name="description"></textarea>

                        <label for="contractor_id">Contractor</label>
                        <select id="contractor_id" name="contractor_id">
                            <option value="">—</option>
                            <?php if (!empty($stakeholders)): foreach ($stakeholders as $s): ?>
                                <option value="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php endforeach; endif; ?>
                        </select>

                        <label for="bureau_etude_id">Bureau Etude</label>
                        <select id="bureau_etude_id" name="bureau_etude_id">
                            <option value="">—</option>
                            <?php if (!empty($stakeholders)): foreach ($stakeholders as $s): ?>
                                <option value="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php endforeach; endif; ?>
                        </select>

                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="planned">planned</option>
                            <option value="active">active</option>
                            <option value="delayed">delayed</option>
                            <option value="completed">completed</option>
                        </select>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Create</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal" id="editLotModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Edit Project Lot</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="edit_lot">
                        <input type="hidden" name="id" value="">

                        <label for="eproject_id">Project</label>
                        <select id="eproject_id" name="project_id" required>
                            <?php if (!empty($projects)): foreach ($projects as $proj): ?>
                                <option value="<?=htmlspecialchars($proj['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($proj['project_name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php endforeach; else: ?>
                                <option value="">No projects</option>
                            <?php endif; ?>
                        </select>

                        <label for="elot_code">Lot Code</label>
                        <input id="elot_code" name="lot_code" type="text">

                        <label for="elot_name">Lot Name</label>
                        <input id="elot_name" name="lot_name" type="text" required>

                        <label for="edescription">Description</label>
                        <textarea id="edescription" name="description"></textarea>

                        <label for="econtractor_id">Contractor</label>
                        <select id="econtractor_id" name="contractor_id">
                            <option value="">—</option>
                            <?php if (!empty($stakeholders)): foreach ($stakeholders as $s): ?>
                                <option value="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php endforeach; endif; ?>
                        </select>

                        <label for="ebureau_etude_id">Bureau Etude</label>
                        <select id="ebureau_etude_id" name="bureau_etude_id">
                            <option value="">—</option>
                            <?php if (!empty($stakeholders)): foreach ($stakeholders as $s): ?>
                                <option value="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?></option>
                            <?php endforeach; endif; ?>
                        </select>

                        <label for="estatus">Status</label>
                        <select id="estatus" name="status">
                            <option value="planned">planned</option>
                            <option value="active">active</option>
                            <option value="delayed">delayed</option>
                            <option value="completed">completed</option>
                        </select>

                        <div class="modal-actions">
                            <button type="submit" class="btn">Save</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal" id="deleteLotModal" aria-hidden="true">
            <div class="modal-overlay" data-close="true"></div>
            <div class="modal-window" role="dialog" aria-modal="true">
                <header class="modal-header">
                    <h2>Remove Lot</h2>
                    <button class="modal-close" data-close="true">✕</button>
                </header>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                        <input type="hidden" name="action" value="delete_lot">
                        <input type="hidden" name="id" value="">
                        <p>Are you sure you want to remove this lot?</p>
                        <div class="modal-actions">
                            <button type="submit" class="btn">Yes, remove</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

            <!-- Project Details -->
            <div class="modal" id="projectDetailsModal" aria-hidden="true">
                <div class="modal-overlay" data-close="true"></div>
                <div class="modal-window" role="dialog" aria-modal="true">
                    <header class="modal-header">
                        <h2>Project Details</h2>
                        <button class="modal-close" data-close="true">✕</button>
                    </header>
                    <div class="modal-body">
                        <div id="project-details">
                            <h3 id="pd-name"></h3>
                            <div id="pd-map" class="pd-map">Map placeholder</div>
                            <table class="detail-table">
                                <tbody>
                                    <tr><th class="detail-th">Description</th><td id="pd-desc" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Client</th><td id="pd-client" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Realisateur</th><td id="pd-realisateur" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Bureau Etude</th><td id="pd-bureau" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Start</th><td id="pd-start" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">End</th><td id="pd-end" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Status</th><td id="pd-status" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Budget</th><td id="pd-budget" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Coordinates</th><td id="pd-coords" class="detail-td"></td></tr>
                                    <tr><th class="detail-th">Other</th><td id="pd-other" class="detail-td"></td></tr>
                                </tbody>
                            </table>
                            <div class="pd-actions">
                                <a id="pd-open-map" href="#" target="_blank" class="btn">Open in Maps</a>
                                <button class="btn ghost" data-close="true">Close</button>
                            </div>
                        </div>
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
                        <p>Are you sure you want to remove this stakeholder? </p>
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
                        <p>Are you sure you want to remove this NT record?</p>
                        <div class="modal-actions">
                            <button type="submit" class="btn">Yes, remove</button>
                            <button type="button" class="btn ghost" data-close="true">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<!-- Project Modals -->
<div class="modal" id="addProjectModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Add Project</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                <input type="hidden" name="action" value="add_project">

                <label for="project_name">Project Name</label>
                <input id="project_name" name="project_name" type="text" required>

                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>

                <label for="wilaya">Wilaya</label>
                <input id="wilaya" name="wilaya" type="text">

                <label for="commune">Commune</label>
                <input id="commune" name="commune" type="text">

                <label for="latitude">Latitude</label>
                <input id="latitude" name="latitude" type="text" pattern="^-?\d+(?:\.\d+)?$">

                <label for="longitude">Longitude</label>
                <input id="longitude" name="longitude" type="text" pattern="^-?\d+(?:\.\d+)?$">

                <label for="client">Client</label>
                <input id="client" name="client" type="text">

                <label for="realisateur">Realisateur</label>
                <input id="realisateur" name="realisateur" type="text">

                <label for="bureau_etude">Bureau Etude</label>
                <input id="bureau_etude" name="bureau_etude" type="text">

                <label for="start_date">Start Date</label>
                <input id="start_date" name="start_date" type="date">

                <label for="end_date">End Date</label>
                <input id="end_date" name="end_date" type="date">

                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="planned">planned</option>
                    <option value="active">active</option>
                    <option value="delayed">delayed</option>
                    <option value="completed">completed</option>
                </select>

                <label for="budget">Budget</label>
                <input id="budget" name="budget" type="number" step="0.01">

                <label for="other_info">Other Info</label>
                <textarea id="other_info" name="other_info"></textarea>

                <div class="modal-actions">
                    <button type="submit" class="btn">Create</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project -->
<div class="modal" id="editProjectModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Edit Project</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                <input type="hidden" name="action" value="edit_project">
                <input type="hidden" name="id" value="">

                <label for="eproject_name">Project Name</label>
                <input id="eproject_name" name="project_name" type="text" required>

                <label for="edescription">Description</label>
                <textarea id="edescription" name="description"></textarea>

                <label for="ewilaya">Wilaya</label>
                <input id="ewilaya" name="wilaya" type="text">

                <label for="ecommune">Commune</label>
                <input id="ecommune" name="commune" type="text">

                <label for="elatitude">Latitude</label>
                <input id="elatitude" name="latitude" type="text" pattern="^-?\d+(?:\.\d+)?$">

                <label for="elongitude">Longitude</label>
                <input id="elongitude" name="longitude" type="text" pattern="^-?\d+(?:\.\d+)?$">

                <label for="eclient">Client</label>
                <input id="eclient" name="client" type="text">

                <label for="erealisateur">Realisateur</label>
                <input id="erealisateur" name="realisateur" type="text">

                <label for="ebureau_etude">Bureau Etude</label>
                <input id="ebureau_etude" name="bureau_etude" type="text">

                <label for="estart_date">Start Date</label>
                <input id="estart_date" name="start_date" type="date">

                <label for="eend_date">End Date</label>
                <input id="eend_date" name="end_date" type="date">

                <label for="estatus">Status</label>
                <select id="estatus" name="status">
                    <option value="planned">planned</option>
                    <option value="active">active</option>
                    <option value="delayed">delayed</option>
                    <option value="completed">completed</option>
                </select>

                <label for="ebudget">Budget</label>
                <input id="ebudget" name="budget" type="number" step="0.01">

                <label for="eother_info">Other Info</label>
                <textarea id="eother_info" name="other_info"></textarea>

                <div class="modal-actions">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Project -->
<div class="modal" id="deleteProjectModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Remove Project</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8')?>">
                <input type="hidden" name="action" value="delete_project">
                <input type="hidden" name="id" value="">
                <p>Are you sure you want to remove this project?</p>
                <div class="modal-actions">
                    <button type="submit" class="btn">Yes, remove</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
