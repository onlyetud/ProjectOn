<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: /projectos/auth/login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pdo = getDB();
$errors = [];
$success = '';

// Handle POST actions: add, edit, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        try {
            // Add Contract
            if (isset($_POST['action']) && $_POST['action'] === 'add_contract') {
                $title = trim((string)($_POST['title'] ?? ''));
                $contract_number = trim((string)($_POST['contract_number'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $status = trim((string)($_POST['status'] ?? 'Draft'));
                $start_date = $_POST['start_date'] ?: null;
                $end_date = $_POST['end_date'] ?: null;
                $entreA = $_POST['entrepriseA_id'] !== '' ? (int)$_POST['entrepriseA_id'] : null;
                $entreB = $_POST['entrepriseB_id'] !== '' ? (int)$_POST['entrepriseB_id'] : null;
                $project_id = $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : null;
                $value = $_POST['value'] !== '' ? number_format((float)$_POST['value'], 2, '.', '') : null;
                $currency = trim((string)($_POST['currency'] ?? ''));

                if ($title === '') $errors[] = 'Title is required.';
                if ($contract_number === '') $errors[] = 'Contract number is required.';
                if ($currency === '') $errors[] = 'Currency is required.';
                if ($value !== null && !is_numeric($value)) $errors[] = 'Value must be numeric.';

                // uniqueness
                if (empty($errors)) {
                    $st = $pdo->prepare('SELECT COUNT(*) FROM contracts WHERE contract_number = :cn AND deleted_at IS NULL');
                    $st->execute([':cn'=>$contract_number]);
                    if ($st->fetchColumn() > 0) $errors[] = 'Contract number already exists.';
                }

                if (empty($errors)) {
                    $ins = $pdo->prepare('INSERT INTO contracts (title, description, contract_number, status, start_date, end_date, entrepriseA_id, entrepriseB_id, project_id, value, currency, created_at, updated_at) VALUES (:title,:desc,:cn,:status,:start,:end,:ea,:eb,:project_id,:value,:currency,NOW(),NOW())');
                    $ins->execute([':title'=>$title,':desc'=>$description,':cn'=>$contract_number,':status'=>$status,':start'=>$start_date,':end'=>$end_date,':ea'=>$entreA,':eb'=>$entreB,':project_id'=>$project_id,':value'=>$value,':currency'=>$currency]);
                    $success = 'Contract created.';
                }
            }

            // Edit Contract
            if (isset($_POST['action']) && $_POST['action'] === 'edit_contract') {
                $id = (int)($_POST['id'] ?? 0);
                $title = trim((string)($_POST['title'] ?? ''));
                $contract_number = trim((string)($_POST['contract_number'] ?? ''));
                $description = trim((string)($_POST['description'] ?? ''));
                $status = trim((string)($_POST['status'] ?? 'Draft'));
                $start_date = $_POST['start_date'] ?: null;
                $end_date = $_POST['end_date'] ?: null;
                $entreA = $_POST['entrepriseA_id'] !== '' ? (int)$_POST['entrepriseA_id'] : null;
                $entreB = $_POST['entrepriseB_id'] !== '' ? (int)$_POST['entrepriseB_id'] : null;
                $project_id = $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : null;
                $value = $_POST['value'] !== '' ? number_format((float)$_POST['value'], 2, '.', '') : null;
                $currency = trim((string)($_POST['currency'] ?? ''));

                if ($id <= 0) $errors[] = 'Invalid contract id.';
                if ($title === '') $errors[] = 'Title is required.';
                if ($contract_number === '') $errors[] = 'Contract number is required.';
                if ($currency === '') $errors[] = 'Currency is required.';
                if ($value !== null && !is_numeric($value)) $errors[] = 'Value must be numeric.';

                if (empty($errors)) {
                    $st = $pdo->prepare('SELECT COUNT(*) FROM contracts WHERE contract_number = :cn AND id != :id AND deleted_at IS NULL');
                    $st->execute([':cn'=>$contract_number, ':id'=>$id]);
                    if ($st->fetchColumn() > 0) $errors[] = 'Contract number already exists.';
                }

                if (empty($errors)) {
                    $up = $pdo->prepare('UPDATE contracts SET title=:title, description=:desc, contract_number=:cn, status=:status, start_date=:start, end_date=:end, entrepriseA_id=:ea, entrepriseB_id=:eb, project_id=:project_id, value=:value, currency=:currency, updated_at=NOW() WHERE id = :id');
                    $up->execute([':title'=>$title,':desc'=>$description,':cn'=>$contract_number,':status'=>$status,':start'=>$start_date,':end'=>$end_date,':ea'=>$entreA,':eb'=>$entreB,':project_id'=>$project_id,':value'=>$value,':currency'=>$currency,':id'=>$id]);
                    $success = 'Contract updated.';
                }
            }

            // Delete Contract (soft)
            if (isset($_POST['action']) && $_POST['action'] === 'delete_contract') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    $d = $pdo->prepare('UPDATE contracts SET deleted_at = NOW() WHERE id = :id');
                    $d->execute([':id'=>$id]);
                    $success = 'Contract deleted.';
                } else {
                    $errors[] = 'Invalid id for delete.';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Database error.';
        }
    }

    // store messages in session and redirect to avoid form re-submit
    if (!empty($errors)) $_SESSION['errors'] = $errors;
    if ($success !== '') $_SESSION['success'] = $success;
    header('Location: /projectos/dashboard/contracts.php');
    exit;
}

