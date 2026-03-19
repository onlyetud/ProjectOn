<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Authentication
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /projectos/auth/login.php');
    exit;
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $pdo = getDB();

        // Add Stakeholder
        if (isset($_POST['action']) && $_POST['action'] === 'add_stakeholder') {
            $name = trim($_POST['name'] ?? '');
            $org = trim($_POST['organization'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $stmt = $pdo->prepare('INSERT INTO stakeholders (name, organization,  email, phone, wilaya, notes, created_at, updated_at) 
            VALUES (:name,:org,:email,:phone,:wilaya,:notes,NOW(),NOW())');
            $stmt->execute([':name'=>$name,':org'=>$org,':email'=>$email,':phone'=>$phone,':wilaya'=>$wilaya,':notes'=>$notes]);
            $success = 'Stakeholder added.';
        }

        // Edit Stakeholder
        if (isset($_POST['action']) && $_POST['action'] === 'edit_stakeholder') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $org = trim($_POST['organization'] ?? '');
            $type = trim($_POST['type'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $wilaya = trim($_POST['wilaya'] ?? '');
            $notes = trim($_POST['notes'] ?? '');

            $stmt = $pdo->prepare('UPDATE stakeholders SET name=:name, organization=:org , email=:email, phone=:phone, wilaya=:wilaya, notes=:notes, updated_at=NOW() WHERE id = :id');
            $stmt->execute([':name'=>$name,':org'=>$org,  ':email'=>$email,':phone'=>$phone,':wilaya'=>$wilaya,':notes'=>$notes,':id'=>$id]);
            $success = 'Stakeholder updated.';
        }

        // Delete Stakeholder (soft)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_stakeholder') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE stakeholders SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id'=>$id]);
            $success = 'Stakeholder removed.';
        }

        // Add NT
        if (isset($_POST['action']) && $_POST['action'] === 'add_nt') {
            $sid = (int)($_POST['stakeholder_id'] ?? 0);
            $code = trim($_POST['nt_code'] ?? '');
            $name = trim($_POST['nt_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            $stmt = $pdo->prepare('INSERT INTO stakeholder_nt (stakeholder_id, nt_code, nt_name, description, created_at, updated_at) VALUES (:sid,:code,:name,:desc,NOW(),NOW())');
            $stmt->execute([':sid'=>$sid,':code'=>$code,':name'=>$name,':desc'=>$desc]);
            $success = 'NT record added.';
        }

        // Edit NT
        if (isset($_POST['action']) && $_POST['action'] === 'edit_nt') {
            $id = (int)($_POST['id'] ?? 0);
            $code = trim($_POST['nt_code'] ?? '');
            $name = trim($_POST['nt_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');

            $stmt = $pdo->prepare('UPDATE stakeholder_nt SET nt_code=:code, nt_name=:name, description=:desc, updated_at=NOW() WHERE id = :id');
            $stmt->execute([':code'=>$code,':name'=>$name,':desc'=>$desc,':id'=>$id]);
            $success = 'NT record updated.';
        }

        // Delete NT (soft)
        if (isset($_POST['action']) && $_POST['action'] === 'delete_nt') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE stakeholder_nt SET deleted_at = NOW() WHERE id = :id');
            $stmt->execute([':id'=>$id]);
            $success = 'NT record removed.';
        }
    }

    // Redirect to avoid resubmission
    header('Location: /projectos/dashboard/stakeholders.php');
    exit;
}

// Search
$q = trim($_GET['q'] ?? '');

$pdo = getDB();
$params = [];
$where = 'WHERE deleted_at IS NULL';
if ($q !== '') {
    $keyword= '%' . $q . '%';
    $where .= ' AND (
    name LIKE :like OR
    organization LIKE :like2 OR
    wilaya LIKE :like4)'; 
    
    $params[':like'] = $keyword; 
    $params[':like2'] = $keyword; 
    $params[':like4'] = $keyword;
}

$stmt = $pdo->prepare("SELECT id, name, organization , wilaya, phone FROM stakeholders  $where ORDER BY created_at DESC");
$stmt->execute($params);
$stakeholders = $stmt->fetchAll();

$stakeIds = array_column($stakeholders, 'id');
$ntsByStake = [];
if (!empty($stakeIds)) {
    $in = implode(',', array_fill(0, count($stakeIds), '?'));
    $stmt = $pdo->prepare("SELECT id, stakeholder_id, nt_code, nt_name, description FROM stakeholder_nt WHERE stakeholder_id IN ($in) AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute($stakeIds);
    $nts = $stmt->fetchAll();
    foreach ($nts as $n) {
        $ntsByStake[$n['stakeholder_id']][] = $n;
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Stakeholders</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Stakeholders</h1>
            <p class="muted">Manage stakeholders and their NT sub-records.</p>
        </div>

        <div class="controls">
            <form method="get">
                <input name="q" type="search" placeholder="Search name, organization, type, wilaya" value="<?=htmlspecialchars($q, ENT_QUOTES, 'UTF-8')?>">
            </form>
            <div>
                <button class="btn btn-add" onclick="openModal('addStakeholderModal')">Add Stakeholder</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="stake-table">
                <thead>
                    <tr> 
                        <th>Name</th>
                        <th>Organization</th> 
                        <th>Wilaya</th>
                        <th>Phone</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($stakeholders as $s): ?>
                    <tr class="stake-row" data-id="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>">
                      
                        <td><?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($s['organization'], ENT_QUOTES, 'UTF-8')?></td>
                  
                        <td><?=htmlspecialchars($s['wilaya'], ENT_QUOTES, 'UTF-8')?></td>
                        <td><?=htmlspecialchars($s['phone'], ENT_QUOTES, 'UTF-8')?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-view ghost open-view-nts" data-id="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-chevron-down"></i></button>
                                <button class="btn btn-add ghost open-add-nt" data-id="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-plus"></i></button>
                                <button class="btn btn-edit ghost open-edit-stakeholder" 
                                    data-id="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"
                                    data-name="<?=htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8')?>"
                                    data-organization="<?=htmlspecialchars($s['organization'], ENT_QUOTES, 'UTF-8')?>"
                            
                                    data-wilaya="<?=htmlspecialchars($s['wilaya'], ENT_QUOTES, 'UTF-8')?>"
                                    data-phone="<?=htmlspecialchars($s['phone'], ENT_QUOTES, 'UTF-8')?>"
                                ><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn btn-delete ghost open-delete-stakeholder" data-id="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <tr class="nt-row" data-parent="<?=htmlspecialchars($s['id'], ENT_QUOTES, 'UTF-8')?>">
                        <td colspan="7">
                            <div class="nt-sub"> 
                                <table class="nt-table">
                                 
                                    <tbody>
                                    <?php if (!empty($ntsByStake[$s['id']] ?? [])): foreach ($ntsByStake[$s['id']] as $n): ?>
                                        <tr>
                                            <td><?=htmlspecialchars($n['nt_code'], ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($n['nt_name'], ENT_QUOTES, 'UTF-8')?></td>
                                            <td><?=htmlspecialchars($n['description'], ENT_QUOTES, 'UTF-8')?></td>
                                            <td>
                                                    <div class="actions">
                                                    <button class="btn btn-edit ghost open-edit-nt" 
                                                        data-id="<?=htmlspecialchars($n['id'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-stakeholder_id="<?=htmlspecialchars($n['stakeholder_id'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-nt_code="<?=htmlspecialchars($n['nt_code'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-nt_name="<?=htmlspecialchars($n['nt_name'], ENT_QUOTES, 'UTF-8')?>"
                                                        data-description="<?=htmlspecialchars($n['description'], ENT_QUOTES, 'UTF-8')?>"
                                                    ><i class="fa-solid fa-pen-to-square"></i></button>
                                                    <button class="btn btn-delete ghost open-delete-nt" data-id="<?=htmlspecialchars($n['id'], ENT_QUOTES, 'UTF-8')?>"><i class="fa-solid fa-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; else: ?>
                                        <tr><td colspan="4" class="muted">No NT records</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../components/modal.php'; ?>
<script src="/projectos/js/modal.js"></script>
<script>
// populate and handle stakeholder/nt modals
document.addEventListener('DOMContentLoaded', function(){
    // open edit stakeholder modal and populate
    document.querySelectorAll('.open-edit-stakeholder').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const modal = document.getElementById('editStakeholderModal');
            modal.querySelector('input[name=id]').value = id;
            modal.querySelector('input[name=name]').value = this.dataset.name||'';
            modal.querySelector('input[name=organization]').value = this.dataset.organization||'';
            modal.querySelector('input[name=type]').value = this.dataset.type||'';
            modal.querySelector('input[name=wilaya]').value = this.dataset.wilaya||'';
            modal.querySelector('input[name=phone]').value = this.dataset.phone||'';
            openModal('editStakeholderModal');
        });
    });

    // open add nt modal
    document.querySelectorAll('.open-add-nt').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const m = document.getElementById('addNTModal');
            m.querySelector('input[name=stakeholder_id]').value = id;
            m.querySelector('input[name=nt_code]').value = '';
            m.querySelector('input[name=nt_name]').value = '';
            m.querySelector('textarea[name=description]').value = '';
            openModal('addNTModal');
        });
    });

    // open edit nt modal
    document.querySelectorAll('.open-edit-nt').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const m = document.getElementById('editNTModal');
            m.querySelector('input[name=id]').value = id;
            m.querySelector('input[name=nt_code]').value = this.dataset.nt_code||'';
            m.querySelector('input[name=nt_name]').value = this.dataset.nt_name||'';
            m.querySelector('textarea[name=description]').value = this.dataset.description||'';
            openModal('editNTModal');
        });
    });

    // delete confirmations
    document.querySelectorAll('.open-delete-stakeholder').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const m = document.getElementById('deleteStakeholderModal');
            m.querySelector('input[name=id]').value = id;
            openModal('deleteStakeholderModal');
        });
    });
    document.querySelectorAll('.open-delete-nt').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const m = document.getElementById('deleteNTModal');
            m.querySelector('input[name=id]').value = id;
            openModal('deleteNTModal');
        });
    });

    // toggle NT rows
    document.querySelectorAll('.open-view-nts').forEach(function(btn){
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            const row = document.querySelector('.nt-row[data-parent="'+id+'"]');
            if (!row) return;
            const shown = row.style.display !== 'none';
            row.style.display = shown ? 'none' : 'table-row';
            // rotate icon
            this.querySelector('i').style.transform = shown ? '' : 'rotate(180deg)';
        });
    });
});
</script>
</body>
</html>