// Search and fetch contracts
$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$where = 'WHERE c.deleted_at IS NULL';
if ($q !== '') {
    $like = '%' . $q . '%';
    $where .= ' AND (c.title LIKE :like OR c.contract_number LIKE :like OR c.status LIKE :like';
    $params[':like'] = $like;
    // if numeric, also check entreprise ids
    if (is_numeric($q)) {
        $where .= ' OR c.entrepriseA_id = :eid OR c.entrepriseB_id = :eid';
        $params[':eid'] = (int)$q;
    }
    $where .= ')';
}

$sql = "SELECT c.*, sA.name AS entrepriseA_name, sB.name AS entrepriseB_name, p.project_name AS project_name FROM contracts c 
LEFT JOIN stakeholders sA ON c.entrepriseA_id = sA.id 
LEFT JOIN stakeholders sB ON c.entrepriseB_id = sB.id 
LEFT JOIN projects p ON c.project_id = p.id 
$where ORDER BY c.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$contracts = $stmt->fetchAll();

// fetch stakeholders for dropdowns
$st = $pdo->prepare('SELECT id, name FROM stakeholders WHERE deleted_at IS NULL ORDER BY name ASC');
$st->execute();
$stakeholders = $st->fetchAll();


// fetch project for dropdowns
$st = $pdo->prepare('SELECT id, project_name FROM projects WHERE deleted_at IS NULL ORDER BY project_name ASC');
$st->execute();
$projects = $st->fetchAll();

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Contracts</title>
    <link rel="stylesheet" href="/projectos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    .contract-table th, .contract-table td{padding:10px 12px;border-bottom:1px solid #eef2f7}
    .contract-table thead th{background:transparent;text-align:left}
    .actions .btn{padding:6px 8px}
    @media(max-width:700px){ .contract-table thead{display:none} .contract-table tr{display:block;margin-bottom:12px} .contract-table td{display:flex;justify-content:space-between;padding:8px} }
    </style>
</head>
<body>
<?php require_once __DIR__ . '/../components/navbar.php'; ?>
<?php require_once __DIR__ . '/../components/sidebar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Contracts</h1>
            <p class="muted">Create, view, edit and delete contracts.</p>
        </div>

        <?php if (!empty($_SESSION['success'])): ?><div class="alert success"><?php echo e($_SESSION['success']); unset($_SESSION['success']); ?></div><?php endif; ?>
        <?php if (!empty($_SESSION['errors'])): ?><div class="alert errors"><?php foreach($_SESSION['errors'] as $er) echo '<div>'.e($er).'</div>'; unset($_SESSION['errors']); ?></div><?php endif; ?>

        <div class="controls">
            <form method="get">
                <input name="q" type="search" placeholder="Search title, number, status or entreprise id" value="<?php echo e($q); ?>">
            </form>
            <div>
                <button class="btn btn-add" type="button" onclick="openModal('addContractModal')"><i class="fa-solid fa-plus"></i> Add New Contract</button>
            </div>
        </div>

        <div class="table-wrap">
            <table class="contract-table stake-table">
                <thead>
                    <tr>
                        <th hidden >ID</th>
                        <th>Contract #</th>
                        <th>Title</th>
                        <th hidden >Entreprise A</th>
                        <th hidden >Entreprise B</th>
                        <th>Project</th>
                        <th>Value</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($contracts)): ?>
                    <tr><td colspan="9" class="muted">No contracts found.</td></tr>
                <?php endif; ?>
                <?php foreach ($contracts as $c): ?>
                    <tr class="contract-row" data-id="<?php echo e($c['id']); ?>"
                        data-contract_number="<?php echo e($c['contract_number']); ?>"
                        data-title="<?php echo e($c['title']); ?>"
                        data-description="<?php echo e($c['description']); ?>"
                        data-entrepriseA_id="<?php echo e($c['entrepriseA_id']); ?>"
                        data-entrepriseB_id="<?php echo e($c['entrepriseB_id']); ?>"
                        data-project_id="<?php echo e($c['project_id']); ?>"
                        data-value="<?php echo e(number_format((float)$c['value'],2,'.','')); ?>"
                        data-currency="<?php echo e($c['currency']); ?>"
                        data-start_date="<?php echo e($c['start_date']); ?>"
                        data-end_date="<?php echo e($c['end_date']); ?>"
                        data-status="<?php echo e($c['status']); ?>"
                    >
                        <td hidden><?php echo e($c['id']); ?></td>
                        <td><?php echo e($c['contract_number']); ?></td>
                        <td><?php echo e($c['title']); ?><div class="muted muted-small"><?php echo e(substr($c['description'] ?? '',0,80)); ?></div></td>
                        <td hidden><?php echo e($c['entrepriseA_name'] ?? $c['entrepriseA_id']); ?></td>
                        <td hidden><?php echo e($c['entrepriseB_name'] ?? $c['entrepriseB_id']); ?></td>
                        <td><?php echo e($c['project_name'] ?? $c['project_id']); ?></td>
                        <td><?php echo e(number_format((float)$c['value'],2)); ?> <?php echo e($c['currency']); ?></td>
                        <td><?php echo e($c['start_date']); ?> / <?php echo e($c['end_date']); ?></td>
                        <td><?php echo e($c['status']); ?></td>
                        <td>
                            <div class="actions">
                                <button class="btn btn-view ghost view-contract" title="View"><i class="fa-solid fa-eye"></i></button>
                                <button class="btn btn-edit" title="Edit" type="button" onclick="(function(b){var tr=b.closest('.contract-row'); openModal('editContractModal'); 
                                fillForm('editContractModal', 
                                {id:tr.getAttribute('data-id'), contract_number:tr.getAttribute('data-contract_number'), title:tr.getAttribute('data-title'), description:tr.getAttribute('data-description'), entrepriseA_id:tr.getAttribute('data-entrepriseA_id'), entrepriseB_id:tr.getAttribute('data-entrepriseB_id'), project_id:tr.getAttribute('data-project_id'), value:tr.getAttribute('data-value'), currency:tr.getAttribute('data-currency'), start_date:tr.getAttribute('data-start_date'), end_date:tr.getAttribute('data-end_date'), status:tr.getAttribute('data-status')});})(this)"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="btn btn-delete" type="button" onclick="(function(b){var tr=b.closest('.contract-row'); fillForm('deleteContractModal',{id:tr.getAttribute('data-id')}); openModal('deleteContractModal');})(this)"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Add Contract Modal -->
<div class="modal" id="addContractModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Add Contract</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add_contract">
                <label>Title<input name="title" type="text" required></label>
                <label>Contract Number<input name="contract_number" type="text" required></label>
                <label>Description<textarea name="description"></textarea></label>
                <label>Status<select name="status">
                    <option>Draft</option>
                    <option>Active</option>
                    <option>Closed</option>
                </select></label>
                <label>Start Date<input type="date" name="start_date"></label>
                <label>End Date<input type="date" name="end_date"></label>
                <label>Entreprise A<select name="entrepriseA_id">
                    <option value="">—</option>
                    <?php foreach($stakeholders as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Entreprise B<select name="entrepriseB_id">
                    <option value="">—</option>
                    <?php foreach($stakeholders as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                    <?php endforeach; ?>
                </select></label>

                
                <label>Project<select name="project_id">
                    <option value="">—</option>
                    <?php foreach($projects as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['project_name']); ?></option>
                    <?php endforeach; ?>
                </select></label>

                <label>Value<input name="value" type="text" required></label>
                <label>Currency<input name="currency" type="text" required value="USD"></label>
                <div class="modal-actions">
                    <button type="submit" class="btn">Create</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Contract Modal -->
<div class="modal" id="editContractModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Edit Contract</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="edit_contract">
                <input type="hidden" name="id" value="">
                <label>Title<input name="title" type="text" required></label>
                <label>Contract Number<input name="contract_number" type="text" required></label>
                <label>Description<textarea name="description"></textarea></label>
                <label>Status<select name="status">
                    <option>Draft</option>
                    <option>Active</option>
                    <option>Closed</option>
                </select></label>
                <label>Start Date<input type="date" name="start_date"></label>
                <label>End Date<input type="date" name="end_date"></label>
                <label>Entreprise A<select name="entrepriseA_id">
                    <option value="">—</option>
                    <?php foreach($stakeholders as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                    <?php endforeach; ?>
                </select></label>
                <label>Entreprise B<select name="entrepriseB_id">
                    <option value="">—</option>
                    <?php foreach($stakeholders as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['name']); ?></option>
                    <?php endforeach; ?>
                </select></label>
        <label>Project<select name="project_id">
                    <option value="">—</option>
                    <?php foreach($projects as $s): ?>
                        <option value="<?php echo e($s['id']); ?>"><?php echo e($s['project_name']); ?></option>
                    <?php endforeach; ?>
                </select></label>

                <label>Value<input name="value" type="text" required></label>
                <label>Currency<input name="currency" type="text" required></label>
                <div class="modal-actions">
                    <button type="submit" class="btn">Save</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Contract Modal -->
<div class="modal" id="deleteContractModal" aria-hidden="true">
    <div class="modal-overlay" data-close="true"></div>
    <div class="modal-window" role="dialog" aria-modal="true">
        <header class="modal-header">
            <h2>Delete Contract</h2>
            <button class="modal-close" data-close="true">✕</button>
        </header>
        <div class="modal-body">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="delete_contract">
                <input type="hidden" name="id" value="">
                <p>Are you sure you want to delete this contract?</p>
                <div class="modal-actions">
                    <button type="submit" class="btn">Delete</button>
                    <button type="button" class="btn ghost" data-close="true">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/modal.php'; ?>
<script src="/projectos/js/modal.js"></script>
<script src="/projectos/js/contracts.js"></script>
</body>
</html>
